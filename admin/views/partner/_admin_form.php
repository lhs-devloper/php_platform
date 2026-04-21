<div class="card border-0 shadow-sm mt-3">
    <div class="card-header bg-white"><h6 class="mb-0"><i class="bi bi-person-plus me-1"></i> 관리자 계정 추가</h6></div>
    <div class="card-body">
        <form method="POST" action="index.php?route=partner/admin/save">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>">
            <input type="hidden" name="partner_id" value="<?= $partner['id'] ?>">
            <input type="hidden" name="admin_id" value="0">

            <div class="row g-2">
                <div class="col-md-2">
                    <input type="text" name="admin_name" class="form-control form-control-sm" placeholder="이름 *" required>
                </div>
                <div class="col-md-2">
                    <input type="text" name="admin_login_id" class="form-control form-control-sm" placeholder="로그인 ID *" required>
                </div>
                <div class="col-md-2">
                    <input type="password" name="admin_password" class="form-control form-control-sm" placeholder="비밀번호 *" required>
                </div>
                <div class="col-md-1">
                    <select name="admin_role" class="form-select form-select-sm">
                        <option value="PARTNER_ADMIN">관리자</option>
                        <option value="PARTNER_STAFF" selected>직원</option>
                        <option value="PARTNER_VIEWER">조회</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="email" name="admin_email" class="form-control form-control-sm" placeholder="이메일">
                </div>
                <div class="col-md-2">
                    <input type="text" name="admin_phone" class="form-control form-control-sm" placeholder="전화번호">
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-sm btn-primary w-100"><i class="bi bi-plus"></i> 추가</button>
                </div>
            </div>
        </form>
    </div>
</div>
