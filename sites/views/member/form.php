<?php $m = $member; ?>
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="POST" action="index.php?route=<?= $isEdit ? 'member/edit&id=' . $m['id'] : 'member/create' ?>">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">이름 <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($m['name'] ?? '') ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">전화번호</label>
                    <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($m['phone'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">성별</label>
                    <select name="gender" class="form-select">
                        <option value="">선택</option>
                        <option value="M" <?= ($m['gender'] ?? '') === 'M' ? 'selected' : '' ?>>남</option>
                        <option value="F" <?= ($m['gender'] ?? '') === 'F' ? 'selected' : '' ?>>여</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">생년월일</label>
                    <input type="date" name="birth_date" class="form-control" value="<?= htmlspecialchars($m['birth_date'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">키 (cm)</label>
                    <input type="number" name="height" class="form-control" step="0.1" value="<?= htmlspecialchars($m['height'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">몸무게 (kg)</label>
                    <input type="number" name="weight" class="form-control" step="0.1" value="<?= htmlspecialchars($m['weight'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">상태</label>
                    <select name="status" class="form-select">
                        <?php foreach (['ACTIVE'=>'수강','PAUSED'=>'휴원','HONORARY'=>'명예','WITHDRAWN'=>'퇴원'] as $v=>$l): ?>
                        <option value="<?= $v ?>" <?= ($m['status'] ?? 'ACTIVE') === $v ? 'selected' : '' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">수강반</label>
                    <select name="class_code_id" class="form-select">
                        <option value="">선택</option>
                        <?php foreach ($classCodes as $cc): ?>
                        <option value="<?= $cc['id'] ?>" <?= ($m['class_code_id'] ?? '') == $cc['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cc['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">담당강사</label>
                    <select name="instructor_id" class="form-select">
                        <option value="">선택</option>
                        <?php foreach ($instructors as $inst): ?>
                        <option value="<?= $inst['id'] ?>" <?= ($m['instructor_id'] ?? '') == $inst['id'] ? 'selected' : '' ?>><?= htmlspecialchars($inst['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">&nbsp;</label>
                    <div class="form-check mt-2">
                        <input type="checkbox" name="consultation_enabled" value="1" class="form-check-input" id="consultCheck"
                               <?= ($m['consultation_enabled'] ?? 0) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="consultCheck">상담소견 기능 활성화</label>
                    </div>
                </div>
                <div class="col-12">
                    <label class="form-label">메모</label>
                    <textarea name="memo" class="form-control" rows="2"><?= htmlspecialchars($m['memo'] ?? '') ?></textarea>
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-accent"><i class="bi bi-check-lg"></i> <?= $isEdit ? '수정' : '등록' ?></button>
                <a href="index.php?route=member/list" class="btn btn-outline-secondary">취소</a>
            </div>
        </form>
    </div>
</div>
