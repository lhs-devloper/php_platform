<!-- 필터 바 -->
<div class="filter-bar shadow-sm mb-4">
    <form method="GET" class="row g-3 align-items-end">
        <input type="hidden" name="route" value="tenant/list">
        <div class="col-md-4">
            <label class="form-label small fw-bold text-primary">검색어</label>
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                <input type="text" name="search" class="form-control form-control-sm border-start-0 ps-0" 
                       placeholder="업체명 / 사업자번호 / 대표자" value="<?= htmlspecialchars($keyword) ?>">
            </div>
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-bold text-primary">상태</label>
            <select name="status" class="form-select form-select-sm">
                <option value="">전체</option>
                <option value="ACTIVE" <?= $status === 'ACTIVE' ? 'selected' : '' ?>>운영중</option>
                <option value="PENDING" <?= $status === 'PENDING' ? 'selected' : '' ?>>대기</option>
                <option value="SUSPENDED" <?= $status === 'SUSPENDED' ? 'selected' : '' ?>>정지</option>
                <option value="TERMINATED" <?= $status === 'TERMINATED' ? 'selected' : '' ?>>해지</option>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-bold text-primary">서비스 유형</label>
            <select name="service_type" class="form-select form-select-sm">
                <option value="">전체</option>
                <option value="POSTURE" <?= $serviceType === 'POSTURE' ? 'selected' : '' ?>>AI자세분석</option>
                <option value="FOOT" <?= $serviceType === 'FOOT' ? 'selected' : '' ?>>AIoT족부분석</option>
                <option value="BOTH" <?= $serviceType === 'BOTH' ? 'selected' : '' ?>>통합</option>
            </select>
        </div>
        <div class="col-md-4 text-end">
            <button type="submit" class="btn btn-sm btn-primary px-3">
                검색
            </button>
            <a href="index.php?route=tenant/list" class="btn btn-sm btn-outline-secondary px-3">초기화</a>
            <?php if (Auth::hasRole(['SUPER_ADMIN','ADMIN','OPERATOR'])): ?>
            <a href="index.php?route=tenant/create" class="btn btn-sm btn-success px-3 ms-2">
                <i class="bi bi-plus-lg"></i> 가맹점 등록
            </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- 목록 테이블 -->
<div class="card border-0 shadow-sm overflow-hidden">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4" style="width: 60px;">No</th>
                        <th>업체명</th>
                        <th>사업자번호</th>
                        <th>대표자</th>
                        <th>전화번호</th>
                        <th>서비스</th>
                        <th>상태</th>
                        <th>계약기간</th>
                        <th>등록일</th>
                        <th class="text-center pe-4" style="width: 80px;">사이트</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($tenants)): ?>
                    <tr><td colspan="10" class="text-center text-muted py-5">등록된 가맹점이 없습니다.</td></tr>
                <?php else: ?>
                    <?php $rowNo = $pagination->totalItems() - $pagination->offset(); ?>
                    <?php foreach ($tenants as $t): ?>
                    <tr>
                        <td class="ps-4 text-muted small"><?= $rowNo-- ?></td>
                        <td>
                            <a href="index.php?route=tenant/detail&id=<?= $t['id'] ?>" class="fw-bold text-decoration-none text-primary">
                                <?= htmlspecialchars($t['company_name']) ?>
                            </a>
                        </td>
                        <td><small class="text-muted"><?= htmlspecialchars($t['business_number'] ?: '-') ?></small></td>
                        <td class="fw-semibold"><?= htmlspecialchars($t['ceo_name'] ?: '-') ?></td>
                        <td><small><?= htmlspecialchars($t['phone'] ?: '-') ?></small></td>
                        <td><?= serviceTypeBadge($t['service_type']) ?></td>
                        <td><?= statusBadge($t['status']) ?></td>
                        <td>
                            <small class="text-muted">
                            <?php if ($t['contract_start']): ?>
                                <?= $t['contract_start'] ?> ~ <?= $t['contract_end'] ?: '미정' ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                            </small>
                        </td>
                        <td><small class="text-muted"><?= substr($t['created_at'], 0, 10) ?></small></td>
                        <td class="text-center pe-4">
                            <?php if (!empty($t['site_domain'])): ?>
                            <a href="index.php?route=tenant/access_site&id=<?= $t['id'] ?>" target="_blank"
                               class="btn btn-xs btn-outline-primary shadow-sm" title="사이트 접속">
                                <i class="bi bi-box-arrow-up-right"></i>
                            </a>
                            <?php else: ?>
                            <span class="text-muted small">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
$baseUrl = 'index.php?route=tenant/list&search=' . urlencode($keyword)
         . '&status=' . urlencode($status) . '&service_type=' . urlencode($serviceType);
include BASE_PATH . '/views/components/pagination.php';
?>
