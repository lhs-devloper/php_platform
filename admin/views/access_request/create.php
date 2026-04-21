<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="bi bi-shield-plus me-1"></i> 가맹점 열람 요청</h6>
            </div>
            <div class="card-body">
                <div class="alert alert-info small mb-4">
                    <i class="bi bi-info-circle me-1"></i>
                    열람 요청을 제출하면 중앙관리자의 승인 후 해당 가맹점 정보를 열람할 수 있습니다.
                    승인 시 열람 기간이 설정됩니다.
                </div>

                <form method="POST" action="index.php?route=access_request/create">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>">

                    <div class="mb-3">
                        <label class="form-label">대상 가맹점 <span class="text-danger">*</span></label>
                        <select name="tenant_id" class="form-select" required>
                            <option value="">-- 가맹점 선택 --</option>
                            <?php foreach ($tenants as $t): ?>
                            <option value="<?= $t['id'] ?>">
                                <?= htmlspecialchars($t['company_name']) ?>
                                (<?= $t['status'] === 'ACTIVE' ? '운영중' : $t['status'] ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">열람 범위</label>
                        <select name="access_scope" class="form-select">
                            <option value="FULL">전체 데이터</option>
                            <option value="REPORT_ONLY" selected>리포트만</option>
                            <option value="STATS_ONLY">통계만</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">요청 사유 <span class="text-danger">*</span></label>
                        <textarea name="reason" class="form-control" rows="4" placeholder="열람이 필요한 사유를 입력해주세요." required></textarea>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-send me-1"></i> 요청 제출
                        </button>
                        <a href="index.php?route=access_request/list" class="btn btn-outline-secondary">취소</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
