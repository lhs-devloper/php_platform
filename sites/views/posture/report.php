<?php $r = $report; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h6 class="mb-0"><?= htmlspecialchars($member['name']) ?> - AI자세분석 리포트</h6>
        <small class="text-muted">촬영일: <?= $session['captured_at'] ?> | 키: <?= $session['height'] ?: '-' ?>cm | 몸무게: <?= $session['weight'] ?: '-' ?>kg</small>
    </div>
    <a href="index.php?route=member/detail&id=<?= $member['id'] ?>&tab=posture" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> 돌아가기
    </a>
</div>

<?php if (!$r): ?>
<div class="alert alert-warning">이 세션에 대한 리포트가 아직 생성되지 않았습니다.</div>
<?php else: ?>

<!-- 핵심 지표 -->
<div class="row g-3 mb-4">
    <?php
    $metrics = [
        ['목 하중', $r['pcmt'], 'kg', 'bi-arrow-down-circle'],
        ['키 손실', $r['height_loss'], 'cm', 'bi-arrow-down'],
        ['근골격 지수', $r['posture_score'], '', 'bi-activity'],
        ['근골격 편차', $r['total_deviation'], '', 'bi-distribute-vertical'],
    ];
    foreach ($metrics as $m):
    ?>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm text-center py-3">
            <i class="bi <?= $m[3] ?> fs-4" style="color:var(--accent);"></i>
            <div class="fw-bold fs-4 mt-1"><?= $m[1] !== null ? $m[1] . $m[2] : '-' ?></div>
            <small class="text-muted"><?= $m[0] ?></small>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- 정면 기울기 -->
<div class="report-section">
    <div class="card border-0 shadow-sm">
        <div class="card-header"><i class="bi bi-person"></i> 정면 기울기 분석</div>
        <div class="card-body p-0">
            <table class="table table-sm mb-0">
                <thead class="table-light"><tr><th>부위</th><th>각도 (deg)</th><th>방향</th></tr></thead>
                <tbody>
                <?php
                $frontItems = [
                    ['눈', 'horizontal_eye_angle', 'horizontal_eye_direction'],
                    ['어깨', 'horizontal_shoulder_angle', 'horizontal_shoulder_direction'],
                    ['골반', 'horizontal_hip_angle', 'horizontal_hip_direction'],
                    ['무릎', 'horizontal_leg_angle', 'horizontal_leg_direction'],
                ];
                foreach ($frontItems as $fi):
                ?>
                <tr>
                    <td><?= $fi[0] ?></td>
                    <td><strong><?= $r[$fi[1]] !== null ? number_format($r[$fi[1]], 1) : '-' ?></strong></td>
                    <td><?= htmlspecialchars($r[$fi[2]] ?: '-') ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- 측면 기울기 (우측 / 좌측) -->
<div class="row g-3 report-section">
    <?php
    $sides = [
        ['우측면', [
            ['목(어깨-귀)', 'shoulder_ear_angle', 'shoulder_ear_direction'],
            ['발-어깨', 'foot_shoulder_angle', 'foot_shoulder_direction'],
            ['허리(어깨-골반)', 'shoulder_hip_angle', 'shoulder_hip_direction'],
            ['발-무릎', 'foot_leg_angle', 'foot_leg_direction'],
        ]],
        ['좌측면', [
            ['목(어깨-귀)', 'other_shoulder_ear_angle', 'other_shoulder_ear_direction'],
            ['발-어깨', 'other_foot_shoulder_angle', 'other_foot_shoulder_direction'],
            ['허리(어깨-골반)', 'other_shoulder_hip_angle', 'other_shoulder_hip_direction'],
            ['발-무릎', 'other_foot_leg_angle', 'other_foot_leg_direction'],
        ]],
    ];
    foreach ($sides as $side):
    ?>
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header"><i class="bi bi-person-standing"></i> <?= $side[0] ?> 기울기</div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead class="table-light"><tr><th>부위</th><th>각도</th><th>방향</th></tr></thead>
                    <tbody>
                    <?php foreach ($side[1] as $si): ?>
                    <tr>
                        <td><?= $si[0] ?></td>
                        <td><strong><?= $r[$si[1]] !== null ? number_format($r[$si[1]], 1) : '-' ?></strong></td>
                        <td><?= htmlspecialchars($r[$si[2]] ?: '-') ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- O/X 다리 + 백니 -->
