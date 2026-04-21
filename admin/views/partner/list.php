<div class="filter-bar shadow-sm mb-4">
    <form method="GET" class="row g-3 align-items-end">
        <input type="hidden" name="route" value="partner/list">
        <div class="col-md-5">
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
        <div class="col-md-5 text-end">
            <button type="submit" class="btn btn-sm btn-primary px-3">검색</button>
            <a href="index.php?route=partner/list" class="btn btn-sm btn-outline-secondary px-3">초기화</a>
            <?php if (Auth::hasRole(['SUPER_ADMIN','ADMIN','OPERATOR'])): ?>
            <a href="index.php?route=partner/create" class="btn btn-sm btn-success px-3 ms-2">
                <i class="bi bi-plus-lg"></i> 협력업체 등록
            </a>
            <?php endif; ?>
        </div>
    </form>
</div>

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
                        <th class="pe-4">계약기간</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($partners)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-5">등록된 협력업체가 없습니다.</td></tr>
                <?php else: ?>
                    <?php foreach ($partners as $p): ?>
                    <tr>
                        <td class="ps-4 text-muted small"><?= $p['id'] ?></td>
                        <td>
                            <a href="index.php?route=partner/detail&id=<?= $p['id'] ?>" class="fw-bold text-decoration-none text-primary">
                                <?= htmlspecialchars($p['company_name']) ?>
                            </a>
                        </td>
                        <td><small class="text-muted"><?= htmlspecialchars($p['business_number'] ?: '-') ?></small></td>
                        <td class="fw-semibold"><?= htmlspecialchars($p['ceo_name'] ?: '-') ?></td>
                        <td><small><?= htmlspecialchars($p['phone'] ?: '-') ?></small></td>
                        <td><?= serviceTypeBadge($p['service_type']) ?></td>
                        <td><?= statusBadge($p['status']) ?></td>
                        <td class="pe-4">
                            <small class="text-muted">
                                <?= $p['contract_start'] ? $p['contract_start'] . ' ~ ' . ($p['contract_end'] ?: '미정') : '-' ?>
                            </small>
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
$baseUrl = 'index.php?route=partner/list&search=' . urlencode($keyword) . '&status=' . urlencode($status);
include BASE_PATH . '/views/components/pagination.php';
?>
