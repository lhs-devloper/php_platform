<?php $adminUser = Auth::user(); ?>
<nav class="navbar navbar-expand navbar-light bg-white sticky-top px-4 py-3">
    <div class="container-fluid px-0">
        <h5 class="navbar-text mb-0 fw-bold text-dark"><?= isset($pageTitle) ? htmlspecialchars($pageTitle) : '' ?></h5>
        
        <div class="ms-auto d-flex align-items-center">
            <div class="vr mx-3 opacity-10"></div>
            
            <?php if ($adminUser): ?>
                <div class="dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="text-end me-3 d-none d-lg-block">
                            <div class="fw-bold small text-dark lh-1"><?= htmlspecialchars($adminUser['name']) ?></div>
                            <small class="text-muted" style="font-size: 0.7rem;"><?= htmlspecialchars($adminUser['login_id']) ?></small>
                        </div>
                        <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center text-white" style="width: 32px; height: 32px;">
                            <i class="bi bi-person-fill"></i>
                        </div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2" style="border-radius: 0.5rem;">
                        <li class="px-3 py-2 border-bottom mb-1 d-lg-none">
                            <div class="fw-bold small text-dark"><?= htmlspecialchars($adminUser['name']) ?></div>
                            <small class="text-muted"><?= htmlspecialchars($adminUser['login_id']) ?></small>
                        </li>
                        <li>
                            <div class="px-3 py-1">
                                <?= roleBadge($adminUser['role']) ?>
                            </div>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger" href="index.php?route=auth/logout">
                                <i class="bi bi-box-arrow-right me-2"></i> 로그아웃
                            </a>
                        </li>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    </div>
</nav>
