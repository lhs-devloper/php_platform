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
            background-color: #f8f9fa;
            background-image: radial-gradient(#4e73df 0.5px, transparent 0.5px);
            background-size: 20px 20px;
            font-family: 'Pretendard', sans-serif;
            display: flex; 
            align-items: center; 
            justify-content: center; 
            min-height: 100vh; 
            margin: 0;
        }
        .login-card { 
            width: 100%; 
            max-width: 420px; 
            animation: fadeIn 0.5s ease-out;
        }
        .card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 1rem 3rem rgba(0,0,0,.1) !important;
        }
        .btn-primary {
            background-color: #4e73df;
            border-color: #4e73df;
            padding: 0.75rem;
            font-weight: 700;
            border-radius: 0.5rem;
        }
        .form-control {
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            background-color: #f8f9fc;
            border: 1px solid #d1d3e2;
        }
        .form-control:focus {
            background-color: #fff;
            border-color: #bac8f3;
            box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
        }
        .input-group-text {
            background-color: #f8f9fc;
            border: 1px solid #d1d3e2;
            border-radius: 0.5rem;
            color: #4e73df;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
<div class="login-card">
    <div class="text-center mb-4">
        <h2 class="fw-bold text-primary mb-0"><i class="bi bi-shield-lock-fill"></i> <?= APP_NAME ?></h2>
        <p class="text-muted small fw-bold mt-1 text-uppercase" style="letter-spacing: 0.1em;">Central Governance System</p>
    </div>

    <div class="card overflow-hidden">
        <div class="bg-primary p-1"></div>
        <div class="card-body p-5">
            <h5 class="text-dark fw-bold mb-4 text-center">Administrator Login</h5>

            <?php include BASE_PATH . '/views/components/alert.php'; ?>

            <form method="POST" action="index.php?route=auth/login">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>">

                <div class="mb-3">
                    <label for="login_id" class="form-label small fw-bold text-muted">ID</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                        <input type="text" class="form-control" id="login_id" name="login_id"
                               placeholder="Enter your ID" required autofocus>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="password" class="form-label small fw-bold text-muted">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-key"></i></span>
                        <input type="password" class="form-control" id="password" name="password"
                               placeholder="Enter your password" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 shadow-sm mt-2">
                    Sign In <i class="bi bi-arrow-right-short ms-1"></i>
                </button>
            </form>
        </div>
    </div>
    <div class="text-center mt-4">
        <small class="text-muted">
            &copy; <?= date('Y') ?> DNI Solution Co., Ltd.<br>
            <span class="opacity-50">Portal Version <?= APP_VERSION ?></span>
        </small>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
