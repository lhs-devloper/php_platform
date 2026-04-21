<?php
/**
 * ThemeManager - 테마 로딩/렌더링 중앙 관리 클래스
 *
 * Level 1: CSS 변수 오버라이드 (색상/로고/폰트)
 * Level 2: 레이아웃 프리셋 (사이드바 위치, 배치)
 * Level 3: DB 저장 Mustache 템플릿으로 뷰 오버라이드
 */
class ThemeManager
{
    private static $settings = null;
    private static $overrides = null;
    private static $initialized = false;

    /** CSS 변수 키 매핑: setting_key => CSS custom property */
    private static $cssVarMap = [
        'accent'       => '--accent',
        'accent_dark'  => '--accent-dark',
        'accent_light' => '--accent-light',
        'accent_subtle'=> '--accent-subtle',
        'primary'      => '--primary',
        'success'      => '--success',
        'info'         => '--info',
        'warning'      => '--warning',
        'danger'       => '--danger',
        'dark'         => '--dark',
        'body_bg'      => '--body-bg',
    ];

    /** Level 3: 오버라이드 허용 뷰 화이트리스트 */
    private static $overridableViews = [
        'dashboard/index',
        'layout/sidebar',
        'layout/topbar',
        'member/list',
        'member/detail',
        'member/form',
    ];

    /** 뷰별 사용 가능 변수 문서 (템플릿 에디터 UI용) */
    private static $viewVariables = [
        'dashboard/index' => [
            'pageTitle'       => '페이지 제목 (string)',
            'memberStats'     => '회원 통계 (array: total, active, inactive, withdrawn)',
            'todayCounts'     => '오늘 분석 수 (array: posture, foot)',
            'consultationCnt' => 'AI 상담 활성 회원 수 (int)',
            'recentPosture'   => '최근 자세분석 세션 (array)',
            'recentFoot'      => '최근 족부분석 세션 (array)',
        ],
        'layout/sidebar' => [
            'appName'        => '앱 이름 (string)',
            'appVersion'     => '앱 버전 (string)',
            'logoUrl'        => '로고 URL (string)',
            'siteTitle'      => '사이트 타이틀 (string)',
            'currentRoute'   => '현재 라우트 (string)',
            'newNoticeCount' => '새 공지 수 (int)',
        ],
        'layout/topbar' => [
            'pageTitle'   => '페이지 제목 (string)',
            'serviceType' => '서비스 유형 라벨 (string)',
            'adminName'   => '관리자 이름 (string)',
            'adminLoginId'=> '관리자 로그인 ID (string)',
            'adminRole'   => '관리자 역할 (string)',
        ],
        'member/list' => [
            'pageTitle'   => '페이지 제목 (string)',
            'keyword'     => '검색 키워드 (string)',
            'members'     => '회원 목록 (array: id, name, phone, gender, status, class_name, instructor_name, created_at)',
            'pagination'  => '페이지네이션 HTML (string)',
        ],
        'member/detail' => [
            'pageTitle' => '페이지 제목 (string)',
            'member'    => '회원 정보 (array: id, name, phone, gender, birth_date, height, weight, status, class_name, instructor_name, memo)',
        ],
        'member/form' => [
            'pageTitle'  => '페이지 제목 (string)',
            'isEdit'     => '수정 모드 여부 (bool)',
            'member'     => '회원 데이터 (array)',
        ],
    ];

    /**
     * 앱 시작 시 1회 호출 - 모든 테마 설정을 한번에 로드
     */
    public static function init()
    {
        if (self::$initialized) return;
        self::$initialized = true;

        try {
            $db = Database::getInstance();

            // theme_settings 전체 로드 (보통 30행 이하)
            $stmt = $db->query("SELECT setting_group, setting_key, setting_value FROM theme_settings");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            self::$settings = [];
            foreach ($rows as $row) {
                self::$settings[$row['setting_group']][$row['setting_key']] = $row['setting_value'];
            }

            // theme_view_overrides 활성 항목 로드
            $stmt = $db->query("SELECT view_path, override_type, html_content, css_content FROM theme_view_overrides WHERE is_active = 1");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            self::$overrides = [];
            foreach ($rows as $row) {
                self::$overrides[$row['view_path']] = $row;
            }
        } catch (PDOException $e) {
            // 테이블 미존재 등 오류 시 빈 상태로 진행 (기본 디자인)
            self::$settings = [];
            self::$overrides = [];
        }
    }

