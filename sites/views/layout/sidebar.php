<?php
$currentRoute = isset($_GET['route']) ? $_GET['route'] : 'dashboard';
if (!function_exists('sideActive')) {
    function sideActive($current, $prefix) {
        return strpos($current, $prefix) === 0 ? 'active' : '';
    }
}
$newNoticeCount = 0;
try { $nm = new NoticeModel(); $newNoticeCount = $nm->getRecentCount(7); } catch (Exception $e) {}
?>
<nav id="sidebar" class="vh-100 p-3 d-flex flex-column" style="width:260px;min-width:260px;background:#1e293b;">
    <div class="px-3 py-4">
        <h5 class="text-white mb-0 fw-bold">
            <?php $__logoUrl = ThemeManager::get('branding', 'logo_url', ''); ?>
            <?php if ($__logoUrl): ?>
                <img src="<?= htmlspecialchars($__logoUrl) ?>" alt="Logo" style="max-height:32px;vertical-align:middle;">
            <?php else: ?>
                <i class="bi bi-heart-pulse-fill text-accent"></i>
            <?php endif; ?>
            <?= htmlspecialchars(ThemeManager::get('branding', 'site_title', '') ?: APP_NAME) ?>
        </h5>
        <div class="mt-2">
            <span class="sidebar-badge text-uppercase">FRANCHISE PORTAL v<?= APP_VERSION ?></span>
        </div>
    </div>

    <hr class="mx-3 opacity-10 text-white mt-0">

    <ul class="nav flex-column mb-auto">
        <li class="nav-item">
            <a class="nav-link text-white <?= sideActive($currentRoute, 'dashboard') ?>"
               href="index.php?route=dashboard">
                <i class="bi bi-speedometer2"></i> <span>대시보드</span>
            </a>
        </li>

        <li class="text-muted mt-3 mb-1 px-3 small text-uppercase fw-bold" style="letter-spacing: 0.05em; font-size: 0.7rem;">회원 서비스</li>
        <li class="nav-item">
            <a class="nav-link text-white <?= sideActive($currentRoute, 'member') ?>"
               href="index.php?route=member/list">
                <i class="bi bi-people"></i> <span>회원 관리</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link text-white <?= sideActive($currentRoute, 'class_code') ?>"
               href="index.php?route=class_code/list">
                <i class="bi bi-collection"></i> <span>수강반 관리</span>
            </a>
        </li>

        <li class="text-muted mt-3 mb-1 px-3 small text-uppercase fw-bold" style="letter-spacing: 0.05em; font-size: 0.7rem;">고객 지원</li>
        <li class="nav-item">
            <a class="nav-link text-white <?= sideActive($currentRoute, 'notice') ?>"
               href="index.php?route=notice/list">
                <i class="bi bi-megaphone"></i> 
                <span class="flex-grow-1">공지사항</span>
                <?php if ($newNoticeCount > 0): ?>
                    <span class="badge bg-danger rounded-pill"><?= $newNoticeCount ?></span>
                <?php endif; ?>
            </a>
        </li>

        <li class="text-muted mt-3 mb-1 px-3 small text-uppercase fw-bold" style="letter-spacing: 0.05em; font-size: 0.7rem;">안내</li>
        <li class="nav-item">
            <a class="nav-link text-white <?= sideActive($currentRoute, 'lab') ?>"
               href="index.php?route=lab">
                <i class="bi bi-calendar-check"></i>
                <span class="flex-grow-1">업데이트 예정사항</span>
                <span class="badge bg-info rounded-pill" style="font-size:0.6rem;">NEW</span>
            </a>
        </li>
    </ul>

    <?php if (Auth::check() && Auth::hasRole(['SUPER', 'ADMIN'])): ?>
    <ul class="nav flex-column mb-3">
        <li class="text-muted mt-3 mb-1 px-3 small text-uppercase fw-bold" style="letter-spacing: 0.05em; font-size: 0.7rem;">사이트 설정</li>
        <li class="nav-item">
            <a class="nav-link text-white <?= sideActive($currentRoute, 'theme') ?>"
               href="index.php?route=theme/settings">
                <i class="bi bi-palette"></i> <span>디자인 설정</span>
            </a>
        </li>
    </ul>
    <?php endif; ?>

    <div class="px-3 mb-2">
        <div class="card sidebar-support-card shadow-none mb-0">
            <div class="card-body p-3">
                <div class="support-label mb-1">Help & Support</div>
                <div class="support-mail">support@ai-sw.net</div>
            </div>
        </div>
    </div>
</nav>
