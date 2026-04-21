<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CentralAdmin - 설치</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="public/css/style.css" rel="stylesheet">
    <style>
        .install-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #1a233a 0%, #4e73df 100%);
        }
        .install-card {
            max-width: 640px;
            width: 100%;
            border: none;
            border-radius: 1rem;
            box-shadow: 0 1rem 3rem rgba(0,0,0,.25);
        }
        .install-header {
            background: var(--dark, #1a233a);
            color: #fff;
            border-radius: 1rem 1rem 0 0;
            padding: 2rem;
            text-align: center;
        }
        .install-header h2 { margin: 0; font-weight: 700; }
        .install-header p { margin: 0.5rem 0 0; opacity: 0.8; font-size: 0.9rem; }
        .status-item {
            display: flex;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid #eee;
        }
        .status-item:last-child { border-bottom: none; }
        .status-icon {
            width: 2rem;
            height: 2rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            flex-shrink: 0;
            font-size: 0.9rem;
        }
        .status-ok { background: #d1fae5; color: #059669; }
        .status-fail { background: #fee2e2; color: #dc2626; }
        .status-warn { background: #fef3c7; color: #d97706; }
    </style>
</head>
<body>
<div class="install-container">
    <div class="card install-card">
        <div class="install-header">
            <h2><i class="bi bi-database-gear"></i> CentralAdmin</h2>
            <p>데이터베이스 설치 및 설정</p>
        </div>
        <div class="card-body p-4">

            <?php if (!empty($installError)): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i>
                    <?= htmlspecialchars($installError) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($installSuccess)): ?>
                <div class="alert alert-success">
                    <i class="bi bi-check-circle-fill me-1"></i>
                    <?= htmlspecialchars($installSuccess) ?>
                </div>
                <div class="text-center mt-3">
                    <a href="index.php" class="btn btn-primary btn-lg">
                        <i class="bi bi-box-arrow-in-right me-1"></i> 관리자 페이지로 이동
                    </a>
                </div>
            <?php else: ?>

                <!-- 현재 상태 표시 -->
                <h6 class="fw-bold mb-3"><i class="bi bi-clipboard-check me-1"></i> 현재 상태</h6>
                <div class="mb-4">
                    <div class="status-item">
                        <div class="status-icon <?= $checkResult['server_ok'] ? 'status-ok' : 'status-fail' ?>">
                            <i class="bi <?= $checkResult['server_ok'] ? 'bi-check-lg' : 'bi-x-lg' ?>"></i>
                        </div>
                        <div>
                            <strong>MySQL 서버 연결</strong>
                            <div class="text-muted small"><?= htmlspecialchars(DB_HOST) ?>:<?= DB_PORT ?></div>
                        </div>
                    </div>
                    <div class="status-item">
                        <div class="status-icon <?= $checkResult['db_exists'] ? 'status-ok' : 'status-fail' ?>">
                            <i class="bi <?= $checkResult['db_exists'] ? 'bi-check-lg' : 'bi-x-lg' ?>"></i>
                        </div>
                        <div>
                            <strong>데이터베이스</strong>
                            <div class="text-muted small"><?= htmlspecialchars(DB_NAME) ?></div>
                        </div>
                    </div>
                    <div class="status-item">
                        <div class="status-icon <?= $checkResult['tables_ok'] ? 'status-ok' : ($checkResult['db_exists'] ? 'status-fail' : 'status-warn') ?>">
                            <i class="bi <?= $checkResult['tables_ok'] ? 'bi-check-lg' : 'bi-x-lg' ?>"></i>
                        </div>
                        <div>
                            <strong>테이블 구조</strong>
                            <?php if (!empty($checkResult['missing_tables'])): ?>
                                <div class="text-danger small">누락: <?= htmlspecialchars(implode(', ', $checkResult['missing_tables'])) ?></div>
                            <?php elseif (!$checkResult['db_exists']): ?>
                                <div class="text-muted small">DB가 없어 확인할 수 없습니다</div>
                            <?php else: ?>
                                <div class="text-muted small">18개 테이블 정상</div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="status-item">
                        <div class="status-icon <?= $checkResult['seed_ok'] ? 'status-ok' : 'status-warn' ?>">
                            <i class="bi <?= $checkResult['seed_ok'] ? 'bi-check-lg' : 'bi-x-lg' ?>"></i>
                        </div>
                        <div>
                            <strong>초기 데이터</strong>
                            <div class="text-muted small">관리자 계정, 서비스/요금제 마스터</div>
                        </div>
                    </div>
                </div>

                <!-- 설치 폼 -->
                <hr>
                <h6 class="fw-bold mb-3"><i class="bi bi-gear me-1"></i> DB 접속 정보</h6>
                <form method="post" action="index.php?route=install">
                    <input type="hidden" name="action" value="install">
                    <div class="row g-3">
                        <div class="col-8">
                            <label class="form-label">호스트</label>
                            <input type="text" name="db_host" class="form-control" value="<?= htmlspecialchars(DB_HOST) ?>" required>
                        </div>
                        <div class="col-4">
                            <label class="form-label">포트</label>
                            <input type="number" name="db_port" class="form-control" value="<?= DB_PORT ?>" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">데이터베이스명</label>
                            <input type="text" name="db_name" class="form-control" value="<?= htmlspecialchars(DB_NAME) ?>" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">사용자</label>
                            <input type="text" name="db_user" class="form-control" value="<?= htmlspecialchars(DB_USER) ?>" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">비밀번호</label>
                            <input type="password" name="db_pass" class="form-control" value="<?= htmlspecialchars(DB_PASS) ?>">
                        </div>
                    </div>

                    <div class="alert alert-info mt-3 mb-3">
                        <i class="bi bi-info-circle me-1"></i>
                        <strong>CentralAdmin.sql</strong> 파일을 사용하여 데이터베이스와 테이블을 자동 생성합니다.
                        <br><small class="text-muted">경로: <?= htmlspecialchars(realpath(__DIR__ . '/../../CentralAdmin.sql') ?: dirname(__DIR__, 2) . '/CentralAdmin.sql') ?></small>
                    </div>

                    <div class="d-grid gap-2 mt-3">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-download me-1"></i> 데이터베이스 설치
                        </button>
                    </div>

                    <div class="text-center mt-3">
                        <small class="text-muted">
                            설치 후 기본 관리자: <code>superadmin</code> / <code>changeme</code>
                        </small>
                    </div>
                </form>

            <?php endif; ?>

        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
