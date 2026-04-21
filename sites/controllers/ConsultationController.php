<?php
class ConsultationController extends Controller
{
    private $consultationModel;
    private $aiConfigModel;
    private $aiUsageLogModel;

    public function __construct()
    {
        parent::__construct();
        $this->consultationModel = new ConsultationModel();
        $this->aiConfigModel     = new AiConfigModel();
        $this->aiUsageLogModel   = new AiUsageLogModel();
    }

    /**
     * AI 상담 소견 생성 (AJAX POST)
     */
    public function generate()
    {
        $this->requireAuth();
        $this->validateAjaxCsrf();
        set_time_limit(60);

        $memberId              = (int)$this->input('member_id');
        $currentPostureId      = (int)$this->input('current_posture_session_id');
        $currentFootId         = (int)$this->input('current_foot_session_id');
        $previousPostureId     = (int)$this->input('previous_posture_session_id');
        $previousFootId        = (int)$this->input('previous_foot_session_id');

        // 검증: 최소 하나의 현재 세션 필요
        if (!$currentPostureId && !$currentFootId) {
            $this->json(['success' => false, 'message' => '현재 분석 세션을 하나 이상 선택해주세요.'], 400);
        }

        // 회원 조회
        $memberModel = new MemberModel();
        $member = $memberModel->findById($memberId);
        if (!$member || !$member['consultation_enabled']) {
            $this->json(['success' => false, 'message' => '상담소견 기능이 활성화되지 않은 회원입니다.'], 403);
        }

        // AI 설정 확인
        $franchiseId = Auth::franchiseId();
        $apiKey = $this->aiConfigModel->getDecryptedApiKey($franchiseId);
        if (!$apiKey) {
            $this->json(['success' => false, 'message' => 'AI 설정이 되어있지 않습니다. 관리자에게 API 키 설정을 요청하세요.'], 400);
        }
        $aiConfig = $this->aiConfigModel->getByFranchise($franchiseId);

        // 리포트 데이터 로드
        $postureReportModel = new PostureReportModel();
        $footReportModel    = new FootReportModel();
        $postureSessionModel = new PostureSessionModel();
        $footSessionModel    = new FootSessionModel();

        $currentPosture  = $currentPostureId ? $postureReportModel->getBySessionId($currentPostureId) : null;
        $currentFoot     = $currentFootId ? $footReportModel->getBySessionId($currentFootId) : null;
        $previousPosture = $previousPostureId ? $postureReportModel->getBySessionId($previousPostureId) : null;
        $previousFoot    = $previousFootId ? $footReportModel->getBySessionId($previousFootId) : null;

        // 세션 날짜 정보
        $currentPostureSession  = $currentPostureId ? $postureSessionModel->findById($currentPostureId) : null;
        $currentFootSession     = $currentFootId ? $footSessionModel->findById($currentFootId) : null;
        $previousPostureSession = $previousPostureId ? $postureSessionModel->findById($previousPostureId) : null;
        $previousFootSession    = $previousFootId ? $footSessionModel->findById($previousFootId) : null;

        // 프롬프트 구성
        $systemPrompt = $this->getSystemPrompt();
        $userMessage = $this->buildUserMessage(
            $member,
            $currentPosture, $currentPostureSession,
            $currentFoot, $currentFootSession,
            $previousPosture, $previousPostureSession,
            $previousFoot, $previousFootSession
        );

        // 서비스 유형 결정
        $serviceType = $this->determineServiceType($currentPostureId, $currentFootId);

        // AI API 호출
        $user = Auth::user();
        try {
            $provider = $aiConfig ? $aiConfig['ai_provider'] : 'CLAUDE';
            $modelName = $aiConfig ? $aiConfig['model_name'] : 'claude-sonnet-4-20250514';

            $client = new AiClient($apiKey, $modelName, $provider);
            $result = $client->generateConsultation($systemPrompt, $userMessage);

            // 사용 이력 기록
            $logId = $this->aiUsageLogModel->logUsage([
                'admin_id'          => $user['id'],
                'member_id'         => $memberId,
                'service_type'      => $serviceType,
                'model_name'        => $modelName,
                'prompt_tokens'     => $result['usage']['prompt_tokens'],
                'completion_tokens' => $result['usage']['completion_tokens'],
                'total_tokens'      => $result['usage']['total_tokens'],
                'cost_usd'          => $this->estimateCost($result['usage'], $provider),
                'status'            => 'SUCCESS',
            ]);

            $this->json([
                'success'    => true,
                'data'       => $result['content'],
                'usage_log_id' => $logId,
                'csrf_token' => Auth::generateCsrfToken(),
            ]);
        } catch (RuntimeException $e) {
            // 실패 이력 기록
            $this->aiUsageLogModel->logUsage([
                'admin_id'      => $user['id'],
                'member_id'     => $memberId,
                'service_type'  => $serviceType,
                'model_name'    => $aiConfig ? $aiConfig['model_name'] : 'unknown',
                'status'        => 'FAILED',
                'error_message' => $e->getMessage(),
            ]);

            $this->json([
                'success'    => false,
                'message'    => 'AI 소견 생성에 실패했습니다: ' . $e->getMessage(),
                'csrf_token' => Auth::generateCsrfToken(),
            ], 500);
        }
    }

