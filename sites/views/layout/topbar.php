<?php $adminUser = Auth::user(); ?>
<nav class="navbar navbar-expand navbar-light bg-white sticky-top px-4 py-3">
    <div class="container-fluid px-0">
        <h5 class="navbar-text mb-0 fw-bold text-dark"><?= isset($pageTitle) ? htmlspecialchars($pageTitle) : '' ?></h5>

        <div class="ms-auto d-flex align-items-center">
            <?php
            $__svcLabels = [
                'POSTURE' => ['icon' => 'bi-body-text', 'label' => '자세분석', 'color' => 'primary'],
                'FOOT'    => ['icon' => 'bi-footprints', 'label' => '족부분석', 'color' => 'info'],
                'BOTH'    => ['icon' => 'bi-stack', 'label' => '자세+족부', 'color' => 'accent'],
            ];
            $__svc = $__svcLabels[TENANT_SERVICE_TYPE] ?? null;
            if ($__svc): ?>
                <span class="badge bg-<?= $__svc['color'] ?>-subtle text-<?= $__svc['color'] ?> border border-<?= $__svc['color'] ?>-subtle px-3 py-2 d-none d-md-inline-block me-2" title="계약 서비스">
                    <i class="bi <?= $__svc['icon'] ?> me-1"></i> <?= $__svc['label'] ?>
                </span>
                <div class="vr mx-2 opacity-10 d-none d-md-block"></div>
            <?php endif; ?>


            <?php if (!empty($_SESSION['is_central_admin'])): ?>
                <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-3 py-2 d-none d-md-inline-block">
                    <i class="bi bi-shield-check me-1"></i> 중앙관리자 모드 (<?= htmlspecialchars($_SESSION['central_admin_name']) ?>)
                </span>
                <div class="vr mx-3 opacity-10 d-none d-md-block"></div>
            <?php endif; ?>

            <?php if ($adminUser): ?>
                <div class="dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="text-end me-3 d-none d-lg-block">
                            <div class="fw-bold small text-dark lh-1"><?= htmlspecialchars($adminUser['name']) ?></div>
                            <small class="text-muted" style="font-size: 0.7rem;"><?= htmlspecialchars($adminUser['login_id']) ?></small>
                        </div>
                        <div class="rounded-circle bg-accent d-flex align-items-center justify-content-center text-white shadow-sm" style="width: 36px; height: 36px;">
                            <i class="bi bi-person-fill fs-5"></i>
                        </div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2" style="border-radius: 0.75rem; min-width: 200px;">
                        <li class="px-3 py-3 border-bottom mb-1">
                            <div class="fw-bold text-dark"><?= htmlspecialchars($adminUser['name']) ?></div>
                            <small class="text-muted"><?= htmlspecialchars($adminUser['login_id']) ?></small>
                            <div class="mt-2">
                                <?= roleBadge($adminUser['role']) ?>
                            </div>
                        </li>
                        <li>
                            <a class="dropdown-item py-2" href="index.php?route=auth/profile">
                                <i class="bi bi-person-gear me-2"></i> 내 계정 설정
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item py-2" href="index.php?route=member/list">
                                <i class="bi bi-people me-2"></i> 회원 관리
                            </a>
                        </li>
                        <li><hr class="dropdown-divider opacity-50"></li>
                        <li>
                            <a class="dropdown-item text-danger py-2" href="index.php?route=auth/logout">
                                <i class="bi bi-box-arrow-right me-2"></i> 로그아웃
                            </a>
                        </li>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    </div>
</nav>
