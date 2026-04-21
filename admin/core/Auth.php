<?php
/**
 * 세션 기반 인증 + CSRF 토큰
 * central_admin + partner_admin 통합 로그인 지원
 */
class Auth
{
    /**
     * 로그인 시도 (중앙관리자 → 협력업체 순으로 조회)
     */
    public static function attempt($loginId, $password)
    {
        $db = Database::getInstance();
        $hashed = hash(HASH_ALGO, $password);

        // 1) 중앙관리자 테이블 조회
        $stmt = $db->prepare('SELECT * FROM central_admin WHERE login_id = ? AND is_active = 1 LIMIT 1');
        $stmt->execute([$loginId]);
        $admin = $stmt->fetch();

        if ($admin && hash_equals($admin['password'], $hashed)) {
            session_regenerate_id(true);
            $_SESSION['admin'] = [
                'id'           => (int)$admin['id'],
                'login_id'     => $admin['login_id'],
                'name'         => $admin['name'],
                'email'        => $admin['email'],
                'role'         => $admin['role'],
                'account_type' => 'central',
                'partner_id'   => null,
            ];
            $db->prepare('UPDATE central_admin SET last_login_at = NOW() WHERE id = ?')->execute([$admin['id']]);
            return true;
        }

        // 2) 협력업체 관리자 테이블 조회
        $stmt = $db->prepare(
            'SELECT pa.*, p.company_name AS partner_name, p.status AS partner_status
             FROM partner_admin pa
             JOIN partner p ON p.id = pa.partner_id
             WHERE pa.login_id = ? AND pa.is_active = 1 AND p.status = \'ACTIVE\'
             LIMIT 1'
        );
        $stmt->execute([$loginId]);
        $partnerAdmin = $stmt->fetch();

        if ($partnerAdmin && hash_equals($partnerAdmin['password'], $hashed)) {
            session_regenerate_id(true);
            $_SESSION['admin'] = [
                'id'           => (int)$partnerAdmin['id'],
                'login_id'     => $partnerAdmin['login_id'],
                'name'         => $partnerAdmin['name'],
                'email'        => $partnerAdmin['email'],
                'role'         => $partnerAdmin['role'],
                'account_type' => 'partner',
                'partner_id'   => (int)$partnerAdmin['partner_id'],
                'partner_name' => $partnerAdmin['partner_name'],
            ];
            $db->prepare('UPDATE partner_admin SET last_login_at = NOW() WHERE id = ?')->execute([$partnerAdmin['id']]);
            return true;
        }

        return false;
    }

    /**
     * 로그인 여부 확인
     */
    public static function check()
    {
        return isset($_SESSION['admin']);
    }

    /**
     * 현재 로그인 사용자 정보
     */
    public static function user()
    {
        return isset($_SESSION['admin']) ? $_SESSION['admin'] : null;
    }

    /**
     * 협력업체 계정인지 확인
     */
    public static function isPartner(): bool
    {
        $user = self::user();
        return $user && ($user['account_type'] ?? '') === 'partner';
    }

    /**
     * 중앙관리자 계정인지 확인
     */
    public static function isCentral(): bool
    {
        $user = self::user();
        return $user && ($user['account_type'] ?? 'central') === 'central';
    }

    /**
     * 협력업체의 열람 승인된 가맹점 ID 목록 반환
     * partner_access_request 에서 APPROVED + 현재 시각이 허용 기간 내인 것만
     */
    public static function getPartnerTenantIds(): array
    {
        $user = self::user();
        if (!$user || ($user['account_type'] ?? '') !== 'partner') {
            return [];
        }

        // 세션 캐시 (5분 TTL)
        if (isset($_SESSION['partner_tenant_ids'], $_SESSION['partner_tenant_ids_ts'])
            && (time() - $_SESSION['partner_tenant_ids_ts']) < 300) {
            return $_SESSION['partner_tenant_ids'];
        }

        $db = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT DISTINCT requested_tenant_id
             FROM partner_access_request
             WHERE partner_id = ?
               AND status = 'APPROVED'
               AND access_start <= NOW()
               AND access_end >= NOW()"
        );
        $stmt->execute([$user['partner_id']]);
        $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $_SESSION['partner_tenant_ids'] = $ids;
        $_SESSION['partner_tenant_ids_ts'] = time();
        return $ids;
    }

    /**
     * 특정 가맹점에 접근 가능한지 확인
     */
    public static function canAccessTenant(int $tenantId): bool
    {
        if (self::isCentral()) {
            return true; // 중앙관리자는 전체 접근 가능
        }
        return in_array($tenantId, self::getPartnerTenantIds());
    }

    /**
     * 로그아웃
     */
    public static function logout()
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $p['path'], $p['domain'], $p['secure'], $p['httponly']
            );
        }
        session_destroy();
    }

    /**
     * CSRF 토큰 생성
     */
    public static function generateCsrfToken()
    {
        if (!empty($_SESSION['csrf_token'])) {
            return $_SESSION['csrf_token'];
        }
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;
        return $token;
    }

    /**
     * CSRF 토큰 검증
     */
    public static function verifyCsrf($token)
    {
        if (empty($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }
        $valid = hash_equals($_SESSION['csrf_token'], $token);
        if ($valid) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $valid;
    }

    /**
     * 역할 체크 (중앙관리자 + 협력업체 역할 모두 지원)
     */
    public static function hasRole(array $allowed)
    {
        $user = self::user();
        if (!$user) {
            return false;
        }
        return in_array($user['role'], $allowed, true);
    }
}
