<?php $activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'info'; ?>

<!-- 상단 헤더 섹션 -->
<div class="d-flex flex-wrap justify-content-between align-items-end mb-4 gap-3">
    <div>
        <nav aria-label="breadcrumb" class="mb-1">
            <ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item"><a href="index.php?route=tenant/list" class="text-decoration-none text-muted">가맹점 관리</a></li>
                <li class="breadcrumb-item active" aria-current="page">가맹점 상세</li>
            </ol>
        </nav>
        <h3 class="mb-0 fw-bold text-dark">
            <?= htmlspecialchars($tenant['company_name']) ?>
            <span class="ms-2"><?= statusBadge($tenant['status']) ?></span>
            <span class="ms-1"><?= serviceTypeBadge($tenant['service_type']) ?></span>
        </h3>
        <small class="text-muted"><i class="bi bi-calendar3 me-1"></i> 등록일: <?= $tenant['created_at'] ?></small>
    </div>
    
    <div class="d-flex gap-2">
        <?php if (!empty($database) && $database['domain']): ?>
        <a href="index.php?route=tenant/access_site&id=<?= $tenant['id'] ?>" target="_blank"
           class="btn btn-sm btn-primary shadow-sm px-3">
            <i class="bi bi-box-arrow-up-right me-1"></i> 서비스 사이트 접속
        </a>
        <?php endif; ?>
        
        <div class="dropdown">
            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                관리 메뉴
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                <?php if (Auth::hasRole(['SUPER_ADMIN','ADMIN','OPERATOR'])): ?>
                <li><a class="dropdown-item" href="index.php?route=tenant/edit&id=<?= $tenant['id'] ?>"><i class="bi bi-pencil me-2"></i> 정보 수정</a></li>
                <?php endif; ?>
                
                <?php if (Auth::hasRole(['SUPER_ADMIN','ADMIN']) && $tenant['status'] !== 'TERMINATED'): ?>
                <li><button class="dropdown-item text-warning"
                            data-confirm="이 가맹점을 해지 처리하시겠습니까?"
                            data-confirm-title="가맹점 해지"
                            data-action="index.php?route=tenant/delete"
                            data-id="<?= $tenant['id'] ?>">
                    <i class="bi bi-x-circle me-2"></i> 가맹점 해지
                </button></li>
                <?php endif; ?>
                
                <?php if (Auth::hasRole(['SUPER_ADMIN'])): ?>
                <li><hr class="dropdown-divider"></li>
                <li><button class="dropdown-item text-danger"
                            data-confirm="이 가맹점을 완전히 삭제하시겠습니까?<?= !empty($database) ? ' 연결된 DB(' . htmlspecialchars($database['db_name']) . ')도 함께 삭제됩니다.' : '' ?> 이 작업은 되돌릴 수 없습니다."
                            data-confirm-title="가맹점 완전 삭제"
                            data-action="index.php?route=tenant/destroy"
                            data-id="<?= $tenant['id'] ?>">
                    <i class="bi bi-trash3 me-2"></i> 데이터 완전 삭제
                </button></li>
                <?php endif; ?>
            </ul>
        </div>
        <a href="index.php?route=tenant/list" class="btn btn-sm btn-light border px-3">목록으로</a>
    </div>
</div>

<!-- 탭 메뉴 -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-0">
        <ul class="nav nav-pills nav-fill p-1 bg-light rounded-3" role="tablist">
            <li class="nav-item">
                <a class="nav-link py-3 <?= $activeTab === 'info' ? 'active shadow-sm bg-white text-primary fw-bold' : 'text-muted' ?>"
                   href="index.php?route=tenant/detail&id=<?= $tenant['id'] ?>&tab=info">
                   <i class="bi bi-info-circle me-1"></i> 기본 정보
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link py-3 <?= $activeTab === 'contacts' ? 'active shadow-sm bg-white text-primary fw-bold' : 'text-muted' ?>"
                   href="index.php?route=tenant/detail&id=<?= $tenant['id'] ?>&tab=contacts">
                    <i class="bi bi-person-lines-fill me-1"></i> 담당자 목록 <span class="badge <?= $activeTab === 'contacts' ? 'bg-primary' : 'bg-secondary' ?> ms-1"><?= count($contacts) ?></span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link py-3 <?= $activeTab === 'database' ? 'active shadow-sm bg-white text-primary fw-bold' : 'text-muted' ?>"
                   href="index.php?route=tenant/detail&id=<?= $tenant['id'] ?>&tab=database">
                   <i class="bi bi-database-check me-1"></i> DB / 시스템 인스턴스
                </a>
            </li>
        </ul>
    </div>
</div>

<div class="tab-content">
<?php if ($activeTab === 'info'): ?>
    <!-- 기본 정보 카드 -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent py-3">
            <h6 class="m-0 font-weight-bold text-primary">가맹점 기본 정보</h6>
        </div>
        <div class="card-body">
            <div class="row g-4">
                <div class="col-md-6 border-end">
                    <table class="table table-borderless table-sm mb-0">
                        <tr><th class="text-muted fw-normal" style="width:140px">업체명</th><td class="fw-bold"><?= htmlspecialchars($tenant['company_name']) ?></td></tr>
                        <tr><th class="text-muted fw-normal">사업자번호</th><td><?= htmlspecialchars($tenant['business_number'] ?: '-') ?></td></tr>
                        <tr><th class="text-muted fw-normal">대표자</th><td><?= htmlspecialchars($tenant['ceo_name'] ?: '-') ?></td></tr>
                        <tr><th class="text-muted fw-normal">전화번호</th><td><?= htmlspecialchars($tenant['phone'] ?: '-') ?></td></tr>
                        <tr><th class="text-muted fw-normal">이메일</th><td><?= htmlspecialchars($tenant['email'] ?: '-') ?></td></tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-borderless table-sm mb-0">
                        <tr><th class="text-muted fw-normal" style="width:140px">주소</th><td><?= htmlspecialchars(trim(($tenant['zipcode'] ?: '') . ' ' . ($tenant['address'] ?: '') . ' ' . ($tenant['address_detail'] ?: ''))) ?: '-' ?></td></tr>
                        <tr><th class="text-muted fw-normal">계약기간</th><td><?= $tenant['contract_start'] ? '<span class="fw-bold">' . $tenant['contract_start'] . '</span> ~ <span class="fw-bold">' . ($tenant['contract_end'] ?: '미정') . '</span>' : '-' ?></td></tr>
                        <tr><th class="text-muted fw-normal">메모</th><td><div class="bg-light p-2 rounded small"><?= nl2br(htmlspecialchars($tenant['memo'] ?: '기록된 메모가 없습니다.')) ?></div></td></tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

<?php elseif ($activeTab === 'contacts'): ?>
    <?php include BASE_PATH . '/views/tenant/_contact_list.php'; ?>
    <?php if (Auth::hasRole(['SUPER_ADMIN','ADMIN','OPERATOR'])): ?>
        <?php include BASE_PATH . '/views/tenant/_contact_form.php'; ?>
    <?php endif; ?>

<?php elseif ($activeTab === 'database'): ?>
    <?php include BASE_PATH . '/views/tenant/_database_info.php'; ?>

<?php endif; ?>
</div>

<?php include BASE_PATH . '/views/components/modal_confirm.php'; ?>
