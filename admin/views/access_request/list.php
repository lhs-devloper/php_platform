<div class="filter-bar shadow-sm mb-4">
    <form method="GET" class="row g-3 align-items-end">
        <input type="hidden" name="route" value="access_request/list">
        <div class="col-md-3">
            <label class="form-label small fw-bold text-primary">상태 필터</label>
            <select name="status" class="form-select form-select-sm">
                <option value="">전체 요청</option>
                <option value="PENDING" <?= $status === 'PENDING' ? 'selected' : '' ?>>대기중</option>
                <option value="APPROVED" <?= $status === 'APPROVED' ? 'selected' : '' ?>>승인됨</option>
                <option value="REJECTED" <?= $status === 'REJECTED' ? 'selected' : '' ?>>거절됨</option>
                <option value="EXPIRED" <?= $status === 'EXPIRED' ? 'selected' : '' ?>>만료됨</option>
                <option value="REVOKED" <?= $status === 'REVOKED' ? 'selected' : '' ?>>철회됨</option>
            </select>
        </div>
        <div class="col-md-9 text-end">
            <button type="submit" class="btn btn-sm btn-primary px-4">필터 적용</button>
            <a href="index.php?route=access_request/list" class="btn btn-sm btn-outline-secondary px-3">초기화</a>
            <?php if (Auth::isPartner()): ?>
            <a href="index.php?route=access_request/create" class="btn btn-sm btn-primary px-3 ms-2">
                <i class="bi bi-plus-lg"></i> 열람 요청
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
                        <th class="ps-4" style="width: 60px;">ID</th>
                        <th>협력업체</th>
                        <th>대상 가맹점</th>
                        <th>요청자</th>
                        <th>열람 범위</th>
                        <th>상태</th>
                        <th>허용 기간</th>
                        <th>요청일</th>
                        <th class="text-center pe-4" style="width: 80px;"></th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($requests)): ?>
                    <tr><td colspan="9" class="text-center text-muted py-5">검색 조건에 맞는 열람 요청이 없습니다.</td></tr>
                <?php else: ?>
                    <?php
                    $scopeLabels = ['FULL' => '전체', 'REPORT_ONLY' => '리포트', 'STATS_ONLY' => '통계'];
                    foreach ($requests as $r):
                    ?>
                    <tr>
                        <td class="ps-4 text-muted small">#<?= $r['id'] ?></td>
                        <td class="fw-bold"><?= htmlspecialchars($r['partner_name'] ?: '-') ?></td>
                        <td>
                            <a href="index.php?route=tenant/detail&id=<?= $r['requested_tenant_id'] ?>" class="text-decoration-none text-dark hover-primary">
                                <?= htmlspecialchars($r['tenant_name'] ?: '-') ?>
                            </a>
                        </td>
                        <td><small class="fw-semibold"><?= htmlspecialchars($r['requester_name'] ?: '-') ?></small></td>
                        <td><span class="badge bg-light text-dark border"><?= $scopeLabels[$r['access_scope']] ?? $r['access_scope'] ?></span></td>
                        <td><?= statusBadge($r['status']) ?></td>
                        <td>
                            <small class="text-muted">
                            <?php if ($r['access_start']): ?>
                                <?= substr($r['access_start'], 2, 8) ?> ~ <?= substr($r['access_end'], 2, 8) ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                            </small>
                        </td>
                        <td><small class="text-muted"><?= substr($r['created_at'], 0, 10) ?></small></td>
                        <td class="text-center pe-4">
                            <a href="index.php?route=access_request/detail&id=<?= $r['id'] ?>" class="btn btn-xs btn-outline-primary shadow-sm">
                                <i class="bi bi-search"></i>
                            </a>
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
$baseUrl = 'index.php?route=access_request/list&status=' . urlencode($status);
include BASE_PATH . '/views/components/pagination.php';
?>
