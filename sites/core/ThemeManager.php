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
        'member/list' => [
            'pageTitle'   => '페이지 제목 (string)',
            'members'     => '회원 목록 (array)',
            'pagination'  => '페이지네이션 HTML (string)',
            'keyword'     => '검색 키워드 (string)',
        ],
        'member/detail' => [
            'pageTitle' => '페이지 제목 (string)',
            'member'    => '회원 정보 (array)',
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
     * PHP 뷰 소스를 Mustache 템플릿으로 변환
     * 기본 소스 불러오기 시 PHP 코드를 노출하지 않고 Mustache 문법으로 변환
     */
    public static function convertPhpToMustache($source)
    {
        $open = '<' . '?php';   // PHP 열기 태그 (주석 파싱 방지)
        $close = '?' . '>';     // PHP 닫기 태그
        $echoTag = '<' . '?=';  // 에코 태그

        // 1. foreach → {{#each}}
        $source = preg_replace(
            '/' . preg_quote($open) . '\s+foreach\s*\(\s*\$(\w+)\s+as\s+\$\w+\s*\)\s*:\s*' . preg_quote($close) . '/i',
            '{{#each $1}}',
            $source
        );
        $source = preg_replace(
            '/' . preg_quote($open) . '\s+endforeach\s*;?\s*' . preg_quote($close) . '/i',
            '{{/each}}',
            $source
        );

        // 2. if → {{#if}}
        $source = preg_replace(
            '/' . preg_quote($open) . '\s+if\s*\(\s*!?\s*empty\s*\(\s*\$(\w+)\s*\)\s*\)\s*:\s*' . preg_quote($close) . '/i',
            '{{#if $1}}',
            $source
        );
        $source = preg_replace(
            '/' . preg_quote($open) . '\s+if\s*\(\s*isset\s*\(\s*\$(\w+)\s*\)\s*(?:&&[^:]+)?\)\s*:\s*' . preg_quote($close) . '/i',
            '{{#if $1}}',
            $source
        );
        $source = preg_replace(
            '/' . preg_quote($open) . '\s+if\s*\(\s*\$(\w+)\s*\)\s*:\s*' . preg_quote($close) . '/i',
            '{{#if $1}}',
            $source
        );
        $source = preg_replace(
            '/' . preg_quote($open) . '\s+if\s*\(\s*\$(\w+)\s*[><!]=?\s*[^:]+\)\s*:\s*' . preg_quote($close) . '/i',
            '{{#if $1}}',
            $source
        );
        // endif
        $source = preg_replace(
            '/' . preg_quote($open) . '\s+endif\s*;?\s*' . preg_quote($close) . '/i',
            '{{/if}}',
            $source
        );
        // else
        $source = preg_replace(
            '/' . preg_quote($open) . '\s+else\s*:\s*' . preg_quote($close) . '/i',
            '{{/if}}<!-- else -->',
            $source
        );

        // 3. htmlspecialchars($var['key']) → {{key}}
        $source = preg_replace(
            '/' . preg_quote($echoTag) . '\s*htmlspecialchars\s*\(\s*\$\w+\[[\'\"]([\w]+)[\'\"]\]\s*\)\s*' . preg_quote($close) . '/',
            '{{$1}}',
            $source
        );
        // htmlspecialchars($var) → {{var}}
        $source = preg_replace(
            '/' . preg_quote($echoTag) . '\s*htmlspecialchars\s*\(\s*\$(\w+)\s*\)\s*' . preg_quote($close) . '/',
            '{{$1}}',
            $source
        );

        // 4. number_format($var['key']) → {{key}}
        $source = preg_replace(
            '/' . preg_quote($echoTag) . '\s*number_format\s*\(\s*\$\w+\[[\'\"]([\w]+)[\'\"]\]\s*\)\s*' . preg_quote($close) . '/',
            '{{$1}}',
            $source
        );

        // 5. $var['key'] → {{{key}}}
        $source = preg_replace(
            '/' . preg_quote($echoTag) . '\s*\$\w+\[[\'\"]([\w]+)[\'\"]\]\s*' . preg_quote($close) . '/',
            '{{{$1}}}',
            $source
        );

        // 6. $var → {{{var}}}
        $source = preg_replace(
            '/' . preg_quote($echoTag) . '\s*\$(\w+)\s*' . preg_quote($close) . '/',
            '{{{$1}}}',
            $source
        );

        // 7. function($var...) → {{{var}}}
        $source = preg_replace(
            '/' . preg_quote($echoTag) . '\s*\w+\s*\(\s*\$(\w+)(?:\[[^\]]*\])?\s*\)\s*' . preg_quote($close) . '/',
            '{{{$1}}}',
            $source
        );

        // 8. substr 등 복합 호출 제거
        $source = preg_replace(
            '/' . preg_quote($echoTag) . '\s*[^?]*' . preg_quote($close) . '/',
            '',
            $source
        );

        // 9. 남은 PHP 블록 제거
        $source = preg_replace(
            '/' . preg_quote($open) . '\s+[^?]*' . preg_quote($close) . '\s*\n?/',
            '',
            $source
        );

        // 10. 빈 줄 정리
        $source = preg_replace('/\n{3,}/', "\n\n", $source);

        return trim($source);
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
                'member'    => [
                    'id' => 1, 'name' => '김민수', 'phone' => '010-1234-5678',
                    'email' => 'minsu@example.com', 'gender' => 'M', 'birth_date' => '1990-05-15',
                    'status' => 'ACTIVE', 'height' => 175.5, 'weight' => 72.0,
                    'note' => '샘플 메모입니다.', 'created_at' => '2025-01-15',
                ],
            ],
            'member/form' => [
                'pageTitle' => '회원 등록',
                'isEdit'    => false,
                'member'    => ['name' => '', 'phone' => '', 'email' => '', 'gender' => '', 'birth_date' => '', 'status' => 'ACTIVE'],
            ],
            'layout/sidebar' => [
                'currentRoute'  => 'dashboard',
                'newNoticeCount' => 3,
            ],
            'layout/topbar' => [
                'pageTitle' => '대시보드',
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
