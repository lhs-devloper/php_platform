<?php $r = $report; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h6 class="mb-0"><?= htmlspecialchars($member['name']) ?> - AIoT족부분석 리포트</h6>
        <small class="text-muted">촬영일: <?= $session['captured_at'] ?> | 키: <?= $session['height'] ?: '-' ?>cm | 몸무게: <?= $session['weight'] ?: '-' ?>kg | BMI: <?= $session['bmi'] ?: '-' ?></small>
    </div>
    <a href="index.php?route=member/detail&id=<?= $member['id'] ?>&tab=foot" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> 돌아가기</a>
</div>

<?php if (!$r): ?>
<div class="alert alert-warning">이 세션에 대한 리포트가 아직 생성되지 않았습니다.</div>
<?php else: ?>

<!-- 발 형태 판정 -->
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm text-center py-3">
            <small class="text-muted">왼발</small>
            <div class="fs-4 fw-bold" style="color:var(--accent);"><?= htmlspecialchars($r['left_foot_type'] ?: '-') ?></div>
            <small>길이 <?= $r['left_foot_length'] ?: '-' ?>mm / 너비 <?= $r['left_foot_width'] ?: '-' ?>mm</small>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-0 shadow-sm text-center py-3">
            <small class="text-muted">오른발</small>
            <div class="fs-4 fw-bold" style="color:var(--accent);"><?= htmlspecialchars($r['right_foot_type'] ?: '-') ?></div>
            <small>길이 <?= $r['right_foot_length'] ?: '-' ?>mm / 너비 <?= $r['right_foot_width'] ?: '-' ?>mm</small>
        </div>
    </div>
</div>

<!-- 족압 -->
<div class="report-section">
    <div class="card border-0 shadow-sm">
        <div class="card-header"><i class="bi bi-bullseye"></i> 족압 분석</div>
        <div class="card-body p-0">
            <table class="table table-sm mb-0">
                <thead class="table-light"><tr><th>부위</th><th class="text-center">왼발</th><th class="text-center">오른발</th></tr></thead>
                <tbody>
                <tr><td>전족부</td><td class="text-center"><?= $r['left_forefoot'] ?: '-' ?></td><td class="text-center"><?= $r['right_forefoot'] ?: '-' ?></td></tr>
                <tr><td>중족부</td><td class="text-center"><?= $r['left_arch'] ?: '-' ?></td><td class="text-center"><?= $r['right_arch'] ?: '-' ?></td></tr>
                <tr><td>후족부</td><td class="text-center"><?= $r['left_heel'] ?: '-' ?></td><td class="text-center"><?= $r['right_heel'] ?: '-' ?></td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- 아치 인덱스 -->
<div class="report-section">
    <div class="card border-0 shadow-sm">
        <div class="card-header"><i class="bi bi-graph-up"></i> 아치 인덱스</div>
        <div class="card-body p-0">
            <table class="table table-sm mb-0">
                <thead class="table-light"><tr><th>지표</th><th class="text-center">왼발</th><th class="text-center">오른발</th></tr></thead>
                <tbody>
                <tr><td>Staheli Index</td><td class="text-center"><?= htmlspecialchars($r['left_staheli'] ?: '-') ?></td><td class="text-center"><?= htmlspecialchars($r['right_staheli'] ?: '-') ?></td></tr>
                <tr><td>Chippaux Index</td><td class="text-center"><?= htmlspecialchars($r['left_chippaux'] ?: '-') ?></td><td class="text-center"><?= htmlspecialchars($r['right_chippaux'] ?: '-') ?></td></tr>
                <tr><td>Clarke Angle</td><td class="text-center"><?= htmlspecialchars($r['left_clarke'] ?: '-') ?></td><td class="text-center"><?= htmlspecialchars($r['right_clarke'] ?: '-') ?></td></tr>
                <tr><td>Arch Index</td><td class="text-center"><?= htmlspecialchars($r['left_arch_index'] ?: '-') ?></td><td class="text-center"><?= htmlspecialchars($r['right_arch_index'] ?: '-') ?></td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- 무지외반증 + 골반/척추 -->
<div class="row g-3 report-section">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header"><i class="bi bi-exclamation-triangle"></i> 무지외반증</div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <tr><th>왼발</th><td><?= htmlspecialchars($r['hallux_valgus_left_angle'] ?: '-') ?></td></tr>
                    <tr><th>오른발</th><td><?= htmlspecialchars($r['hallux_valgus_right_angle'] ?: '-') ?></td></tr>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header"><i class="bi bi-align-center"></i> 골반 / 척추</div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <tr><th>골반 좌우 비율</th><td><?= htmlspecialchars($r['pelvis'] ?: '-') ?></td></tr>
                    <tr><th>척추 전후 비율</th><td><?= htmlspecialchars($r['spine'] ?: '-') ?></td></tr>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- 오소틱 추천 -->
<div class="report-section">
    <div class="card border-0 shadow-sm">
        <div class="card-header"><i class="bi bi-shoe"></i> 오소틱 추천</div>
        <div class="card-body p-0">
            <table class="table table-sm mb-0">
                <thead class="table-light"><tr><th></th><th class="text-center">왼발</th><th class="text-center">오른발</th></tr></thead>
                <tbody>
                <tr><td>추천 길이</td><td class="text-center"><?= htmlspecialchars($r['orthotic_left_length'] ?: '-') ?></td><td class="text-center"><?= htmlspecialchars($r['orthotic_right_length'] ?: '-') ?></td></tr>
                <tr><td>추천 폭</td><td class="text-center"><?= htmlspecialchars($r['orthotic_left_width'] ?: '-') ?></td><td class="text-center"><?= htmlspecialchars($r['orthotic_right_width'] ?: '-') ?></td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- 이미지 -->
<div class="report-section">
    <div class="card border-0 shadow-sm">
        <div class="card-header"><i class="bi bi-images"></i> 분석 이미지</div>
        <div class="card-body">
            <div class="row g-2">
                <?php
                $footImgs = [
                    ['족문', $r['footprint_img']],
                    ['히트맵', $r['heatmap_img']],
                    ['왼발 측정', $r['left_footprint_img']],
                    ['오른발 측정', $r['right_footprint_img']],
                    ['무지외반 좌', $r['hallux_valgus_left_img']],
                    ['무지외반 우', $r['hallux_valgus_right_img']],
                    ['오소틱', $r['orthotic_img']],
                ];
                foreach ($footImgs as $fi):
                ?>
                <div class="col-md-3 text-center">
                    <?php if ($fi[1]): ?>
                        <img src="<?= htmlspecialchars(footImgUrl($fi[1])) ?>" class="report-img" alt="<?= $fi[0] ?>">
                    <?php else: ?>
                        <div class="report-img-placeholder"><i class="bi bi-image"></i></div>
                    <?php endif; ?>
                    <small class="text-muted d-block mt-1"><?= $fi[0] ?></small>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php endif; ?>
