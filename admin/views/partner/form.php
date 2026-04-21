<?php $p = $partner; ?>
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="POST" action="index.php?route=<?= $isEdit ? 'partner/edit&id=' . $p['id'] : 'partner/create' ?>">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>">

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">업체명 <span class="text-danger">*</span></label>
                    <input type="text" name="company_name" class="form-control" value="<?= htmlspecialchars($p['company_name'] ?? '') ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">사업자등록번호</label>
                    <input type="text" name="business_number" class="form-control" value="<?= htmlspecialchars($p['business_number'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">대표자명</label>
                    <input type="text" name="ceo_name" class="form-control" value="<?= htmlspecialchars($p['ceo_name'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">전화번호</label>
                    <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($p['phone'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">이메일</label>
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($p['email'] ?? '') ?>">
                </div>
                <div class="col-md-12">
                    <label class="form-label">주소</label>
                    <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($p['address'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">서비스 유형</label>
                    <select name="service_type" class="form-select">
                        <option value="BOTH" <?= ($p['service_type'] ?? 'BOTH') === 'BOTH' ? 'selected' : '' ?>>통합</option>
                        <option value="POSTURE" <?= ($p['service_type'] ?? '') === 'POSTURE' ? 'selected' : '' ?>>AI자세분석</option>
                        <option value="FOOT" <?= ($p['service_type'] ?? '') === 'FOOT' ? 'selected' : '' ?>>AIoT족부분석</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">상태</label>
                    <select name="status" class="form-select">
                        <?php foreach (['PENDING' => '대기', 'ACTIVE' => '운영중', 'SUSPENDED' => '정지', 'TERMINATED' => '해지'] as $val => $label): ?>
                        <option value="<?= $val ?>" <?= ($p['status'] ?? 'PENDING') === $val ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">계약 시작일</label>
                    <input type="date" name="contract_start" class="form-control" value="<?= htmlspecialchars($p['contract_start'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">계약 종료일</label>
                    <input type="date" name="contract_end" class="form-control" value="<?= htmlspecialchars($p['contract_end'] ?? '') ?>">
                </div>
                <div class="col-12">
                    <label class="form-label">메모</label>
                    <textarea name="memo" class="form-control" rows="3"><?= htmlspecialchars($p['memo'] ?? '') ?></textarea>
                </div>
            </div>

            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> <?= $isEdit ? '수정' : '등록' ?></button>
                <a href="index.php?route=partner/list" class="btn btn-outline-secondary">취소</a>
            </div>
        </form>
    </div>
</div>
