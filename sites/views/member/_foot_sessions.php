<div class="compare-section">
    <div class="d-flex justify-content-between align-items-center mb-3 px-1">
        <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-clock-history me-1"></i> 족부분석 촬영 이력</h6>
        <a class="btn btn-sm btn-accent btn-compare disabled shadow-sm" href="javascript:void(0)"
           data-base-url="index.php?route=foot/compare">
            <i class="bi bi-arrow-left-right me-1"></i> 선택 세션 비교 (2개)
        </a>
    </div>

    <div class="card border-0 shadow-sm overflow-hidden">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-muted small fw-bold">
                        <tr>
                            <th class="ps-4" style="width: 50px;">선택</th>
                            <th>촬영 일시</th>
                            <th>키 / 몸무게 / BMI</th>
                            <th>상태</th>
                            <th class="text-end pe-4">리포트</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($footSessions)): ?>
                        <tr><td colspan="5" class="text-center text-muted py-5">촬영 이력이 없습니다.</td></tr>
                    <?php else: foreach ($footSessions as $fs): ?>
                        <tr>
                            <td class="ps-4">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input compare-check" value="<?= $fs['id'] ?>">
                                </div>
                            </td>
                            <td class="fw-semibold"><?= $fs['captured_at'] ?></td>
                            <td><small class="text-muted"><?= $fs['height'] ?: '-' ?>cm / <?= $fs['weight'] ?: '-' ?>kg / BMI <?= $fs['bmi'] ?: '-' ?></small></td>
                            <td><?= statusBadge($fs['status']) ?></td>
                            <td class="text-end pe-4">
                                <?php if ($fs['has_report']): ?>
                                <a href="index.php?route=foot/report&id=<?= $fs['id'] ?>" class="btn btn-xs btn-outline-accent">
                                    <i class="bi bi-file-earmark-text me-1"></i> 리포트 보기
                                </a>
                                <?php else: ?>
                                <span class="text-muted small">리포트 없음</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
