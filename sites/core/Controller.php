<?php
/**
 * 베이스 컨트롤러 (가맹점 사이트)
 */
class Controller
{
    protected $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    protected function view($viewPath, array $data = [])
    {
        // extract 전에 내부 변수 보호 (data에 viewPath 등이 있으면 덮어쓰기 방지)
        $__viewPath = $viewPath;
        $__data = $data;
        extract($data);

        // Level 3: DB 템플릿 오버라이드 확인
        $override = ThemeManager::resolveView($__viewPath);
        if ($override) {
            try {
                $contentView = null;
                // 중첩 배열(member, tenant 등)을 플랫 전개하여 {{key}} 접근 가능하게
                $__flatData = $__data;
                foreach ($__data as $k => $v) {
                    if (is_array($v) && !isset($v[0])) {
                        $__flatData = array_merge($__flatData, $v);
                    }
                }
                $contentHtml = ThemeManager::renderTemplate($override['html_content'], $__flatData);
                $contentCss  = $override['css_content'] ?? '';
            } catch (Exception $e) {
                // 렌더링 실패 시 기본 뷰로 폴백
                $contentView = BASE_PATH . '/views/' . $__viewPath . '.php';
                $contentHtml = null;
                $contentCss  = '';
                error_log("ThemeManager: template render failed for [{$__viewPath}]: " . $e->getMessage());
            }
        } else {
            $contentView = BASE_PATH . '/views/' . $__viewPath . '.php';
            $contentHtml = null;
            $contentCss  = '';
        }

        if ($contentHtml === null && !file_exists($contentView)) {
            http_response_code(500);
            echo "<h1>View not found: {$__viewPath}</h1>";
            return;
        }

        // Level 2: 레이아웃 선택
        $masterLayout = ThemeManager::layoutFile();
        include $masterLayout;
    }

    protected function viewStandalone($viewPath, array $data = [])
    {
        extract($data);
        include BASE_PATH . '/views/' . $viewPath . '.php';
    }

    protected function redirect($route, array $params = [])
    {
        $url = 'index.php?route=' . $route;
        foreach ($params as $key => $val) {
            $url .= '&' . urlencode($key) . '=' . urlencode($val);
        }
        header('Location: ' . $url);
        exit;
    }

    protected function flash($type, $message)
    {
        $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    }

    protected function requireAuth()
    {
        if (!Auth::check()) $this->redirect('auth/login');
    }

    protected function requireRole(array $roles)
    {
        if (!Auth::hasRole($roles)) {
            http_response_code(403);
            echo '<h1>403</h1><p>권한이 없습니다.</p>';
            exit;
        }
    }

    protected function validateCsrf()
    {
        $token = isset($_POST['_csrf']) ? $_POST['_csrf'] : '';
        if (!Auth::verifyCsrf($token)) {
            $this->flash('danger', 'CSRF 토큰이 유효하지 않습니다.');
            header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
            exit;
        }
    }

    protected function input($key, $default = null)
    {
        if (isset($_POST[$key])) return trim($_POST[$key]);
        if (isset($_GET[$key])) return trim($_GET[$key]);
        return $default;
    }

    /**
     * AJAX 요청용 CSRF 검증 (X-CSRF-Token 헤더)
     */
    protected function validateAjaxCsrf()
    {
        $token = isset($_SERVER['HTTP_X_CSRF_TOKEN']) ? $_SERVER['HTTP_X_CSRF_TOKEN'] : '';
        if (!Auth::verifyCsrf($token)) {
            $this->json(['success' => false, 'message' => 'CSRF 토큰이 유효하지 않습니다.'], 403);
        }
    }

    /**
     * JSON 응답 헬퍼
     */
    protected function json(array $data, int $status = 200)
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
