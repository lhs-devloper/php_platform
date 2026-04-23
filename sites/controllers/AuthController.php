<?php
class AuthController extends Controller
{
    public function login()
    {
        if (Auth::check()) $this->redirect('dashboard');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCsrf();
            if (Auth::attempt($this->input('login_id'), $this->input('password'))) {
                $this->flash('success', '로그인되었습니다.');
                $this->redirect('dashboard');
            } else {
                $this->flash('danger', '아이디 또는 비밀번호가 올바르지 않습니다.');
                $this->redirect('auth/login');
            }
        }

        $this->viewStandalone('auth/login', ['csrfToken' => Auth::generateCsrfToken()]);
    }

    /**
     * 중앙 관리자 토큰으로 자동 로그인
     */
    public function adminAccess()
    {
        $token = isset($_GET['token']) ? $_GET['token'] : '';
        $parts = explode('.', $token);

        if (count($parts) !== 2) {
            $this->flash('danger', '잘못된 접근입니다.');
            $this->redirect('auth/login');
            return;
        }

        list($payload, $signature) = $parts;

        // HMAC 검증
        $expected = hash_hmac('sha256', $payload, ADMIN_ACCESS_SECRET);
        if (!hash_equals($expected, $signature)) {
            $this->flash('danger', '인증 토큰이 유효하지 않습니다.');
            $this->redirect('auth/login');
            return;
        }

        $data = json_decode(base64_decode($payload), true);
        if (!$data || !isset($data['ts'])) {
            $this->flash('danger', '토큰 데이터가 올바르지 않습니다.');
            $this->redirect('auth/login');
            return;
        }

        // 60초 유효기간 체크
        if (abs(time() - $data['ts']) > 60) {
            $this->flash('danger', '토큰이 만료되었습니다. 다시 시도해주세요.');
            $this->redirect('auth/login');
            return;
        }

        // 테넌트 DB에서 첫 번째 활성 관리자로 로그인
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT * FROM admin WHERE is_active = 1 ORDER BY id ASC LIMIT 1');
        $stmt->execute();
        $admin = $stmt->fetch();

        if (!$admin) {
            $this->flash('danger', '가맹점에 등록된 관리자가 없습니다.');
            $this->redirect('auth/login');
            return;
        }

        // 세션 생성
        session_regenerate_id(true);
        $_SESSION['admin'] = [
            'id'           => (int)$admin['id'],
            'franchise_id' => (int)$admin['franchise_id'],
            'login_id'     => $admin['login_id'],
            'name'         => $admin['name'],
            'role'         => $admin['role'],
        ];
        $_SESSION['is_central_admin'] = true;
        $_SESSION['central_admin_name'] = $data['admin_name'];

        $stmt = $db->prepare('UPDATE admin SET last_login_at = NOW() WHERE id = ?');
        $stmt->execute([$admin['id']]);

        $this->redirect('dashboard');
    }

    public function logout()
    {
        Auth::logout();
        session_name('TENANT_' . strtoupper($GLOBALS['subdomain']));
        session_start();
        $this->flash('success', '로그아웃되었습니다.');
        $this->redirect('auth/login');
    }

    /**
     * 내 계정 설정 페이지
     * - 웹 관리자 계정 (TotalApp.admin): ID 고정, 비밀번호 변경 가능
     * - 앱 계정 (CentralAdmin.tenant): ID 고정(조회만), 비밀번호 변경 가능
     */
    public function profile()
    {
        $this->requireAuth();

        $user = Auth::user();
        $stmt = $this->db->prepare('SELECT id, login_id, name, role, last_login_at FROM admin WHERE id = ? LIMIT 1');
        $stmt->execute([$user['id']]);
        $admin = $stmt->fetch();

        // CentralAdmin에서 앱 계정 ID 조회 (조회 전용)
        $appId = $this->fetchAppId();

        $this->view('auth/profile', [
            'pageTitle' => '내 계정 설정',
            'admin'     => $admin,
            'appId'     => $appId,
            'csrfToken' => Auth::generateCsrfToken(),
        ]);
    }

    /**
     * 웹 관리자 비밀번호 변경 (TotalApp.admin.password)
     */
    public function updatePassword()
    {
        $this->requireAuth();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('auth/profile');
            return;
        }
        $this->validateCsrf();

        $user = Auth::user();
        $currentPassword = (string)$this->input('current_password', '');
        $newPassword     = (string)$this->input('new_password', '');
        $confirmPassword = (string)$this->input('confirm_password', '');

        if (strlen($newPassword) < 6) {
            $this->flash('danger', '새 비밀번호는 6자 이상이어야 합니다.');
            $this->redirect('auth/profile');
            return;
        }
        if ($newPassword !== $confirmPassword) {
            $this->flash('danger', '새 비밀번호와 확인 값이 일치하지 않습니다.');
            $this->redirect('auth/profile');
            return;
        }

        $stmt = $this->db->prepare('SELECT password FROM admin WHERE id = ? LIMIT 1');
        $stmt->execute([$user['id']]);
        $row = $stmt->fetch();
        if (!$row || !hash_equals($row['password'], hash(HASH_ALGO, $currentPassword))) {
            $this->flash('danger', '현재 비밀번호가 올바르지 않습니다.');
            $this->redirect('auth/profile');
            return;
        }

        $stmt = $this->db->prepare('UPDATE admin SET password = ? WHERE id = ?');
        $stmt->execute([hash(HASH_ALGO, $newPassword), $user['id']]);

        $this->flash('success', '비밀번호가 변경되었습니다.');
        $this->redirect('auth/profile');
    }

    /**
     * 앱 계정 비밀번호 변경 (CentralAdmin.tenant.app_pw)
     * 웹 관리자 현재 비밀번호로 본인 확인 후 업데이트
     */
    public function updateAppPassword()
    {
        $this->requireAuth();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('auth/profile');
            return;
        }
        $this->validateCsrf();

        $user = Auth::user();
        $currentPassword = (string)$this->input('current_password', '');
        $newAppPassword  = (string)$this->input('new_app_password', '');
        $confirmPassword = (string)$this->input('confirm_app_password', '');

        if (strlen($newAppPassword) < 6) {
            $this->flash('danger', '새 앱 비밀번호는 6자 이상이어야 합니다.');
            $this->redirect('auth/profile');
            return;
        }
        if ($newAppPassword !== $confirmPassword) {
            $this->flash('danger', '새 앱 비밀번호와 확인 값이 일치하지 않습니다.');
            $this->redirect('auth/profile');
            return;
        }

        // 본인 확인: 웹 관리자 현재 비밀번호
        $stmt = $this->db->prepare('SELECT password FROM admin WHERE id = ? LIMIT 1');
        $stmt->execute([$user['id']]);
        $row = $stmt->fetch();
        if (!$row || !hash_equals($row['password'], hash(HASH_ALGO, $currentPassword))) {
            $this->flash('danger', '현재 비밀번호가 올바르지 않습니다.');
            $this->redirect('auth/profile');
            return;
        }

        $tenantId = isset($GLOBALS['tenant_db_info']['tenant_id']) ? (int)$GLOBALS['tenant_db_info']['tenant_id'] : 0;
        if ($tenantId <= 0) {
            $this->flash('danger', '테넌트 정보를 확인할 수 없습니다.');
            $this->redirect('auth/profile');
            return;
        }

        try {
            $central = $this->centralDb();
            $stmt = $central->prepare('UPDATE tenant SET app_pw = ? WHERE id = ?');
            $stmt->execute([Crypto::encrypt($newAppPassword), $tenantId]);
            $this->flash('success', '앱 비밀번호가 변경되었습니다.');
        } catch (Exception $e) {
            $this->flash('danger', '앱 비밀번호 변경 실패: ' . $e->getMessage());
        }

        $this->redirect('auth/profile');
    }

    /**
     * 현재 테넌트의 앱 로그인 ID를 CentralAdmin에서 조회
     */
    private function fetchAppId(): ?string
    {
        $tenantId = isset($GLOBALS['tenant_db_info']['tenant_id']) ? (int)$GLOBALS['tenant_db_info']['tenant_id'] : 0;
        if ($tenantId <= 0) return null;
        try {
            $stmt = $this->centralDb()->prepare('SELECT app_id FROM tenant WHERE id = ? LIMIT 1');
            $stmt->execute([$tenantId]);
            $row = $stmt->fetch();
            return $row ? ($row['app_id'] ?? null) : null;
        } catch (Exception $e) {
            return null;
        }
    }

    private function centralDb(): PDO
    {
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            CENTRAL_DB_HOST, CENTRAL_DB_PORT, CENTRAL_DB_NAME);
        return new PDO($dsn, CENTRAL_DB_USER, CENTRAL_DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
}
