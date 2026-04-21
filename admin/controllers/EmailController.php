<?php
/**
 * 이메일 발송 컨트롤러
 */
class EmailController extends Controller
{
    private $emailLogModel;
    private $emailService;
    private $configModel;

    public function __construct()
    {
        parent::__construct();
        $this->emailLogModel = new EmailLogModel();
        $this->configModel   = new SystemConfigModel();
        $this->emailService  = new EmailService();
    }

    /**
     * 이메일 발송 이력 목록
     */
    public function list()
    {
        $this->requireAuth();

        $keyword = $this->input('search', '');
        $status  = $this->input('status', '');
        $page    = max(1, (int)$this->input('page', 1));

        $result = $this->emailLogModel->search($keyword, $status, $page, ITEMS_PER_PAGE);

        $this->view('email/list', [
            'pageTitle'  => '이메일 관리',
            'emails'     => $result['rows'],
            'pagination' => new Pagination($result['total'], ITEMS_PER_PAGE, $page),
            'keyword'    => $keyword,
            'status'     => $status,
            'smtpConfigured' => $this->emailService->isConfigured(),
        ]);
    }

    /**
     * 이메일 작성 & 발송
     */
    public function compose()
    {
        $this->requireAuth();
        $this->requireRole(['SUPER_ADMIN', 'ADMIN', 'OPERATOR']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCsrf();
            $this->processSend();
            return;
        }

        $formData = isset($_SESSION['form_data']) ? $_SESSION['form_data'] : [];
        unset($_SESSION['form_data']);

        // 수신자 프리셋 옵션용 데이터
        $tenantModel = new TenantModel();

        $this->view('email/compose', [
            'pageTitle'       => '이메일 작성',
            'email'           => $formData,
            'tenants'         => $tenantModel->findAll(['status' => 'ACTIVE'], 'company_name ASC', 1000, 0),
            'csrfToken'       => Auth::generateCsrfToken(),
            'smtpConfigured'  => $this->emailService->isConfigured(),
        ]);
    }

    /**
     * 이메일 상세 보기
     */
    public function detail()
    {
        $this->requireAuth();

        $id    = (int)$this->input('id');
        $email = $this->emailLogModel->findByIdWithRelations($id);
        if (!$email) {
            $this->flash('danger', '이메일을 찾을 수 없습니다.');
            $this->redirect('email/list');
            return;
        }

        $this->view('email/detail', [
            'pageTitle' => '이메일 상세',
            'email'     => $email,
            'csrfToken' => Auth::generateCsrfToken(),
        ]);
    }

    /**
     * 이메일 재발송
     */
    public function resend()
    {
        $this->requireAuth();
        $this->requireRole(['SUPER_ADMIN', 'ADMIN', 'OPERATOR']);
        $this->validateCsrf();

        $id    = (int)$this->input('id');
        $email = $this->emailLogModel->findById($id);
        if (!$email) {
            $this->flash('danger', '이메일을 찾을 수 없습니다.');
            $this->redirect('email/list');
            return;
        }

        $success = $this->emailService->send(
            $email['to_email'],
            $email['subject'],
            $email['body_html'],
            '',
            $email['cc_email'] ?? '',
            $email['bcc_email'] ?? ''
        );

        // 새 로그로 기록
        $logData = [
            'admin_id'   => Auth::user()['id'],
            'to_email'   => $email['to_email'],
            'cc_email'   => $email['cc_email'] ?? '',
            'bcc_email'  => $email['bcc_email'] ?? '',
            'subject'    => $email['subject'],
            'body_html'  => $email['body_html'],
            'status'     => $success ? 'SENT' : 'FAILED',
            'error_message' => $success ? null : $this->emailService->getLastError(),
            'sent_at'    => $success ? date('Y-m-d H:i:s') : null,
        ];
        $newId = $this->emailLogModel->insert($logData);

        $this->auditLog('CREATE', 'email_log', $newId, "이메일 재발송: {$email['subject']} → {$email['to_email']}");

        if ($success) {
            $this->flash('success', '이메일이 재발송되었습니다.');
        } else {
            $this->flash('danger', '재발송 실패: ' . $this->emailService->getLastError());
        }
        $this->redirect('email/detail', ['id' => $newId]);
    }

    /**
     * 이메일 삭제 (로그만 삭제)
     */
    public function delete()
    {
        $this->requireAuth();
        $this->requireRole(['SUPER_ADMIN', 'ADMIN']);
        $this->validateCsrf();

        $id    = (int)$this->input('id');
        $email = $this->emailLogModel->findById($id);
        if ($email) {
            $this->emailLogModel->delete($id);
            $this->auditLog('DELETE', 'email_log', $id, "이메일 로그 삭제: {$email['subject']}", $email);
            $this->flash('success', '이메일 로그가 삭제되었습니다.');
        }
        $this->redirect('email/list');
    }