    /**
     * 설정값 조회 (기본값 폴백)
     */
    public static function get($group, $key, $default = '')
    {
        if (isset(self::$settings[$group][$key]) && self::$settings[$group][$key] !== '') {
            return self::$settings[$group][$key];
        }
        return $default;
    }

    /**
     * 그룹 전체 설정 조회
     */
    public static function getGroup($group)
    {
        return isset(self::$settings[$group]) ? self::$settings[$group] : [];
    }

    /**
     * CSS 오버라이드 존재 여부
     */
    public static function hasCssOverrides()
    {
        return !empty(self::$settings['colors']);
    }

    /**
     * Level 1: CSS 커스텀 프로퍼티 오버라이드 문자열 생성
     * 예: ":root { --accent: #ff6600; --dark: #2d3748; }"
     */
    public static function cssVariables()
    {
        $colors = self::getGroup('colors');
        if (empty($colors)) return '';

        $vars = [];
        foreach ($colors as $key => $value) {
            if (!isset(self::$cssVarMap[$key])) continue;
            // 색상값 검증: hex 색상만 허용
            if (!preg_match('/^#[0-9a-fA-F]{3,8}$/', $value)) continue;
            $vars[] = self::$cssVarMap[$key] . ': ' . $value;
        }

        if (empty($vars)) return '';
        return ':root { ' . implode('; ', $vars) . '; }';
    }

    /**
     * Level 2: 레이아웃 프리셋에 따른 master 파일 경로 반환
     */
    public static function layoutFile()
    {
        return BASE_PATH . '/views/layout/master.php';
    }

    /**
     * Level 2: 사이드바 위치 (left/right)
     */
    public static function sidebarPosition()
    {
        return self::get('layout', 'sidebar_position', 'left');
    }

    /**
     * Level 2: 레이아웃 프리셋 (default/compact/wide)
     */
    public static function layoutPreset()
    {
        return self::get('layout', 'layout_preset', 'default');
    }

    /**
     * Level 2: 탑바 스타일 (default/colored/dark)
     */
    public static function topbarStyle()
    {
        return self::get('layout', 'topbar_style', 'default');
    }

    /**
     * Level 3: 뷰 오버라이드 존재 여부 확인
     * @return array|null 오버라이드 데이터 또는 null
     */
    public static function resolveView($viewPath)
    {
        if (isset(self::$overrides[$viewPath])) {
            return self::$overrides[$viewPath];
        }
        return null;
    }

    /**
     * 오버라이드 가능 뷰 목록 반환
     */
    public static function getOverridableViews()
    {
        return self::$overridableViews;
    }

    /**
     * 뷰별 사용 가능 변수 문서 반환
     */
    public static function getViewVariables($viewPath = null)
    {
        if ($viewPath !== null) {
            return isset(self::$viewVariables[$viewPath]) ? self::$viewVariables[$viewPath] : [];
        }
        return self::$viewVariables;
    }

    // ─── Level 3: 안전한 Mustache 스타일 템플릿 엔진 ───

