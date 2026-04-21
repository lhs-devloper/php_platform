<!-- 연결된 가맹점 목록 -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr><th>가맹점명</th><th>서비스</th><th>상태</th><th>연결일</th><th></th></tr>
            </thead>
            <tbody>
            <?php if (empty($mappedTenants)): ?>
                <tr><td colspan="5" class="text-center text-muted py-3">연결된 가맹점이 없습니다.</td></tr>
            <?php else: ?>
                <?php foreach ($mappedTenants as $mt): ?>
                <tr>
                    <td>
                        <a href="index.php?route=tenant/detail&id=<?= $mt['tenant_id'] ?>"><?= htmlspecialchars($mt['company_name']) ?></a>
                    </td>
                    <td><?= serviceTypeBadge($mt['service_type']) ?></td>
                    <td><?= statusBadge($mt['tenant_status']) ?></td>
                    <td><small><?= substr($mt['created_at'], 0, 10) ?></small></td>
                    <td class="text-end">
                        <?php if (Auth::hasRole(['SUPER_ADMIN','ADMIN'])): ?>
                        <form method="POST" action="index.php?route=partner/tenant/remove" style="display:inline;">
                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>">
                            <input type="hidden" name="partner_id" value="<?= $partner['id'] ?>">
                            <input type="hidden" name="tenant_id" value="<?= $mt['tenant_id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger"
                                    onclick="return confirm('이 가맹점 연결을 해제하시겠습니까?')">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- 가맹점 추가 -->
<?php if (Auth::hasRole(['SUPER_ADMIN','ADMIN','OPERATOR']) && !empty($availableTenants)): ?>
<div class="card border-0 shadow-sm mt-3">
    <div class="card-header bg-white"><h6 class="mb-0">가맹점 연결 추가</h6></div>
    <div class="card-body">
        <form method="POST" action="index.php?route=partner/tenant/add" class="d-flex gap-2">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>">
            <input type="hidden" name="partner_id" value="<?= $partner['id'] ?>">
            <select name="tenant_id" class="form-select form-select-sm" style="max-width:400px;" required>
                <option value="">-- 가맹점 선택 --</option>
                <?php foreach ($availableTenants as $at): ?>
                <option value="<?= $at['id'] ?>"><?= htmlspecialchars($at['company_name']) ?> (<?= $at['status'] ?>)</option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-link-45deg"></i> 연결</button>
        </form>
    </div>
</div>
<?php endif; ?>