    /**
     * 상담 소견 저장 (POST)
     */
    public function store()
    {
        $this->requireAuth();
        $this->validateCsrf();

        $user = Auth::user();
        $memberId = (int)$this->input('member_id');

        $data = [
            'member_id'                   => $memberId,
            'writer_type'                 => $user['role'] === 'STAFF' ? 'ADMIN' : 'ADMIN',
            'writer_id'                   => $user['id'],
            'writer_name'                 => $user['name'],
            'service_type'                => $this->input('service_type', 'POSTURE'),
            'current_posture_session_id'  => $this->input('current_posture_session_id') ?: null,
            'current_foot_session_id'     => $this->input('current_foot_session_id') ?: null,
            'previous_posture_session_id' => $this->input('previous_posture_session_id') ?: null,
            'previous_foot_session_id'    => $this->input('previous_foot_session_id') ?: null,
            'overall_assessment'          => $this->input('overall_assessment', ''),
            'improvement_note'            => $this->input('improvement_note') ?: null,
            'concern_note'                => $this->input('concern_note') ?: null,
            'recommendation'              => $this->input('recommendation') ?: null,
            'comparison_summary'          => $this->input('comparison_summary') ?: null,
            'is_ai_generated'             => 1,
        ];

        $consultationId = $this->consultationModel->insert($data);

        // AI 사용 이력에 상담 ID 연결
        $usageLogId = (int)$this->input('usage_log_id');
        if ($usageLogId) {
            $this->aiUsageLogModel->linkConsultation($usageLogId, $consultationId);
        }

        $this->flash('success', 'AI 상담 소견이 저장되었습니다.');
        $this->redirect('member/detail', ['id' => $memberId, 'tab' => 'consultation']);
    }

    /**
     * 상담 소견 상세 보기
     */
    public function detail()
    {
        $this->requireAuth();
        $id = (int)$this->input('id');
        $consultation = $this->consultationModel->findById($id);
        if (!$consultation) {
            $this->flash('danger', '상담 소견을 찾을 수 없습니다.');
            $this->redirect('dashboard');
            return;
        }

        $memberModel = new MemberModel();
        $member = $memberModel->findById($consultation['member_id']);

        // 근거 리포트 데이터 로드
        $postureReportModel = new PostureReportModel();
        $footReportModel    = new FootReportModel();
        $postureSessionModel = new PostureSessionModel();
        $footSessionModel    = new FootSessionModel();

        $curPostureReport  = null; $curPostureSession  = null;
        $prevPostureReport = null; $prevPostureSession = null;
        $curFootReport     = null; $curFootSession     = null;
        $prevFootReport    = null; $prevFootSession    = null;

        if ($consultation['current_posture_session_id']) {
            $curPostureSession = $postureSessionModel->findById($consultation['current_posture_session_id']);
            $curPostureReport  = $postureReportModel->getBySessionId($consultation['current_posture_session_id']);
        }
        if ($consultation['previous_posture_session_id']) {
            $prevPostureSession = $postureSessionModel->findById($consultation['previous_posture_session_id']);
            $prevPostureReport  = $postureReportModel->getBySessionId($consultation['previous_posture_session_id']);
        }
        if ($consultation['current_foot_session_id']) {
            $curFootSession = $footSessionModel->findById($consultation['current_foot_session_id']);
            $curFootReport  = $footReportModel->getBySessionId($consultation['current_foot_session_id']);
        }
        if ($consultation['previous_foot_session_id']) {
            $prevFootSession = $footSessionModel->findById($consultation['previous_foot_session_id']);
            $prevFootReport  = $footReportModel->getBySessionId($consultation['previous_foot_session_id']);
        }

        $this->view('consultation/detail', [
            'pageTitle'          => '상담 소견 상세',
            'consultation'       => $consultation,
            'member'             => $member,
            'curPostureSession'  => $curPostureSession,
            'curPostureReport'   => $curPostureReport,
            'prevPostureSession' => $prevPostureSession,
            'prevPostureReport'  => $prevPostureReport,
            'curFootSession'     => $curFootSession,
            'curFootReport'      => $curFootReport,
            'prevFootSession'    => $prevFootSession,
            'prevFootReport'     => $prevFootReport,
        ]);
    }

