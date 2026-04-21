<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - ' : '' ?><?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="public/css/style.css" rel="stylesheet">
    <?php if (ThemeManager::hasCssOverrides()): ?>
    <style id="theme-overrides"><?= ThemeManager::cssVariables() ?></style>
    <?php endif; ?>
    <?php if (!empty($contentCss)): ?>
    <style id="view-scoped-css"><?= ThemeManager::sanitizeCss($contentCss) ?></style>
    <?php endif; ?>
    <?php $__fontUrl = ThemeManager::get('fonts', 'font_url', ''); if ($__fontUrl): ?>
    <link href="<?= htmlspecialchars($__fontUrl) ?>" rel="stylesheet">
    <?php endif; ?>
    <?php $__fontFamily = ThemeManager::get('fonts', 'font_family', ''); if ($__fontFamily): ?>
    <style>body { font-family: '<?= htmlspecialchars($__fontFamily) ?>', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }</style>
    <?php endif; ?>
</head>
<body data-layout="<?= htmlspecialchars(ThemeManager::layoutPreset()) ?>"
      data-sidebar="<?= htmlspecialchars(ThemeManager::sidebarPosition()) ?>"
      data-topbar="<?= htmlspecialchars(ThemeManager::topbarStyle()) ?>">
<div class="d-flex <?= ThemeManager::sidebarPosition() === 'right' ? 'flex-row-reverse' : '' ?>">
    <?php include BASE_PATH . '/views/layout/sidebar.php'; ?>
    <div class="flex-grow-1 d-flex flex-column" style="min-height:100vh;">
        <?php include BASE_PATH . '/views/layout/topbar.php'; ?>
        <main class="flex-grow-1 p-4" style="background:var(--body-bg, #f8fafc);">
            <?php include BASE_PATH . '/views/components/alert.php'; ?>
            <?php if (isset($contentHtml) && $contentHtml !== null): ?>
                <?= $contentHtml ?>
            <?php elseif (isset($contentView) && file_exists($contentView)): ?>
                <?php include $contentView; ?>
            <?php endif; ?>
        </main>
        <footer class="bg-white border-top text-center text-muted py-2">
            <small>&copy; <?= date('Y') ?> <?= APP_NAME ?></small>
        </footer>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="public/js/app.js"></script>
<?php if (isset($aiEnabled) && $aiEnabled): ?>
<script src="public/js/ai-consultation.js"></script>
<?php endif; ?>
</body>
</html>
