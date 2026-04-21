<?php
$partnerRoles = [
    'PARTNER_ADMIN'  => '업체관리자',
    'PARTNER_STAFF'  => '직원',
    'PARTNER_VIEWER' => '조회전용',
];
?>
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr><th>이름</th><th>로그인 ID</th><th>역할</th><th>이메일</th><th>전화</th><th>상태</th><th>마지막 로그인</th><th class="text-end">관리</th></tr>
            </thead>
            <tbody>
            <?php if (empty($admins)): ?>
                <tr><td colspan="8" class="text-center text-muted py-3">등록된 관리자 계정이 없습니다.</td></tr>
            <?php else: ?>
                <?php foreach ($admins as $a): ?>
                <tr class="<?= !$a['is_active'] ? 'table-secondary' : '' ?>">
                    <td><?= htmlspecialchars($a['name']) ?></td>
                    <td><code><?= htmlspecialchars($a['login_id']) ?></code></td>
                    <td><span class="badge bg-light text-dark"><?= $partnerRoles[$a['role']] ?? $a['role'] ?></span></td>
                    <td><small><?= htmlspecialchars($a['email'] ?: '-') ?></small></td>
                    <td><small><?= htmlspecialchars($a['phone'] ?: '-') ?></small></td>
                    <td><?= $a['is_active'] ? '<span class="badge bg-success">활성</span>' : '<span class="badge bg-secondary">비활성</span>' ?></td>
                    <td><small><?= $a['last_login_at'] ?: '-' ?></small></td>
                    <td class="text-end">
                        <div class="btn-group btn-group-sm">
                            <?php if (Auth::hasRole(['SUPER_ADMIN','ADMIN','OPERATOR'])): ?>
                            <!-- 수정 -->
                            <button type="button" class="btn btn-outline-primary" title="수정"
                                    onclick="openEditModal(<?= htmlspecialchars(json_encode($a, JSON_UNESCAPED_UNICODE)) ?>)">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <?php endif; ?>

                            <?php if (Auth::hasRole(['SUPER_ADMIN','ADMIN'])): ?>
                            <!-- 활성/비활성 토글 -->
                            <form method="POST" action="index.php?route=partner/admin/toggle" style="display:inline;">
                                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>">
                                <input type="hidden" name="id" value="<?= $a['id'] ?>">
                                <input type="hidden" name="partner_id" value="<?= $partner['id'] ?>">
                                <?php if ($a['is_active']): ?>
                                <button type="submit" class="btn btn-outline-warning" title="비활성화"
                                        onclick="return confirm('이 관리자를 비활성화하시겠습니까?')">
                                    <i class="bi bi-person-x"></i>
                                </button>
                                <?php else: ?>
                                <button type="submit" class="btn btn-outline-success" title="활성화"
                                        onclick="return confirm('이 관리자를 다시 활성화하시겠습니까?')">
                                    <i class="bi bi-person-check"></i>
                                </button>
                                <?php endif; ?>
                            </form>
                            <?php endif; ?>

                            <?php if (Auth::hasRole(['SUPER_ADMIN'])): ?>
                            <!-- 삭제 -->
                            <button type="button" class="btn btn-outline-danger" title="삭제"
                                    data-confirm="이 관리자 계정을 완전히 삭제하시겠습니까? 이 작업은 되돌릴 수 없습니다."
                                    data-confirm-title="관리자 삭제"
                                    data-action="index.php?route=partner/admin/delete&partner_id=<?= $partner['id'] ?>"
                                    data-id="<?= $a['id'] ?>">
                                <i class="bi bi-trash"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- 관리자 수정 모달 -->
<div class="modal fade" id="editAdminModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="index.php?route=partner/admin/save">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>">
        <input type="hidden" name="partner_id" value="<?= $partner['id'] ?>">
        <input type="hidden" name="admin_id" id="editAdminId" value="">
        <div class="modal-header">
            <h5 class="modal-title"><i class="bi bi-pencil me-1"></i> 관리자 수정</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">이름 <span class="text-danger">*</span></label>
                    <input type="text" name="admin_name" id="editAdminName" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">로그인 ID</label>
                    <input type="text" name="admin_login_id" id="editAdminLoginId" class="form-control" readonly>
                </div>
                <div class="col-md-6">
                    <label class="form-label">비밀번호 <small class="text-muted">(변경시만 입력)</small></label>
                    <input type="password" name="admin_password" class="form-control" placeholder="미입력시 유지">
                </div>
                <div class="col-md-6">
                    <label class="form-label">역할</label>
                    <select name="admin_role" id="editAdminRole" class="form-select">
                        <option value="PARTNER_ADMIN">업체관리자</option>
                        <option value="PARTNER_STAFF">직원</option>
                        <option value="PARTNER_VIEWER">조회전용</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">이메일</label>
                    <input type="email" name="admin_email" id="editAdminEmail" class="form-control">
                </div>
                <div class="col-md-6">
                    <label class="form-label">전화번호</label>
                    <input type="text" name="admin_phone" id="editAdminPhone" class="form-control">
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
            <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> 저장</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function openEditModal(admin) {
    document.getElementById('editAdminId').value = admin.id;
    document.getElementById('editAdminName').value = admin.name || '';
    document.getElementById('editAdminLoginId').value = admin.login_id || '';
    document.getElementById('editAdminRole').value = admin.role || 'PARTNER_STAFF';
    document.getElementById('editAdminEmail').value = admin.email || '';
    document.getElementById('editAdminPhone').value = admin.phone || '';
    new bootstrap.Modal(document.getElementById('editAdminModal')).show();
}
</script>
