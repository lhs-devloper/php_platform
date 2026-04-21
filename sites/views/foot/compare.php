<?php $a = $reportA; $b = $reportB; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h6><?= htmlspecialchars($member['name']) ?> - 족부분석 전/후 비교</h6>
    <a href="index.php?route=member/detail&id=<?= $member['id'] ?>&tab=foot" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> 돌아가기</a>
</div>

<?php if (!$a || !$b): ?>
<div class="alert alert-warning">비교할 리포트가 부족합니다.</div>
<?php else: ?>

<!-- 세션 정보 -->
<div class="row g-3 mb-4">
    <div class="col-md-6"><div class="card border-0 shadow-sm"><div class="card-body text-center py-2">
        <span class="badge bg-secondary">BEFORE</span> <?= $sessionA['captured_at'] ?>
    </div></div></div>
    <div class="col-md-6"><div class="card border-0 shadow-sm"><div class="card-body text-center py-2">
        <span class="badge bg-primary">AFTER</span> <?= $sessionB['captured_at'] ?>
    </div></div></div>
</div>

<!-- 발 형태 비교 -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header"><i class="bi bi-footprints"></i> 발 형태 비교</div>
    <div class="card-body p-0">
        <table class="table table-sm mb-0">
            <thead class="table-light"><tr><th></th><th class="text-center">Before 왼발</th><th class="text-center">After 왼발</th><th class="text-center">Before 오른발</th><th class="text-center">After 오른발</th></tr></thead>
            <tbody>
            <tr><td>발 형태</td>
                <td class="text-center"><?= htmlspecialchars($a['left_foot_type'] ?: '-') ?></td>
                <td class="text-center fw-bold"><?= htmlspecialchars($b['left_foot_type'] ?: '-') ?></td>
                <td class="text-center"><?= htmlspecialchars($a['right_foot_type'] ?: '-') ?></td>
                <td class="text-center fw-bold"><?= htmlspecialchars($b['right_foot_type'] ?: '-') ?></td>
            </tr>
            <tr><td>발 길이</td>
                <td class="text-center"><?= $a['left_foot_length'] ?: '-' ?></td>
                <td class="text-center"><?= $b['left_foot_length'] ?: '-' ?></td>
                <td class="text-center"><?= $a['right_foot_length'] ?: '-' ?></td>
                <td class="text-center"><?= $b['right_foot_length'] ?: '-' ?></td>
            </tr>
            <tr><td>발 너비</td>
                <td class="text-center"><?= $a['left_foot_width'] ?: '-' ?></td>
                <td class="text-center"><?= $b['left_foot_width'] ?: '-' ?></td>
                <td class="text-center"><?= $a['right_foot_width'] ?: '-' ?></td>
                <td class="text-center"><?= $b['right_foot_width'] ?: '-' ?></td>
            </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- 아치 인덱스 비교 -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header"><i class="bi bi-graph-up"></i> 아치 인덱스 비교</div>
    <div class="card-body p-0">
        <table class="table table-sm mb-0">
            <thead class="table-light"><tr><th>지표</th><th class="text-center">Before 왼발</th><th class="text-center">After 왼발</th><th class="text-center">Before 오른발</th><th class="text-center">After 오른발</th></tr></thead>
            <tbody>
            <?php
            $indices = [
                ['Staheli', 'left_staheli', 'right_staheli'],
                ['Chippaux', 'left_chippaux', 'right_chippaux'],
                ['Clarke', 'left_clarke', 'right_clarke'],
                ['Arch Index', 'left_arch_index', 'right_arch_index'],
            ];
            foreach ($indices as $idx):
            ?>
            <tr>
                <td><?= $idx[0] ?></td>
                <td class="text-center"><?= htmlspecialchars($a[$idx[1]] ?: '-') ?></td>
                <td class="text-center fw-bold"><?= htmlspecialchars($b[$idx[1]] ?: '-') ?></td>
                <td class="text-center"><?= htmlspecialchars($a[$idx[2]] ?: '-') ?></td>
                <td class="text-center fw-bold"><?= htmlspecialchars($b[$idx[2]] ?: '-') ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- 이미지 비교 -->
<div class="card border-0 shadow-sm">
    <div class="card-header"><i class="bi bi-images"></i> 이미지 비교</div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6 text-center">
                <small class="text-muted">Before - 히트맵</small><br>
                <?php if ($a['heatmap_img']): ?>
                    <img src="<?= htmlspecialchars(footImgUrl($a['heatmap_img'])) ?>" class="report-img" style="max-height:250px;">
                <?php else: ?><div class="report-img-placeholder"><i class="bi bi-image"></i></div><?php endif; ?>
            </div>
            <div class="col-md-6 text-center">
                <small class="text-muted">After - 히트맵</small><br>
                <?php if ($b['heatmap_img']): ?>
                    <img src="<?= htmlspecialchars(footImgUrl($b['heatmap_img'])) ?>" class="report-img" style="max-height:250px;">
                <?php else: ?><div class="report-img-placeholder"><i class="bi bi-image"></i></div><?php endif; ?>
            </div>
        </div>
        <div class="row g-3 mt-2">
            <div class="col-md-6 text-center">
                <small class="text-muted">Before - 족문</small><br>
                <?php if ($a['footprint_img']): ?>
                    <img src="<?= htmlspecialchars(footImgUrl($a['footprint_img'])) ?>" class="report-img" style="max-height:250px;">
                <?php else: ?><div class="report-img-placeholder"><i class="bi bi-image"></i></div><?php endif; ?>
            </div>
            <div class="col-md-6 text-center">
                <small class="text-muted">After - 족문</small><br>
                <?php if ($b['footprint_img']): ?>
                    <img src="<?= htmlspecialchars(footImgUrl($b['footprint_img'])) ?>" class="report-img" style="max-height:250px;">
                <?php else: ?><div class="report-img-placeholder"><i class="bi bi-image"></i></div><?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php endif; ?>
