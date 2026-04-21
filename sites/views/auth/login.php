<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>로그인 - <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        @import url('https://cdn.jsdelivr.net/gh/orioncactus/pretendard/dist/web/static/pretendard.css');
        body { 
            background-color: #f0fdfa;
            background-image: radial-gradient(#0d9488 0.5px, transparent 0.5px);
            background-size: 30px 30px;
            font-family: 'Pretendard', sans-serif;
            display: flex; 
            align-items: center; 
            justify-content: center; 
            min-height: 100vh; 
            margin: 0;
        }
        .login-card { 
            width: 100%; 
            max-width: 440px; 
            animation: fadeIn 0.6s ease-out;
        }
        .card {
            border: none;
            border-radius: 1.25rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04) !important;
        }
        .btn-accent {
            background-color: #0d9488;
            border-color: #0d9488;
            color: #fff;
            padding: 0.8rem;
            font-weight: 700;
            border-radius: 0.75rem;
            transition: all 0.2s;
        }
        .btn-accent:hover {
            background-color: #0f766e;
            transform: translateY(-1px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .form-control {
            padding: 0.8rem 1rem;
            border-radius: 0.75rem;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
        }
        .form-control:focus {
            background-color: #fff;
            border-color: #0d9488;
            box-shadow: 0 0 0 0.2rem rgba(13, 148, 136, 0.15);
        }
        .input-group-text {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            color: #0d9488;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
<div class="login-card p-3">
    <div class="text-center mb-5">
        <div class="d-inline-flex align-items-center justify-content-center bg-white rounded-circle shadow-sm mb-3" style="width: 64px; height: 64px;">
            <i class="bi bi-heart-pulse-fill text-accent fs-2"></i>
        </div>
        <h2 class="fw-bold text-dark mb-1"><?= htmlspecialchars(APP_NAME) ?></h2>
        <p class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.15em;">Member Management Portal</p>
    </div>

    <div class="card overflow-hidden">
        <div class="card-body p-4 p-md-5">
            <h5 class="text-dark fw-bold mb-4">로그인</h5>

            <?php include BASE_PATH . '/views/components/alert.php'; ?>

            <form method="POST" action="index.php?route=auth/login">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>">

                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted">아이디</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                        <input type="text" name="login_id" class="form-control" 
                               placeholder="아이디를 입력하세요" required autofocus>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label small fw-bold text-muted">비밀번호</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-shield-lock"></i></span>
                        <input type="password" name="password" class="form-control" 
                               placeholder="비밀번호를 입력하세요" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-accent w-100 shadow-sm mt-2">
                    시작하기 <i class="bi bi-arrow-right-short ms-1"></i>
                </button>
            </form>
        </div>
        <div class="bg-light p-3 text-center border-top">
            <small class="text-muted">접속 도메인: <span class="fw-bold text-accent"><?= htmlspecialchars($GLOBALS['subdomain'] . '.localhost') ?></span></small>
        </div>
    </div>
    
    <div class="text-center mt-5">
        <p class="text-muted small">
            &copy; <?= date('Y') ?> DNI Solution. All rights reserved.<br>
            <span class="opacity-50">System v<?= APP_VERSION ?></span>
        </p>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
