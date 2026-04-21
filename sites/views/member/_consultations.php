<div class="d-flex justify-content-between align-items-center mb-3 px-1">
    <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-chat-dots me-1"></i> 상담 소견 이력</h6>
    <?php if (isset($aiEnabled) && $aiEnabled): ?>
        <button type="button" class="btn btn-sm btn-accent shadow-sm" data-bs-toggle="modal" data-bs-target="#aiConsultModal">
            <i class="bi bi-robot me-1"></i> AI 상담 소견 생성
        </button>
    <?php else: ?>
        <div class="text-muted small">
            <i class="bi bi-info-circle me-1"></i> AI 상담 기능은 설정 후 사용 가능합니다.
        </div>
    <?php endif; ?>
</div>

<div class="card border-0 shadow-sm overflow-hidden">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-muted small fw-bold">
                    <tr>
                        <th class="ps-4">상담일시</th>
                        <th>분석 유형</th>
                        <th>작성자</th>
                        <th>종합 소견 요약</th>
                        <th class="pe-4 text-end"></th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($consultations)): ?>
                    <tr><td colspan="5" class="text-center text-muted py-5">상담 이력이 없습니다.</td></tr>
                <?php else: foreach ($consultations as $c): ?>
                    <tr style="cursor: pointer;" onclick="location.href='index.php?route=consultation/detail&id=<?= $c['id'] ?>'">
                        <td class="ps-4 fw-semibold text-dark"><?= substr($c['consulted_at'], 0, 10) ?></td>
                        <td>
                            <?php if ($c['service_type'] === 'POSTURE'): ?>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle">자세분석</span>
                            <?php elseif ($c['service_type'] === 'FOOT'): ?>
                                <span class="badge bg-info-subtle text-info border border-info-subtle">족부분석</span>
                            <?php else: ?>
                                <span class="badge bg-success-subtle text-success border border-success-subtle">통합분석</span>
                            <?php endif; ?>
                            <?php if (!empty($c['is_ai_generated'])): ?>
                                <span class="badge bg-purple-subtle text-purple border border-purple-subtle ms-1"><i class="bi bi-robot"></i> AI</span>
                            <?php endif; ?>
                        </td>
                        <td><small class="fw-bold text-secondary"><?= htmlspecialchars($c['writer_name']) ?></small></td>
                        <td>
                            <div class="text-truncate text-muted small" style="max-width: 350px;">
                                <?= htmlspecialchars($c['overall_assessment']) ?>
                            </div>
                        </td>
                        <td class="pe-4 text-end">
                            <i class="bi bi-chevron-right text-muted opacity-50"></i>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if (isset($aiEnabled) && $aiEnabled): ?>
    <?php include BASE_PATH . '/views/member/_consultation_generate.php'; ?>
<?php endif; ?>