    /**
     * Mustache 스타일 템플릿 렌더링 (PHP 실행 불가)
     *
     * 지원 구문:
     *   {{variable}}              - HTML 이스케이프 출력
     *   {{{variable}}}            - raw 출력 (사전 살균된 HTML용)
     *   {{#if variable}}...{{/if}}    - 조건부 블록
     *   {{#each items}}...{{/each}}   - 반복 블록 ({{@index}}, {{@key}}, {{this}} 지원)
     *
     * @param string $template Mustache 템플릿 문자열
     * @param array $data 컨트롤러에서 전달한 데이터
     * @return string 렌더링된 HTML
     */
    public static function renderTemplate($template, array $data)
    {
        // 보안: PHP 태그 제거
        $template = self::sanitizeTemplate($template);

        // {{#each items}}...{{/each}} 처리
        $template = preg_replace_callback(
            '/\{\{#each\s+(\w+)\}\}(.*?)\{\{\/each\}\}/s',
            function ($matches) use ($data) {
                $key = $matches[1];
                $inner = $matches[2];
                if (!isset($data[$key]) || !is_array($data[$key])) return '';

                $output = '';
                $index = 0;
                foreach ($data[$key] as $k => $item) {
                    $itemTemplate = $inner;
                    // {{@index}}, {{@key}}
                    $itemTemplate = str_replace('{{@index}}', $index, $itemTemplate);
                    $itemTemplate = str_replace('{{@key}}', htmlspecialchars($k), $itemTemplate);

                    if (is_array($item)) {
                        // 배열 아이템: {{field}} 치환
                        foreach ($item as $field => $val) {
                            $itemTemplate = str_replace('{{{' . $field . '}}}', (string)$val, $itemTemplate);
                            $itemTemplate = str_replace('{{' . $field . '}}', htmlspecialchars((string)$val), $itemTemplate);
                        }
                    } else {
                        // 스칼라 아이템: {{this}} 치환
                        $itemTemplate = str_replace('{{{this}}}', (string)$item, $itemTemplate);
                        $itemTemplate = str_replace('{{this}}', htmlspecialchars((string)$item), $itemTemplate);
                    }
                    $output .= $itemTemplate;
                    $index++;
                }
                return $output;
            },
            $template
        );

        // {{#if variable}}...{{/if}} 처리
        $template = preg_replace_callback(
            '/\{\{#if\s+(\w+)\}\}(.*?)\{\{\/if\}\}/s',
            function ($matches) use ($data) {
                $key = $matches[1];
                $content = $matches[2];
                if (!empty($data[$key])) {
                    return $content;
                }
                return '';
            },
            $template
        );

        // {{{variable}}} - raw 출력 (이스케이프 없음)
        $template = preg_replace_callback(
            '/\{\{\{(\w+)\}\}\}/',
            function ($matches) use ($data) {
                $key = $matches[1];
                return isset($data[$key]) ? (string)$data[$key] : '';
            },
            $template
        );

        // {{variable}} - HTML 이스케이프 출력
        $template = preg_replace_callback(
            '/\{\{(\w+)\}\}/',
            function ($matches) use ($data) {
                $key = $matches[1];
                return isset($data[$key]) ? htmlspecialchars((string)$data[$key]) : '';
            },
            $template
        );

        return $template;
    }

    /**
     * 템플릿 보안 살균 - PHP/JS 실행 코드 제거
     */
    public static function sanitizeTemplate($template)
    {
        // PHP 태그 제거
        $template = preg_replace('/<\?(?:php|=).*?\?>/si', '', $template);
        // <script> 태그 제거
        $template = preg_replace('/<script\b[^>]*>.*?<\/script>/si', '', $template);
        // 이벤트 핸들러 속성 제거 (onclick, onerror 등)
        $template = preg_replace('/\bon\w+\s*=\s*["\'][^"\']*["\']/i', '', $template);
        $template = preg_replace('/\bon\w+\s*=\s*\S+/i', '', $template);
        return $template;
    }

    /**
     * CSS 보안 살균 - 위험한 CSS 패턴 제거
     */
    public static function sanitizeCss($css)
    {
        // expression() 제거
        $css = preg_replace('/expression\s*\(/i', '/* blocked */(', $css);
        // javascript: 제거
        $css = preg_replace('/javascript\s*:/i', '/* blocked */', $css);
        // @import 제거
        $css = preg_replace('/@import\b/i', '/* blocked */', $css);
        // url(data: 제거 (데이터 URI 악용 방지)
        $css = preg_replace('/url\s*\(\s*["\']?\s*data\s*:/i', 'url(/* blocked */', $css);
        // behavior: 제거 (IE HTC 파일)
        $css = preg_replace('/behavior\s*:/i', '/* blocked */', $css);
        return $css;
    }

