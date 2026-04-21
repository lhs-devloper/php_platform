<?php
class AuthController extends Controller
{
    public function login()
    {
        // 이미 로그인 상태면 대시보드로
        if (Auth::check()) {
            $this->redirect('dashboard');
        }

        // POST: 로그인 처리
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCsrf();
            $loginId = $this->input('login_id');
            $password = $this->input('password');

            if (Auth::attempt($loginId, $password)) {
                $this->auditLog('LOGIN', 'central_admin', Auth::user()['id'], Auth::user()['name'] . ' 로그인');
                $this->flash('success', '로그인되었습니다.');
                $this->redirect('dashboard');
            } else {
                $this->flash('danger', '아이디 또는 비밀번호가 올바르지 않습니다.');
                $this->redirect('auth/login');
            }
        }

        // GET: 로그인 폼 표시
        $csrfToken = Auth::generateCsrfToken();
        $this->viewStandalone('auth/login', ['csrfToken' => $csrfToken]);
    }

    public function logout()
    {
        if (Auth::check()) {
            $this->auditLog('LOGOUT', 'central_admin', Auth::user()['id'], Auth::user()['name'] . ' 로그아웃');
        }
        Auth::logout();
        session_start();
        $this->flash('success', '로그아웃되었습니다.');
        $this->redirect('auth/login');
    }
}
