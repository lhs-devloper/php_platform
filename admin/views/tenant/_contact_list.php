<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr><th>이름</th><th>역할</th><th>전화번호</th><th>이메일</th><th>주담당</th><th></th></tr>
            </thead>
            <tbody>
            <?php if (empty($contacts)): ?>
                <tr><td colspan="6" class="text-center text-muted py-3">등록된 담당자가 없습니다.</td></tr>
            <?php else: ?>
                <?php
                $roleLabels = ['OWNER' => '대표', 'MANAGER' => '운영', 'TECH' => '기술', 'BILLING' => '정산'];
                foreach ($contacts as $c):
                ?>
                <tr>
                    <td><?= htmlspecialchars($c['name']) ?></td>
                    <td><span class="badge bg-light text-dark"><?= $roleLabels[$c['role']] ?? $c['role'] ?></span></td>
                    <td><?= htmlspecialchars($c['phone'] ?: '-') ?></td>
                    <td><?= htmlspecialchars($c['email'] ?: '-') ?></td>
                    <td><?= $c['is_primary'] ? '<i class="bi bi-check-circle-fill text-success"></i>' : '' ?></td>
                    <td class="text-end">
                        <?php if (Auth::hasRole(['SUPER_ADMIN','ADMIN'])): ?>
                        <button class="btn btn-sm btn-outline-danger"
                                data-confirm="이 담당자를 삭제하시겠습니까?"
                                data-confirm-title="담당자 삭제"
                                data-action="index.php?route=tenant/contact/delete&tenant_id=<?= $tenant['id'] ?>"
                                data-id="<?= $c['id'] ?>">
                            <i class="bi bi-trash"></i>
                        </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
