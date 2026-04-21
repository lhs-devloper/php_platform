<?php
/**
 * 베이스 컨트롤러
 * 뷰 렌더링, 리다이렉트, 플래시 메시지, CSRF 검증, 감사 로그
 */
class Controller
{
    protected $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * 뷰 렌더링 (master.php 레이아웃 포함)
     */
    protected function view($viewPath, array $data = [])
    {
        extract($data);
        $contentView = BASE_PATH . '/views/' . $viewPath . '.php';

        if (!file_exists($contentView)) {
            http_response_code(500);
            echo "<h1>View not found: {$viewPath}</h1>";
            return;
        }

        include BASE_PATH . '/views/layout/master.php';
    }

    /**
     * 레이아웃 없이 뷰 렌더링 (로그인 페이지 등)
     */
    protected function viewStandalone($viewPath, array $data = [])
    {
        extract($data);
        include BASE_PATH . '/views/' . $viewPath . '.php';
    }

    /**
     * 리다이렉트
     */
    protected function redirect($route, array $params = [])
    {
        $url = 'index.php?route=' . $route;
        foreach ($params as $key => $val) {
            $url .= '&' . urlencode($key) . '=' . urlencode($val);
        }
        header('Location: ' . $url);
        exit;
    }

    /**
     * 플래시 메시지 설정
     */
    protected function flash($type, $message)
    {
        $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    }

    /**
     * 인증 필수 체크
     */
    protected function requireAuth()
    {
        if (!Auth::check()) {
            $this->redirect('auth/login');
        }
    }

    /**
     * 역할 체크
     */
    protected function requireRole(array $roles)
    {
        if (!Auth::hasRole($roles)) {
            http_response_code(403);
            echo '<h1>403 Forbidden</h1><p>권한이 없습니다.</p>';
            exit;
        }
    }

    /**
     * CSRF 검증
     */
    protected function validateCsrf()
    {
        $token = isset($_POST['_csrf']) ? $_POST['_csrf'] : '';
        if (!Auth::verifyCsrf($token)) {
            $this->flash('danger', 'CSRF 토큰이 유효하지 않습니다. 다시 시도해주세요.');
            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit;
        }
    }

    /**
     * 감사 로그 기록
     */
    protected function auditLog($action, $targetType, $targetId = null, $description = '', $before = null, $after = null, $tenantId = null)
    {
        $user = Auth::user();
        $adminId = $user ? $user['id'] : null;

        $stmt = $this->db->prepare(
            'INSERT INTO audit_log (admin_id, tenant_id, action, target_type, target_id, description, before_data, after_data, ip_address, user_agent, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())'
        );
        $stmt->execute([
            $adminId,
            $tenantId,
            $action,
            $targetType,
            $targetId,
            $description,
            $before !== null ? json_encode($before, JSON_UNESCAPED_UNICODE) : null,
            $after !== null ? json_encode($after, JSON_UNESCAPED_UNICODE) : null,
            isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null,
            isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 500) : null,
        ]);
    }

    /**
     * POST 입력값 가져오기
     */
    protected function input($key, $default = null)
    {
        if (isset($_POST[$key])) {
            return trim($_POST[$key]);
        }
        if (isset($_GET[$key])) {
            return trim($_GET[$key]);
        }
        return $default;
    }
}