    /**
     * 리포트 미리보기 (AJAX GET)
     * ?type=posture|foot&session_id=N
     */
    public function previewReport()
    {
        $this->requireAuth();

        $type = $this->input('type', '');
        $sessionId = (int)$this->input('session_id');

        if (!$sessionId || !in_array($type, ['posture', 'foot'])) {
            $this->json(['success' => false, 'message' => '잘못된 요청입니다.'], 400);
        }

        if ($type === 'posture') {
            $session = (new PostureSessionModel())->findById($sessionId);
            $report = (new PostureReportModel())->getBySessionId($sessionId);
            if (!$session || !$report) {
                $this->json(['success' => false, 'message' => '리포트를 찾을 수 없습니다.'], 404);
            }

            $labels = $this->getPostureLabels();
            $data = [];
            foreach ($labels as $col => $label) {
                if (isset($report[$col]) && $report[$col] !== null && $report[$col] !== '') {
                    $value = $report[$col];
                    $dirCol = str_replace('_angle', '_direction', $col);
                    if (strpos($col, '_angle') !== false && isset($report[$dirCol]) && $report[$dirCol]) {
                        $value = $value . '도 (' . $this->translateDirection($report[$dirCol]) . ')';
                    }
                    $data[] = ['label' => $label, 'value' => $value];
                }
            }

            // 이미지
            $images = [];
            $imgCols = [
                'front_user_img' => '정면', 'side_right_user_img' => '우측면',
                'side_left_user_img' => '좌측면', 'back_user_img' => '뒷면',
                'skeleton_current_front_img' => '현재 정면 스켈레톤', 'skeleton_current_side_img' => '현재 측면 스켈레톤',
            ];
            foreach ($imgCols as $col => $label) {
                if (!empty($report[$col])) {
                    $images[] = ['label' => $label, 'url' => postureImgUrl($report[$col])];
                }
            }

            $this->json([
                'success' => true,
                'type'    => 'posture',
                'session' => [
                    'date'   => substr($session['captured_at'], 0, 10),
                    'height' => $session['height'],
                    'weight' => $session['weight'],
                ],
                'data'   => $data,
                'images' => $images,
            ]);

        } else {
            $session = (new FootSessionModel())->findById($sessionId);
            $report = (new FootReportModel())->getBySessionId($sessionId);
            if (!$session || !$report) {
                $this->json(['success' => false, 'message' => '리포트를 찾을 수 없습니다.'], 404);
            }

            $labels = $this->getFootLabels();
            $data = [];
            foreach ($labels as $col => $label) {
                if (isset($report[$col]) && $report[$col] !== null && $report[$col] !== '') {
                    $data[] = ['label' => $label, 'value' => $report[$col]];
                }
            }

            $images = [];
            $imgCols = [
                'footprint_img' => '족문', 'heatmap_img' => '히트맵',
                'left_footprint_img' => '왼발 측정', 'right_footprint_img' => '오른발 측정',
            ];
            foreach ($imgCols as $col => $label) {
                if (!empty($report[$col])) {
                    $images[] = ['label' => $label, 'url' => footImgUrl($report[$col])];
                }
            }

            $this->json([
                'success' => true,
                'type'    => 'foot',
                'session' => [
                    'date'   => substr($session['captured_at'], 0, 10),
                    'height' => $session['height'],
                    'weight' => $session['weight'],
                ],
                'data'   => $data,
                'images' => $images,
            ]);
        }
    }

    // ─── 프롬프트 관련 ───

