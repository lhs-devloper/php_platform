<?php
/**
 * 세션 기반 인증 - TotalApp.admin 테이블
 */
class Auth
{
    public static function attempt($loginId, $password)
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT * FROM admin WHERE login_id = ? AND is_active = 1 LIMIT 1');
        $stmt->execute([$loginId]);
        $admin = $stmt->fetch();

        if (!$admin) return false;

        if (!hash_equals($admin['password'], hash(HASH_ALGO, $password))) {
            return false;
        }

        session_regenerate_id(true);
        $_SESSION['admin'] = [
            'id'           => (int)$admin['id'],
            'franchise_id' => (int)$admin['franchise_id'],
            'login_id'     => $admin['login_id'],
            'name'         => $admin['name'],
            'role'         => $admin['role'],
        ];

        $stmt = $db->prepare('UPDATE admin SET last_login_at = NOW() WHERE id = ?');
        $stmt->execute([$admin['id']]);
        return true;
    }

    public static function check()
    {
        return isset($_SESSION['admin']);
    }

    public static function user()
    {
        return isset($_SESSION['admin']) ? $_SESSION['admin'] : null;
    }

    public static function franchiseId()
    {
        $user = self::user();
        return $user ? $user['franchise_id'] : null;
    }

    public static function logout()
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    public static function generateCsrfToken()
    {
        if (!empty($_SESSION['csrf_token'])) {
            return $_SESSION['csrf_token'];
        }
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;
        return $token;
    }

    public static function verifyCsrf($token)
    {
        if (empty($_SESSION['csrf_token']) || empty($token)) return false;
        $valid = hash_equals($_SESSION['csrf_token'], $token);
        if ($valid) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $valid;
    }

    public static function hasRole(array $allowed)
    {
        $user = self::user();
        return $user ? in_array($user['role'], $allowed, true) : false;
    }
}