<div class="report-section">
    <div class="card border-0 shadow-sm">
        <div class="card-header"><i class="bi bi-arrow-down-up"></i> 다리 분석</div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6>O/X 다리</h6>
                    <table class="table table-sm">
                        <tr><th>좌측</th><td><?= $r['left_genu_varus_angle'] !== null ? $r['left_genu_varus_angle'] . 'deg' : '-' ?></td><td><?= htmlspecialchars($r['left_genu_varus_direction'] ?: '-') ?></td></tr>
                        <tr><th>우측</th><td><?= $r['right_genu_varus_angle'] !== null ? $r['right_genu_varus_angle'] . 'deg' : '-' ?></td><td><?= htmlspecialchars($r['right_genu_varus_direction'] ?: '-') ?></td></tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <h6>백니 (반장슬)</h6>
                    <table class="table table-sm">
                        <tr><th>좌측</th><td><?= $r['left_back_knee'] !== null ? $r['left_back_knee'] . 'deg' : '-' ?></td></tr>
                        <tr><th>우측</th><td><?= $r['right_back_knee'] !== null ? $r['right_back_knee'] . 'deg' : '-' ?></td></tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 이미지 -->
<div class="report-section">
    <div class="card border-0 shadow-sm">
        <div class="card-header"><i class="bi bi-images"></i> 촬영 이미지</div>
        <div class="card-body">
            <h6>원본 이미지</h6>
            <div class="row g-2 mb-3">
                <?php
                $userImgs = [
                    ['정면', $r['front_user_img']],
                    ['우측면', $r['side_right_user_img']],
                    ['좌측면', $r['side_left_user_img']],
                    ['뒷면', $r['back_user_img']],
                ];
                foreach ($userImgs as $ui):
                ?>
                <div class="col-md-3 text-center">
                    <?php if ($ui[1]): ?>
                        <img src="<?= htmlspecialchars(postureImgUrl($ui[1])) ?>" class="report-img" alt="<?= $ui[0] ?>">
                    <?php else: ?>
                        <div class="report-img-placeholder"><i class="bi bi-image"></i></div>
                    <?php endif; ?>
                    <small class="text-muted d-block mt-1"><?= $ui[0] ?></small>
                </div>
                <?php endforeach; ?>
            </div>

            <h6>스켈레톤 (현재/미래)</h6>
            <div class="row g-2 mb-3">
                <?php
                $skelImgs = [
                    ['현재 정면', $r['skeleton_current_front_img']],
                    ['현재 측면', $r['skeleton_current_side_img']],
                    ['미래 정면', $r['skeleton_future_front_img']],
                    ['미래 측면', $r['skeleton_future_side_img']],
                ];
                foreach ($skelImgs as $si):
                ?>
                <div class="col-md-3 text-center">
                    <?php if ($si[1]): ?>
                        <img src="<?= htmlspecialchars(postureImgUrl($si[1])) ?>" class="report-img" alt="<?= $si[0] ?>">
                    <?php else: ?>
                        <div class="report-img-placeholder"><i class="bi bi-image"></i></div>
                    <?php endif; ?>
                    <small class="text-muted d-block mt-1"><?= $si[0] ?></small>
                </div>
                <?php endforeach; ?>
            </div>

            <h6>특수 분석 이미지</h6>
            <div class="row g-2">
                <?php
                $specialImgs = [
                    ['FHP', $r['fhp_img']],
                    ['얼굴비대칭', $r['face_asymmetry_img']],
                    ['O/X 좌', $r['left_genu_varus_img']],
                    ['O/X 우', $r['right_genu_varus_img']],
                ];
                foreach ($specialImgs as $sp):
                ?>
                <div class="col-md-3 text-center">
                    <?php if ($sp[1]): ?>
                        <img src="<?= htmlspecialchars(postureImgUrl($sp[1])) ?>" class="report-img" alt="<?= $sp[0] ?>">
                    <?php else: ?>
                        <div class="report-img-placeholder"><i class="bi bi-image"></i></div>
                    <?php endif; ?>
                    <small class="text-muted d-block mt-1"><?= $sp[0] ?></small>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php endif; ?>
