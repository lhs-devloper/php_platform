<!-- 수강반 등록/수정 폼 -->
<div class="mb-4">
    <a href="index.php?route=class_code/list" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> 목록으로
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-0 py-3">
                <h6 class="m-0 fw-bold text-dark">
                    <i class="bi bi-<?= $isEdit ? 'pencil' : 'plus-lg' ?> me-2"></i>
                    <?= $isEdit ? '수강반 수정' : '수강반 등록' ?>
                </h6>
            </div>
            <div class="card-body">
                <form method="POST" action="index.php?route=class_code/<?= $isEdit ? 'edit&id=' . $item['id'] : 'create' ?>">
                    <input type="hidden" name="_csrf" value="<?= Auth::generateCsrfToken() ?>">

                    <div class="mb-3">
                        <label class="form-label small fw-bold">코드 <span class="text-danger">*</span></label>
                        <input type="text" name="code" class="form-control"
                               value="<?= htmlspecialchars($item['code']) ?>"
                               maxlength="20" required
                               placeholder="예: Class 1, BEGINNER, KIDS">
                        <div class="form-text">영문/숫자/공백 조합 권장 (최대 20자)</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">수강반명 <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control"
                               value="<?= htmlspecialchars($item['name']) ?>"
                               maxlength="100" required
                               placeholder="예: 초급 그룹, 필라테스 키즈반">
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-bold">상태</label>
                        <div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="is_active" id="active1" value="1"
                                       <?= $item['is_active'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="active1">
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">활성</span>
                                </label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="is_active" id="active0" value="0"
                                       <?= !$item['is_active'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="active0">
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">비활성</span>
                                </label>
                            </div>
                        </div>
                        <div class="form-text">비활성화 시 회원 등록/수정 시 선택 목록에서 숨겨집니다.</div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="index.php?route=class_code/list" class="btn btn-outline-secondary">취소</a>
                        <button type="submit" class="btn btn-accent">
                            <i class="bi bi-check-lg me-1"></i> <?= $isEdit ? '수정' : '등록' ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
