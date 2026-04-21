<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - ' : '' ?><?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="public/css/style.css" rel="stylesheet">
</head>
<body class="overflow-hidden" style="height:100vh;">
<div class="d-flex" style="height:100vh;">
    <?php include BASE_PATH . '/views/layout/sidebar.php'; ?>

    <div class="flex-grow-1 d-flex flex-column" style="height:100vh; overflow:hidden;">
        <?php include BASE_PATH . '/views/layout/topbar.php'; ?>

        <main class="flex-grow-1 p-4 bg-light" style="overflow-y:auto;">
            <?php include BASE_PATH . '/views/components/alert.php'; ?>
            <?php
            if (isset($contentView) && file_exists($contentView)) {
                include $contentView;
            }
            ?>

            <footer class="text-center text-muted py-3 mt-4">
                <small>&copy; <?= date('Y') ?> <?= APP_NAME ?></small>
            </footer>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="public/js/app.js"></script>
</body>
</html>
