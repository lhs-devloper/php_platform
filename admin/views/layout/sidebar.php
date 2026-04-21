<?php
$currentRoute = isset($_GET['route']) ? $_GET['route'] : 'dashboard';
$isPartner = Auth::isPartner();
$pendingAccessCount = 0;

if (!$isPartner) {
    try {
        $stmt = Database::getInstance()->query("SELECT COUNT(*) FROM partner_access_request WHERE status = 'PENDING'");
        $pendingAccessCount = (int)$stmt->fetchColumn();
    } catch (Exception $e) {
    }
}

function sidebarActive($currentRoute, $prefix)
{
    return strpos($currentRoute, $prefix) === 0 ? 'active' : '';
}
?>
<nav id="sidebar" class="bg-dark text-white p-3" style="width:260px;min-width:260px;">
    <div class="px-3 py-4 text-center">
        <h4 class="text-white mb-0 fw-bold">
            <i class="bi bi-shield-lock-fill text-primary"></i> <?= APP_NAME ?>
        </h4>
        <div class="mt-1">
            <?php if ($isPartner): ?>
                <span class="badge bg-info-subtle text-info border border-info-subtle" style="font-size: 0.65rem;">PARTNER PORTAL</span>
            <?php else: ?>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size: 0.65rem;">ADMIN PORTAL v<?= APP_VERSION ?></span>
            <?php endif; ?>
        </div>
    </div>

    <hr class="mx-3 opacity-10">

    <?php if ($isPartner): ?>
    <!-- ========== 협력업체 메뉴 ========== -->
    <?php $partnerUser = Auth::user(); ?>
    <div class="px-3 mb-3">
        <div class="small text-white-50">소속</div>
        <div class="fw-bold text-white"><?= htmlspecialchars($partnerUser['partner_name'] ?? '') ?></div>
    </div>

    <ul class="nav flex-column mb-auto">
        <li class="nav-item">
            <a class="nav-link text-white <?= sidebarActive($currentRoute, 'dashboard') ?>"
                href="index.php?route=dashboard">
                <i class="bi bi-speedometer2"></i> <span>대시보드</span>
            </a>
        </li>

        <li class="text-muted mt-3 mb-1 px-3 small text-uppercase fw-bold" style="letter-spacing: 0.05em;">소속 가맹점</li>
        <li class="nav-item">
            <a class="nav-link text-white <?= sidebarActive($currentRoute, 'tenant') ?>"
                href="index.php?route=tenant/list">
                <i class="bi bi-building"></i> <span>가맹점 목록</span>
            </a>
        </li>

        <li class="text-muted mt-3 mb-1 px-3 small text-uppercase fw-bold" style="letter-spacing: 0.05em;">열람 관리</li>
        <li class="nav-item">
            <a class="nav-link text-white <?= sidebarActive($currentRoute, 'access_request') ?>"
                href="index.php?route=access_request/list">
                <i class="bi bi-shield-check"></i> <span>열람 요청</span>
            </a>
        </li>

        <li class="text-muted mt-3 mb-1 px-3 small text-uppercase fw-bold" style="letter-spacing: 0.05em;">정보</li>
        <li class="nav-item">
            <a class="nav-link text-white <?= sidebarActive($currentRoute, 'notice') ?>"
                href="index.php?route=notice/list">
                <i class="bi bi-megaphone"></i> <span>공지사항</span>
            </a>
        </li>
    </ul>

    <?php else: ?>
    <!-- ========== 중앙관리자 메뉴 ========== -->
    <ul class="nav flex-column mb-auto">
        <li class="nav-item">
            <a class="nav-link text-white <?= sidebarActive($currentRoute, 'dashboard') ?>"
                href="index.php?route=dashboard">
                <i class="bi bi-speedometer2"></i> <span>대시보드</span>
            </a>
        </li>

        <li class="text-muted mt-3 mb-1 px-3 small text-uppercase fw-bold" style="letter-spacing: 0.05em;">가맹점 관리</li>
        <li class="nav-item">
            <a class="nav-link text-white <?= sidebarActive($currentRoute, 'tenant') ?>"
                href="index.php?route=tenant/list">
                <i class="bi bi-building"></i> <span>가맹점 목록</span>
            </a>
        </li>

        <li class="text-muted mt-3 mb-1 px-3 small text-uppercase fw-bold" style="letter-spacing: 0.05em;">협력업체 관리</li>
        <li class="nav-item">
            <a class="nav-link text-white <?= sidebarActive($currentRoute, 'partner') ?>"
                href="index.php?route=partner/list">
                <i class="bi bi-people"></i> <span>협력업체 목록</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link text-white <?= sidebarActive($currentRoute, 'access_request') ?>"
                href="index.php?route=access_request/list">
                <i class="bi bi-shield-check"></i>
                <span class="flex-grow-1">열람 요청</span>
                <?php if ($pendingAccessCount > 0): ?>
                    <span class="badge bg-danger rounded-pill"><?= $pendingAccessCount ?></span>
                <?php endif; ?>
            </a>
        </li>

        <li class="text-muted mt-3 mb-1 px-3 small text-uppercase fw-bold" style="letter-spacing: 0.05em;">서비스 운영</li>
        <li class="nav-item">
            <a class="nav-link text-white <?= sidebarActive($currentRoute, 'schema') ?>"
                href="index.php?route=schema/list">
                <i class="bi bi-database-gear"></i> <span>스키마 업데이트</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link text-white <?= sidebarActive($currentRoute, 'notice') ?>"
                href="index.php?route=notice/list">
                <i class="bi bi-megaphone"></i> <span>공지사항</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link text-white <?= sidebarActive($currentRoute, 'email') ?>"
                href="index.php?route=email/list">
                <i class="bi bi-envelope"></i> <span>이메일 발송</span>
            </a>
        </li>
    </ul>
    <?php endif; ?>

    <div class="mt-auto px-3 py-2">
        <div class="card bg-primary bg-gradient border-0 mb-0 overflow-hidden shadow-none" style="border-radius: 0.5rem;">
            <div class="card-body p-3">
                <div class="small text-white-50 mb-2">Technical Support</div>
                <div class="small text-white fw-bold">admin@pibs.co.kr</div>
            </div>
        </div>
    </div>
</nav>
