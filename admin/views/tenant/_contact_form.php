<div class="card border-0 shadow-sm mt-3">
    <div class="card-header bg-white"><h6 class="mb-0">담당자 추가</h6></div>
    <div class="card-body">
        <form method="POST" action="index.php?route=tenant/contact/save">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>">
            <input type="hidden" name="tenant_id" value="<?= $tenant['id'] ?>">
            <input type="hidden" name="contact_id" value="0">

            <div class="row g-2">
                <div class="col-md-2">
                    <input type="text" name="contact_name" class="form-control form-control-sm" placeholder="이름" required>
                </div>
                <div class="col-md-2">
                    <select name="contact_role" class="form-select form-select-sm">
                        <option value="OWNER">대표</option>
                        <option value="MANAGER" selected>운영</option>
                        <option value="TECH">기술</option>
                        <option value="BILLING">정산</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="text" name="contact_phone" class="form-control form-control-sm" placeholder="전화번호">
                </div>
                <div class="col-md-3">
                    <input type="email" name="contact_email" class="form-control form-control-sm" placeholder="이메일">
                </div>
                <div class="col-md-1">
                    <div class="form-check mt-1">
                        <input type="checkbox" name="is_primary" value="1" class="form-check-input" id="isPrimary">
                        <label class="form-check-label small" for="isPrimary">주담당</label>
                    </div>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-sm btn-primary w-100">
                        <i class="bi bi-plus"></i> 추가
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
