<?php
$a = $reportA; $b = $reportB;
function compareVal($va, $vb, $lower_is_better = true) {
    if ($va === null || $vb === null) return ['class' => 'compare-same', 'icon' => ''];
    $diff = $vb - $va;
    if (abs($diff) < 0.1) return ['class' => 'compare-same', 'icon' => '='];
    $improved = $lower_is_better ? $diff < 0 : $diff > 0;
    return [
        'class' => $improved ? 'compare-better' : 'compare-worse',
        'icon'  => $improved ? '<i class="bi bi-arrow-down-circle-fill"></i>' : '<i class="bi bi-arrow-up-circle-fill"></i>',
    ];
}
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h6><?= htmlspecialchars($member['name']) ?> - 자세분석 전/후 비교</h6>
    <a href="index.php?route=member/detail&id=<?= $member['id'] ?>&tab=posture" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> 돌아가기</a>
</div>

<?php if (!$a || !$b): ?>
<div class="alert alert-warning">비교할 리포트가 부족합니다.</div>
<?php else: ?>

<!-- 세션 정보 -->
<div class="row g-3 mb-4">
    <div class="col-md-6"><div class="card border-0 shadow-sm"><div class="card-body text-center py-2">
        <span class="badge bg-secondary">BEFORE</span> <?= $sessionA['captured_at'] ?>
        <small class="text-muted d-block">키 <?= $sessionA['height'] ?: '-' ?>cm / 몸무게 <?= $sessionA['weight'] ?: '-' ?>kg</small>
    </div></div></div>
    <div class="col-md-6"><div class="card border-0 shadow-sm"><div class="card-body text-center py-2">
        <span class="badge bg-primary">AFTER</span> <?= $sessionB['captured_at'] ?>
        <small class="text-muted d-block">키 <?= $sessionB['height'] ?: '-' ?>cm / 몸무게 <?= $sessionB['weight'] ?: '-' ?>kg</small>
    </div></div></div>
</div>

<!-- 핵심 지표 비교 -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header"><i class="bi bi-bar-chart"></i> 핵심 지표 비교</div>
    <div class="card-body p-0">
        <table class="table table-sm mb-0">
            <thead class="table-light"><tr><th>지표</th><th class="text-center">Before</th><th class="text-center">After</th><th class="text-center">변화</th></tr></thead>
            <tbody>
            <?php
            $compareItems = [
                ['목 하중 (kg)', 'pcmt', true],
                ['키 손실 (cm)', 'height_loss', true],
                ['근골격 지수', 'posture_score', false],
                ['근골격 편차', 'total_deviation', true],
                ['눈 기울기', 'horizontal_eye_angle', true],
                ['어깨 기울기', 'horizontal_shoulder_angle', true],
                ['골반 기울기', 'horizontal_hip_angle', true],
                ['무릎 기울기', 'horizontal_leg_angle', true],
            ];
            foreach ($compareItems as $ci):
                $cv = compareVal($a[$ci[1]], $b[$ci[1]], $ci[2]);
                $diff = ($a[$ci[1]] !== null && $b[$ci[1]] !== null) ? round($b[$ci[1]] - $a[$ci[1]], 2) : null;
            ?>
            <tr>
                <td><?= $ci[0] ?></td>
                <td class="text-center"><?= $a[$ci[1]] !== null ? number_format($a[$ci[1]], 1) : '-' ?></td>
                <td class="text-center"><?= $b[$ci[1]] !== null ? number_format($b[$ci[1]], 1) : '-' ?></td>
                <td class="text-center <?= $cv['class'] ?>">
                    <?= $cv['icon'] ?>
                    <?= $diff !== null ? ($diff > 0 ? '+' : '') . number_format($diff, 1) : '-' ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- 이미지 비교 (원본) -->
<div class="card border-0 shadow-sm">
    <div class="card-header"><i class="bi bi-images"></i> 이미지 비교 (정면)</div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6 text-center">
                <small class="text-muted">Before</small><br>
                <?php if ($a['front_user_img']): ?>
                    <img src="<?= htmlspecialchars(postureImgUrl($a['front_user_img'])) ?>" class="report-img" style="max-height:300px;">
                <?php else: ?><div class="report-img-placeholder"><i class="bi bi-image"></i></div><?php endif; ?>
            </div>
            <div class="col-md-6 text-center">
                <small class="text-muted">After</small><br>
                <?php if ($b['front_user_img']): ?>
                    <img src="<?= htmlspecialchars(postureImgUrl($b['front_user_img'])) ?>" class="report-img" style="max-height:300px;">
                <?php else: ?><div class="report-img-placeholder"><i class="bi bi-image"></i></div><?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php endif; ?>