    private function getSystemPrompt()
    {
        return <<<'PROMPT'
당신은 피트니스/필라테스 전문 상담 어시스턴트입니다.
회원의 자세분석 및/또는 족부분석 데이터를 기반으로 전문적인 상담 소견을 작성합니다.

작성 규칙:
1. 반드시 한국어로 작성하세요.
2. 전문 용어를 사용하되, 일반인도 이해할 수 있도록 쉽게 풀어서 설명하세요.
3. 구체적인 수치를 인용하며 소견을 작성하세요.
4. 이전 데이터가 없으면 improvement_note와 comparison_summary는 빈 문자열("")로 작성하세요.
5. 출력은 반드시 아래 JSON 형식으로만 응답하세요. 다른 텍스트는 포함하지 마세요.

{
  "overall_assessment": "종합 소견 (3-5문장, 현재 상태에 대한 전반적 평가)",
  "improvement_note": "개선된 점 (이전 데이터 대비 좋아진 부분, 없으면 빈 문자열)",
  "concern_note": "우려/주의 사항 (2-3문장, 주의가 필요한 부분과 이유)",
  "recommendation": "향후 권장 사항 (2-3개 구체적 운동/습관/관리 제안, 번호 매기기)",
  "comparison_summary": "전/후 비교 요약 (이전 데이터 대비 변화 기술, 없으면 빈 문자열)"
}
PROMPT;
    }

    private function buildUserMessage($member, $curPosture, $curPostureSession, $curFoot, $curFootSession, $prevPosture, $prevPostureSession, $prevFoot, $prevFootSession)
    {
        $lines = [];

        // 회원 정보
        $lines[] = '## 회원 정보';
        $age = $member['birth_date'] ? $this->calcAge($member['birth_date']) . '세' : '미입력';
        $gender = $member['gender'] === 'M' ? '남' : ($member['gender'] === 'F' ? '여' : '미입력');
        $lines[] = sprintf(
            '- 성별: %s, 나이: %s, 키: %scm, 몸무게: %skg',
            $gender, $age,
            $member['height'] ?: '미입력',
            $member['weight'] ?: '미입력'
        );
        if ($member['memo']) {
            $lines[] = '- 특이사항: ' . $member['memo'];
        }
        $lines[] = '';

        // 현재 자세분석
        if ($curPosture) {
            $date = $curPostureSession ? substr($curPostureSession['captured_at'], 0, 10) : '날짜 미상';
            $lines[] = "## 현재 자세분석 ({$date})";
            $lines = array_merge($lines, $this->formatPostureReport($curPosture));
            $lines[] = '';
        }

        // 이전 자세분석
        if ($prevPosture) {
            $date = $prevPostureSession ? substr($prevPostureSession['captured_at'], 0, 10) : '날짜 미상';
            $lines[] = "## 이전 자세분석 ({$date})";
            $lines = array_merge($lines, $this->formatPostureReport($prevPosture));
            $lines[] = '';
        }

        // 현재 족부분석
        if ($curFoot) {
            $date = $curFootSession ? substr($curFootSession['captured_at'], 0, 10) : '날짜 미상';
            $lines[] = "## 현재 족부분석 ({$date})";
            $lines = array_merge($lines, $this->formatFootReport($curFoot));
            $lines[] = '';
        }

        // 이전 족부분석
        if ($prevFoot) {
            $date = $prevFootSession ? substr($prevFootSession['captured_at'], 0, 10) : '날짜 미상';
            $lines[] = "## 이전 족부분석 ({$date})";
            $lines = array_merge($lines, $this->formatFootReport($prevFoot));
            $lines[] = '';
        }

        return implode("\n", $lines);
    }

    private function formatPostureReport($report)
    {
        $labels = $this->getPostureLabels();
        $lines = [];
        foreach ($labels as $col => $label) {
            if (isset($report[$col]) && $report[$col] !== null && $report[$col] !== '') {
                $value = $report[$col];
                // 방향 컬럼이 있으면 같이 표시
                $dirCol = str_replace('_angle', '_direction', $col);
                if (strpos($col, '_angle') !== false && isset($report[$dirCol]) && $report[$dirCol]) {
                    $dir = $this->translateDirection($report[$dirCol]);
                    $lines[] = "- {$label}: {$value}도 ({$dir})";
                } else {
                    $lines[] = "- {$label}: {$value}";
                }
            }
        }
        return $lines;
    }

    private function formatFootReport($report)
    {
        $labels = $this->getFootLabels();
        $lines = [];
        foreach ($labels as $col => $label) {
            if (isset($report[$col]) && $report[$col] !== null && $report[$col] !== '') {
                $lines[] = "- {$label}: {$report[$col]}";
            }
        }
        return $lines;
    }

