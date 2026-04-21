<?php $activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'info'; ?>

<div class="d-flex flex-wrap justify-content-between align-items-end mb-4 gap-3">
    <div>
        <nav aria-label="breadcrumb" class="mb-1">
            <ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item"><a href="index.php?route=partner/list" class="text-decoration-none text-muted">협력업체 관리</a></li>
                <li class="breadcrumb-item active" aria-current="page">협력업체 상세</li>
            </ol>
        </nav>
        <h3 class="mb-0 fw-bold text-dark">
            <?= htmlspecialchars($partner['company_name']) ?>
            <span class="ms-2"><?= statusBadge($partner['status']) ?></span>
            <span class="ms-1"><?= serviceTypeBadge($partner['service_type']) ?></span>
        </h3>
        <small class="text-muted"><i class="bi bi-calendar3 me-1"></i> 등록일: <?= $partner['created_at'] ?></small>
    </div>
    
    <div class="d-flex gap-2">
        <?php if (Auth::hasRole(['SUPER_ADMIN','ADMIN','OPERATOR'])): ?>
        <a href="index.php?route=partner/edit&id=<?= $partner['id'] ?>" class="btn btn-sm btn-outline-primary px-3">
            <i class="bi bi-pencil me-1"></i> 수정
        </a>
        <?php endif; ?>
        <?php if (Auth::hasRole(['SUPER_ADMIN','ADMIN']) && $partner['status'] !== 'TERMINATED'): ?>
        <button class="btn btn-sm btn-outline-danger px-3"
                data-confirm="이 협력업체를 해지 처리하시겠습니까?"
                data-confirm-title="협력업체 해지"
                data-action="index.php?route=partner/delete"
                data-id="<?= $partner['id'] ?>">
            <i class="bi bi-x-circle me-1"></i> 해지
        </button>
        <?php endif; ?>
        <a href="index.php?route=partner/list" class="btn btn-sm btn-light border px-3">목록으로</a>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-0">
        <ul class="nav nav-pills nav-fill p-1 bg-light rounded-3" role="tablist">
            <li class="nav-item">
                <a class="nav-link py-3 <?= $activeTab === 'info' ? 'active shadow-sm bg-white text-primary fw-bold' : 'text-muted' ?>"
                   href="index.php?route=partner/detail&id=<?= $partner['id'] ?>&tab=info">
                   <i class="bi bi-info-circle me-1"></i> 기본 정보
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link py-3 <?= $activeTab === 'admins' ? 'active shadow-sm bg-white text-primary fw-bold' : 'text-muted' ?>"
                   href="index.php?route=partner/detail&id=<?= $partner['id'] ?>&tab=admins">
                    <i class="bi bi-person-badge me-1"></i> 관리자 계정 <span class="badge <?= $activeTab === 'admins' ? 'bg-primary' : 'bg-secondary' ?> ms-1"><?= count($admins) ?></span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link py-3 <?= $activeTab === 'tenants' ? 'active shadow-sm bg-white text-primary fw-bold' : 'text-muted' ?>"
                   href="index.php?route=partner/detail&id=<?= $partner['id'] ?>&tab=tenants">
                   <i class="bi bi-building-check me-1"></i> 소속 가맹점 <span class="badge <?= $activeTab === 'tenants' ? 'bg-primary' : 'bg-secondary' ?> ms-1"><?= count($mappedTenants) ?></span>
                </a>
            </li>
        </ul>
    </div>
</div>

<div class="tab-content">
<?php if ($activeTab === 'info'): ?>
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent py-3">
            <h6 class="m-0 font-weight-bold text-primary">협력업체 상세 정보</h6>
        </div>
        <div class="card-body">
            <div class="row g-4">
                <div class="col-md-6 border-end">
                    <table class="table table-borderless table-sm mb-0">
                        <tr><th class="text-muted fw-normal" style="width:150px">업체명</th><td class="fw-bold"><?= htmlspecialchars($partner['company_name']) ?></td></tr>
                        <tr><th class="text-muted fw-normal">사업자등록번호</th><td><?= htmlspecialchars($partner['business_number'] ?: '-') ?></td></tr>
                        <tr><th class="text-muted fw-normal">대표자</th><td><?= htmlspecialchars($partner['ceo_name'] ?: '-') ?></td></tr>
                        <tr><th class="text-muted fw-normal">전화번호</th><td><?= htmlspecialchars($partner['phone'] ?: '-') ?></td></tr>
                        <tr><th class="text-muted fw-normal">이메일</th><td><?= htmlspecialchars($partner['email'] ?: '-') ?></td></tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-borderless table-sm mb-0">
                        <tr><th class="text-muted fw-normal" style="width:150px">주소</th><td><?= htmlspecialchars($partner['address'] ?: '-') ?></td></tr>
                        <tr><th class="text-muted fw-normal">계약기간</th><td><?= $partner['contract_start'] ? '<span class="fw-bold">' . $partner['contract_start'] . '</span> ~ <span class="fw-bold">' . ($partner['contract_end'] ?: '미정') . '</span>' : '-' ?></td></tr>
                        <tr><th class="text-muted fw-normal">메모</th><td><div class="bg-light p-2 rounded small"><?= nl2br(htmlspecialchars($partner['memo'] ?: '기록된 메모가 없습니다.')) ?></div></td></tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

<?php elseif ($activeTab === 'admins'): ?>
    <?php include BASE_PATH . '/views/partner/_admin_list.php'; ?>
    <?php if (Auth::hasRole(['SUPER_ADMIN','ADMIN','OPERATOR'])): ?>
        <?php include BASE_PATH . '/views/partner/_admin_form.php'; ?>
    <?php endif; ?>

<?php elseif ($activeTab === 'tenants'): ?>
    <?php include BASE_PATH . '/views/partner/_tenant_mapping.php'; ?>

<?php endif; ?>
</div>

<?php include BASE_PATH . '/views/components/modal_confirm.php'; ?>