    /**
     * SMTP 설정 화면 & 저장
     */
    public function settings()
    {
        $this->requireAuth();
        $this->requireRole(['SUPER_ADMIN', 'ADMIN']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCsrf();

            $smtpData = [
                'smtp_host'       => $this->input('smtp_host', ''),
                'smtp_port'       => $this->input('smtp_port', '587'),
                'smtp_user'       => $this->input('smtp_user', ''),
                'smtp_pass'       => $this->input('smtp_pass', ''),
                'smtp_from_email' => $this->input('smtp_from_email', ''),
                'smtp_from_name'  => $this->input('smtp_from_name', 'CentralAdmin'),
                'smtp_encryption' => $this->input('smtp_encryption', 'tls'),
            ];

            $before = $this->configModel->getSmtpConfig();
            $this->configModel->saveSmtpConfig($smtpData);
            $after = $this->configModel->getSmtpConfig();

            // 비밀번호는 감사 로그에서 마스킹
            $logBefore = $before; $logBefore['smtp_pass'] = $logBefore['smtp_pass'] ? '****' : '';
            $logAfter  = $after;  $logAfter['smtp_pass']  = $logAfter['smtp_pass']  ? '****' : '';
            $this->auditLog('CONFIG', 'system_config', null, 'SMTP 설정 변경', $logBefore, $logAfter);

            $this->flash('success', 'SMTP 설정이 저장되었습니다.');
            $this->redirect('email/settings');
            return;
        }

        $smtpConfig = $this->configModel->getSmtpConfig();

        $this->view('email/settings', [
            'pageTitle'  => 'SMTP 설정',
            'smtp'       => $smtpConfig,
            'csrfToken'  => Auth::generateCsrfToken(),
        ]);
    }

    /**
     * SMTP 연결 테스트 (AJAX)
     * POST로 폼 데이터를 받아 저장하지 않고 즉시 테스트
     */
    public function testSmtp()
    {
        $this->requireAuth();
        $this->requireRole(['SUPER_ADMIN', 'ADMIN']);

        header('Content-Type: application/json');

        // POST로 전달된 설정값으로 테스트 (저장 전 테스트 가능)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $config = [
                'smtp_host'       => $this->input('smtp_host', ''),
                'smtp_port'       => $this->input('smtp_port', '587'),
                'smtp_user'       => $this->input('smtp_user', ''),
                'smtp_pass'       => $this->input('smtp_pass', ''),
                'smtp_from_email' => $this->input('smtp_from_email', ''),
                'smtp_from_name'  => $this->input('smtp_from_name', 'CentralAdmin'),
                'smtp_encryption' => $this->input('smtp_encryption', 'tls'),
            ];
            $service = new EmailService($config);
        } else {
            // GET: DB에 저장된 설정으로 테스트
            $service = $this->emailService;
        }

        $result = $service->testConnection();
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // -----------------------------------------------
    // Private
    // -----------------------------------------------

    private function processSend()
    {
        $data = [
            'to_email'  => $this->input('to_email', ''),
            'cc_email'  => $this->input('cc_email', ''),
            'bcc_email' => $this->input('bcc_email', ''),
            'subject'   => $this->input('subject', ''),
            'body_html' => isset($_POST['body_html']) ? $_POST['body_html'] : '',
        ];

        // 유효성 검사
        $v = new Validator($data);
        $v->required('to_email', '수신자')->required('subject', '제목')->required('body_html', '본문');
        if (!$v->passes()) {
            $this->flash('danger', $v->firstError());
            $_SESSION['form_data'] = $data;
            $this->redirect('email/compose');
            return;
        }

        // 이메일 발송
        $success = $this->emailService->send(
            $data['to_email'],
            $data['subject'],
            $data['body_html'],
            '',
            $data['cc_email'],
            $data['bcc_email']
        );

        // DB 로그 기록
        $logData = [
            'admin_id'      => Auth::user()['id'],
            'to_email'      => $data['to_email'],
            'cc_email'      => $data['cc_email'],
            'bcc_email'     => $data['bcc_email'],
            'subject'       => $data['subject'],
            'body_html'     => $data['body_html'],
            'status'        => $success ? 'SENT' : 'FAILED',
            'error_message' => $success ? null : $this->emailService->getLastError(),
            'sent_at'       => $success ? date('Y-m-d H:i:s') : null,
        ];
        $id = $this->emailLogModel->insert($logData);

        $this->auditLog('CREATE', 'email_log', $id, "이메일 발송: {$data['subject']} → {$data['to_email']}");

        if ($success) {
            $this->flash('success', '이메일이 성공적으로 발송되었습니다.');
            $this->redirect('email/detail', ['id' => $id]);
        } else {
            $this->flash('danger', '이메일 발송 실패: ' . $this->emailService->getLastError());
            $this->redirect('email/detail', ['id' => $id]);
        }
    }
}
