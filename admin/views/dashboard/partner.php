<?php $user = Auth::user(); ?>

<!-- 환영 메시지 -->
<div class="alert alert-info border-0 shadow-sm mb-4">
    <div class="d-flex align-items-center">
        <i class="bi bi-people-fill fs-3 me-3"></i>
        <div>
            <strong><?= htmlspecialchars($user['partner_name'] ?? '') ?></strong>
            <span class="text-muted">협력업체 포털에 오신 것을 환영합니다.</span>
            <div class="small text-muted mt-1">
                로그인: <?= htmlspecialchars($user['name']) ?> (<?= htmlspecialchars($user['login_id']) ?>)
            </div>
        </div>
    </div>
</div>

<!-- 소속 가맹점 통계 -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <div class="h3 mb-0 fw-bold text-primary"><?= $tenantStats['total'] ?></div>
                <div class="small text-muted">소속 가맹점</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <div class="h3 mb-0 fw-bold text-success"><?= $tenantStats['ACTIVE'] ?? 0 ?></div>
                <div class="small text-muted">운영중</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <div class="h3 mb-0 fw-bold text-warning"><?= $tenantStats['PENDING'] ?? 0 ?></div>
                <div class="small text-muted">대기</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <div class="h3 mb-0 fw-bold text-danger"><?= $tenantStats['SUSPENDED'] ?? 0 ?></div>
                <div class="small text-muted">정지</div>
            </div>
        </div>
    </div>
</div>

<!-- 소속 가맹점 목록 -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <strong><i class="bi bi-building me-1"></i> 소속 가맹점</strong>
        <a href="index.php?route=tenant/list" class="btn btn-sm btn-outline-primary">전체 보기</a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">업체명</th>
                        <th>서비스</th>
                        <th>상태</th>
                        <th>도메인</th>
                        <th class="text-end pe-4">상세</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($tenants)): ?>
                    <tr><td colspan="5" class="text-center text-muted py-5">소속된 가맹점이 없습니다.</td></tr>
                <?php else: foreach ($tenants as $t): ?>
                    <tr>
                        <td class="ps-4 fw-bold"><?= htmlspecialchars($t['company_name']) ?></td>
                        <td><?= serviceTypeBadge($t['service_type']) ?></td>
                        <td><?= statusBadge($t['status']) ?></td>
                        <td>
                            <?php if (!empty($t['site_domain'])): ?>
                                <code class="small"><?= htmlspecialchars($t['site_domain']) ?></code>
                            <?php else: ?>
                                <span class="text-muted small">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end pe-4">
                            <a href="index.php?route=tenant/detail&id=<?= $t['id'] ?>" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