    /**
     * 뷰별 Mustache 기본 템플릿 반환
     * PHP 코드 대신 {{variable}} 문법의 깨끗한 기본 소스를 제공
     */
    public static function getDefaultTemplate($viewPath)
    {
        $templates = [

'layout/sidebar' => '<nav id="sidebar" class="vh-100 p-3 d-flex flex-column" style="width:260px;min-width:260px;background:var(--dark,#1e293b);">
    <div class="px-3 py-4">
        <h5 class="text-white mb-0 fw-bold">
            {{#if logoUrl}}
                <img src="{{logoUrl}}" alt="Logo" style="max-height:32px;vertical-align:middle;">
            {{/if}}
            {{siteTitle}}
        </h5>
        <div class="mt-2">
            <span class="sidebar-badge text-uppercase">FRANCHISE PORTAL v{{appVersion}}</span>
        </div>
    </div>

    <hr class="mx-3 opacity-10 text-white mt-0">

    <ul class="nav flex-column mb-auto">
        <li class="nav-item">
            <a class="nav-link text-white" href="index.php?route=dashboard">
                <i class="bi bi-speedometer2"></i> <span>대시보드</span>
            </a>
        </li>

        <li class="text-muted mt-3 mb-1 px-3 small text-uppercase fw-bold" style="letter-spacing:0.05em;font-size:0.7rem;">회원 서비스</li>
        <li class="nav-item">
            <a class="nav-link text-white" href="index.php?route=member/list">
                <i class="bi bi-people"></i> <span>회원 관리</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link text-white" href="index.php?route=class_code/list">
                <i class="bi bi-collection"></i> <span>수강반 관리</span>
            </a>
        </li>

        <li class="text-muted mt-3 mb-1 px-3 small text-uppercase fw-bold" style="letter-spacing:0.05em;font-size:0.7rem;">고객 지원</li>
        <li class="nav-item">
            <a class="nav-link text-white" href="index.php?route=notice/list">
                <i class="bi bi-megaphone"></i>
                <span class="flex-grow-1">공지사항</span>
                {{#if newNoticeCount}}
                    <span class="badge bg-danger rounded-pill">{{newNoticeCount}}</span>
                {{/if}}
            </a>
        </li>
    </ul>

    <div class="px-3 mb-2">
        <div class="card sidebar-support-card shadow-none mb-0">
            <div class="card-body p-3">
                <div class="support-label mb-1">Help &amp; Support</div>
                <div class="support-mail">support@ai-sw.net</div>
            </div>
        </div>
    </div>
</nav>',

'layout/topbar' => '<nav class="navbar navbar-expand navbar-light bg-white sticky-top px-4 py-3">
    <div class="container-fluid px-0">
        <h5 class="navbar-text mb-0 fw-bold text-dark">{{pageTitle}}</h5>

        <div class="ms-auto d-flex align-items-center">
            {{#if serviceType}}
            <span class="badge bg-accent-subtle text-accent border border-accent-subtle px-3 py-2 d-none d-md-inline-block me-2">
                {{serviceType}}
            </span>
            <div class="vr mx-2 opacity-10 d-none d-md-block"></div>
            {{/if}}

            {{#if adminName}}
            <div class="dropdown">
                <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                    <div class="text-end me-3 d-none d-lg-block">
                        <div class="fw-bold small text-dark lh-1">{{adminName}}</div>
                        <small class="text-muted" style="font-size:0.7rem;">{{adminLoginId}}</small>
                    </div>
                    <div class="rounded-circle bg-accent d-flex align-items-center justify-content-center text-white shadow-sm" style="width:36px;height:36px;">
                        <i class="bi bi-person-fill fs-5"></i>
                    </div>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2" style="border-radius:0.75rem;min-width:200px;">
                    <li class="px-3 py-3 border-bottom mb-1">
                        <div class="fw-bold text-dark">{{adminName}}</div>
                        <small class="text-muted">{{adminLoginId}}</small>
                    </li>
                    <li>
                        <a class="dropdown-item text-danger py-2" href="index.php?route=auth/logout">
                            <i class="bi bi-box-arrow-right me-2"></i> 로그아웃
                        </a>
                    </li>
                </ul>
            </div>
            {{/if}}
        </div>
    </div>
</nav>',

'member/list' => '<!-- 검색 바 -->
<div class="filter-bar shadow-sm mb-4">
    <form method="GET" class="row g-3 align-items-end">
        <input type="hidden" name="route" value="member/list">
        <div class="col-md-4">
            <label class="form-label small fw-bold text-accent">회원 검색</label>
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                <input type="text" name="search" class="form-control form-control-sm border-start-0 ps-0"
                       placeholder="이름 또는 전화번호" value="{{keyword}}">
            </div>
        </div>
        <div class="col-md-4 text-end">
            <button type="submit" class="btn btn-sm btn-accent px-3">검색</button>
            <a href="index.php?route=member/list" class="btn btn-sm btn-outline-secondary px-3">초기화</a>
        </div>
    </form>
</div>

<!-- 회원 목록 -->
<div class="card border-0 shadow-sm overflow-hidden">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-muted">
                    <tr>
                        <th class="ps-4">이름</th>
                        <th>전화번호</th>
                        <th>성별</th>
                        <th>수강반</th>
                        <th>담당강사</th>
                        <th>상태</th>
                        <th class="text-end pe-4">등록일</th>
                    </tr>
                </thead>
                <tbody>
                {{#each members}}
                    <tr style="cursor:pointer;" onclick="location.href=\'index.php?route=member/detail&id={{id}}\'">
                        <td class="ps-4 fw-bold text-dark">{{name}}</td>
                        <td><small class="text-muted">{{phone}}</small></td>
                        <td>{{gender}}</td>
                        <td><span class="badge bg-light text-dark border fw-normal">{{class_name}}</span></td>
                        <td><small class="fw-semibold text-secondary">{{instructor_name}}</small></td>
                        <td>{{status}}</td>
                        <td class="text-end pe-4 text-muted small">{{created_at}}</td>
                    </tr>
                {{/each}}
                </tbody>
            </table>
        </div>
    </div>
</div>

{{{pagination}}}',

'member/detail' => '<!-- 회원 요약 헤더 -->
<div class="card border-0 shadow-sm mb-4 overflow-hidden">
    <div class="bg-accent p-1"></div>
    <div class="card-body p-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="d-flex align-items-center">
                <div class="rounded-circle bg-accent-subtle d-flex align-items-center justify-content-center text-accent me-4 shadow-sm" style="width:64px;height:64px;">
                    <i class="bi bi-person-fill fs-2"></i>
                </div>
                <div>
                    <h4 class="mb-1 fw-bold text-dark">
                        {{name}} <span class="badge bg-secondary ms-2">{{gender}}</span> <span class="badge bg-accent ms-1">{{status}}</span>
                    </h4>
                    <div class="d-flex flex-wrap gap-3 text-muted small fw-semibold">
                        {{#if phone}}<span><i class="bi bi-telephone me-1"></i> {{phone}}</span>{{/if}}
                        {{#if birth_date}}<span><i class="bi bi-calendar me-1"></i> {{birth_date}}</span>{{/if}}
                        {{#if height}}<span><i class="bi bi-rulers me-1"></i> {{height}}cm</span>{{/if}}
                        {{#if weight}}<span><i class="bi bi-speedometer me-1"></i> {{weight}}kg</span>{{/if}}
                    </div>
                </div>
            </div>
            <div class="d-flex gap-2">
                <a href="index.php?route=member/edit&id={{id}}" class="btn btn-sm btn-outline-accent px-3">
                    <i class="bi bi-pencil me-1"></i> 정보 수정
                </a>
                <a href="index.php?route=member/list" class="btn btn-sm btn-light border px-3">목록</a>
            </div>
        </div>

        {{#if class_name}}
        <div class="mt-3 pt-3 border-top d-flex gap-4">
            <div class="small">
                <span class="text-muted">소속반:</span>
                <span class="ms-1 fw-bold text-dark">{{class_name}}</span>
            </div>
            {{#if instructor_name}}
            <div class="small">
                <span class="text-muted">담당강사:</span>
                <span class="ms-1 fw-bold text-dark">{{instructor_name}}</span>
            </div>
            {{/if}}
        </div>
        {{/if}}

        {{#if memo}}
        <div class="mt-3 pt-3 border-top">
            <div class="small text-muted">메모</div>
            <div class="bg-light p-2 rounded small mt-1">{{memo}}</div>
        </div>
        {{/if}}
    </div>
</div>',

'member/form' => '<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="POST">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">이름 <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="{{name}}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">전화번호</label>
                    <input type="text" name="phone" class="form-control" value="{{phone}}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">성별</label>
                    <select name="gender" class="form-select">
                        <option value="">선택</option>
                        <option value="M">남</option>
                        <option value="F">여</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">생년월일</label>
                    <input type="date" name="birth_date" class="form-control" value="{{birth_date}}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">키 (cm)</label>
                    <input type="number" name="height" class="form-control" step="0.1" value="{{height}}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">몸무게 (kg)</label>
                    <input type="number" name="weight" class="form-control" step="0.1" value="{{weight}}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">상태</label>
                    <select name="status" class="form-select">
                        <option value="ACTIVE">수강</option>
                        <option value="PAUSED">휴원</option>
                        <option value="HONORARY">명예</option>
                        <option value="WITHDRAWN">퇴원</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">메모</label>
                    <textarea name="memo" class="form-control" rows="2">{{memo}}</textarea>
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-accent"><i class="bi bi-check-lg"></i> 저장</button>
                <a href="index.php?route=member/list" class="btn btn-outline-secondary">취소</a>
            </div>
        </form>
    </div>
</div>',

        ];

        return isset($templates[$viewPath]) ? $templates[$viewPath] : '';
    }

    /**
     * 미리보기용 샘플 데이터 반환
     */
    public static function getSampleData($viewPath)
    {
        $samples = [
            'dashboard/index' => [
                'pageTitle'       => '대시보드',
                'memberStats'     => ['total' => 128, 'ACTIVE' => 95, 'PAUSED' => 20, 'WITHDRAWN' => 13],
                'todayCounts'     => ['total' => 12, 'posture' => 7, 'foot' => 5],
                'consultationCnt' => 34,
                'recentPosture'   => [
                    ['id' => 1, 'member_name' => '김민수', 'status' => 'ACTIVE', 'captured_at' => date('Y-m-d H:i:s')],
                    ['id' => 2, 'member_name' => '이지은', 'status' => 'ACTIVE', 'captured_at' => date('Y-m-d H:i:s', strtotime('-1 hour'))],
                    ['id' => 3, 'member_name' => '박서준', 'status' => 'PAUSED', 'captured_at' => date('Y-m-d H:i:s', strtotime('-3 hour'))],
                ],
                'recentFoot'      => [
                    ['id' => 1, 'member_name' => '최유리', 'status' => 'ACTIVE', 'captured_at' => date('Y-m-d H:i:s')],
                    ['id' => 2, 'member_name' => '정하늘', 'status' => 'ACTIVE', 'captured_at' => date('Y-m-d H:i:s', strtotime('-2 hour'))],
                ],
            ],
            'member/list' => [
                'pageTitle'  => '회원 관리',
                'keyword'    => '',
                'pagination' => '<nav><ul class="pagination"><li class="page-item active"><a class="page-link">1</a></li><li class="page-item"><a class="page-link">2</a></li></ul></nav>',
                'members'    => [
                    ['id' => 1, 'name' => '김민수', 'phone' => '010-1234-5678', 'status' => 'ACTIVE', 'gender' => 'M', 'created_at' => '2025-01-15'],
                    ['id' => 2, 'name' => '이지은', 'phone' => '010-9876-5432', 'status' => 'ACTIVE', 'gender' => 'F', 'created_at' => '2025-02-20'],
                    ['id' => 3, 'name' => '박서준', 'phone' => '010-5555-1234', 'status' => 'PAUSED', 'gender' => 'M', 'created_at' => '2025-03-10'],
                ],
            ],
            'member/detail' => [
                'pageTitle' => '회원 상세',
                'id' => 1, 'name' => '김민수', 'phone' => '010-1234-5678',
                'email' => 'minsu@example.com', 'gender' => 'M', 'birth_date' => '1990-05-15',
                'status' => 'ACTIVE', 'height' => 175.5, 'weight' => 72.0,
                'class_name' => 'Class 1', 'instructor_name' => '박강사',
                'memo' => '샘플 메모입니다.', 'created_at' => '2025-01-15',
            ],
            'member/form' => [
                'pageTitle' => '회원 등록',
                'isEdit'    => false,
                'name' => '', 'phone' => '', 'email' => '', 'gender' => '',
                'birth_date' => '', 'status' => 'ACTIVE', 'height' => '', 'weight' => '', 'memo' => '',
            ],
            'layout/sidebar' => [
                'appName'        => APP_NAME,
                'appVersion'     => APP_VERSION,
                'logoUrl'        => '',
                'siteTitle'      => APP_NAME,
                'currentRoute'   => 'dashboard',
                'newNoticeCount' => 3,
            ],
            'layout/topbar' => [
                'pageTitle'    => '대시보드',
                'serviceType'  => '자세+족부',
                'adminName'    => '관리자',
                'adminLoginId' => 'admin',
                'adminRole'    => 'ADMIN',
            ],
        ];

        return isset($samples[$viewPath]) ? $samples[$viewPath] : ['pageTitle' => '미리보기'];
    }

    /**
     * 모든 설정 반환 (관리 UI용)
     */
    public static function getAllSettings()
    {
        return self::$settings ?? [];
    }

    /**
     * 초기화 상태 확인
     */
    public static function isInitialized()
    {
        return self::$initialized;
    }
}