    private function getPostureLabels()
    {
        return [
            'horizontal_eye_angle'        => '눈 수평 기울기',
            'horizontal_shoulder_angle'    => '어깨 수평 기울기',
            'horizontal_hip_angle'         => '골반 수평 기울기',
            'horizontal_leg_angle'         => '무릎 수평 기울기',
            'shoulder_ear_angle'           => '우측 목 기울기 (어깨-귀)',
            'foot_shoulder_angle'          => '우측 발-어깨 각도',
            'shoulder_hip_angle'           => '우측 허리 기울기',
            'foot_leg_angle'               => '우측 발-무릎 각도',
            'other_shoulder_ear_angle'     => '좌측 목 기울기 (어깨-귀)',
            'other_foot_shoulder_angle'    => '좌측 발-어깨 각도',
            'other_shoulder_hip_angle'     => '좌측 허리 기울기',
            'other_foot_leg_angle'         => '좌측 발-무릎 각도',
            'spine_angle'                  => '척추 각도',
            'pcmt'                         => '목 하중(PCMT, kg)',
            'height_loss'                  => '키 손실(cm)',
            'balance_point_x'             => '신체 균형점 X',
            'balance_point_y'             => '신체 균형점 Y',
            'total_deviation'              => '근골격 편차',
            'posture_score'                => '근골격 지수(점수)',
            'left_genu_varus_angle'        => '좌측 O/X 다리 각도',
            'right_genu_varus_angle'       => '우측 O/X 다리 각도',
            'left_back_knee'               => '좌측 백니(반장슬) 각도',
            'right_back_knee'              => '우측 백니(반장슬) 각도',
        ];
    }

    private function getFootLabels()
    {
        return [
            'left_foot_length'       => '좌측 발 길이(mm)',
            'left_foot_width'        => '좌측 발 너비(mm)',
            'right_foot_length'      => '우측 발 길이(mm)',
            'right_foot_width'       => '우측 발 너비(mm)',
            'left_forefoot'          => '좌측 전족부 족압',
            'left_arch'              => '좌측 중족부 족압',
            'left_heel'              => '좌측 후족부 족압',
            'right_forefoot'         => '우측 전족부 족압',
            'right_arch'             => '우측 중족부 족압',
            'right_heel'             => '우측 후족부 족압',
            'left_staheli'           => '좌측 Staheli Index',
            'right_staheli'          => '우측 Staheli Index',
            'left_chippaux'          => '좌측 Chippaux Index',
            'right_chippaux'         => '우측 Chippaux Index',
            'left_clarke'            => '좌측 Clarke Angle',
            'right_clarke'           => '우측 Clarke Angle',
            'left_arch_index'        => '좌측 Arch Index',
            'right_arch_index'       => '우측 Arch Index',
            'left_foot_type'         => '좌측 발 형태',
            'right_foot_type'        => '우측 발 형태',
            'hallux_valgus_left_angle'  => '무지외반증 좌측 각도',
            'hallux_valgus_right_angle' => '무지외반증 우측 각도',
            'pelvis'                 => '골반 좌우 비율',
            'spine'                  => '척추 전후 비율',
        ];
    }

    private function translateDirection($dir)
    {
        $map = [
            'left'  => '왼쪽', 'right' => '오른쪽',
            'front' => '앞쪽', 'back'  => '뒤쪽',
            'O'     => 'O다리', 'X'    => 'X다리',
        ];
        return isset($map[$dir]) ? $map[$dir] : $dir;
    }

    private function determineServiceType($postureId, $footId)
    {
        if ($postureId && $footId) return 'BOTH';
        if ($postureId) return 'POSTURE';
        return 'FOOT';
    }

    private function calcAge($birthDate)
    {
        $birth = new DateTime($birthDate);
        $now = new DateTime();
        return $now->diff($birth)->y;
    }

    private function estimateCost($usage, $provider)
    {
        $prompt = $usage['prompt_tokens'] ?? 0;
        $completion = $usage['completion_tokens'] ?? 0;

        if ($provider === 'CLAUDE') {
            // Claude Sonnet 4 기준
            return ($prompt / 1000000 * 3.0) + ($completion / 1000000 * 15.0);
        }
        // OpenAI GPT-4o 기준
        return ($prompt / 1000000 * 2.5) + ($completion / 1000000 * 10.0);
    }
}
