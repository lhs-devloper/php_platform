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
}
