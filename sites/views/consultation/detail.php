<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">
        <i class="bi bi-chat-dots"></i> 상담 소견 상세
        <?php if (!empty($consultation['is_ai_generated'])): ?>
            <span class="badge bg-purple"><i class="bi bi-robot"></i> AI 생성</span>
        <?php endif; ?>
    </h5>
    <a href="index.php?route=member/detail&id=<?= $consultation['member_id'] ?>&tab=consultation" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> 목록으로
    </a>
</div>

<!-- 회원/상담 정보 -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <div class="row">
            <div class="col-md-3">
                <small class="text-muted">회원</small><br>
                <strong><?= htmlspecialchars($member['name']) ?></strong>
            </div>
            <div class="col-md-3">
                <small class="text-muted">분석 유형</small><br>
                <?php if ($consultation['service_type'] === 'POSTURE'): ?>
                    <span class="badge bg-primary">자세분석</span>
                <?php elseif ($consultation['service_type'] === 'FOOT'): ?>
                    <span class="badge bg-info text-dark">족부분석</span>
                <?php else: ?>
                    <span class="badge bg-success">통합</span>
                <?php endif; ?>
            </div>
            <div class="col-md-3">
                <small class="text-muted">작성자</small><br>
                <?= htmlspecialchars($consultation['writer_name']) ?>
            </div>
            <div class="col-md-3">
                <small class="text-muted">상담일</small><br>
                <?= substr($consultation['consulted_at'], 0, 16) ?>
            </div>
        </div>
    </div>
</div>

<!-- ─── 근거 데이터 (분석 이미지) ─── -->
<?php
$hasAnyReport = $curPostureReport || $prevPostureReport || $curFootReport || $prevFootReport;

$postureImgCols = [
    'front_user_img' => '정면', 'side_right_user_img' => '우측면',
    'side_left_user_img' => '좌측면', 'back_user_img' => '뒷면',
    'skeleton_current_front_img' => '현재 정면 스켈레톤', 'skeleton_current_side_img' => '현재 측면 스켈레톤',
    'skeleton_future_front_img' => '미래 정면 스켈레톤', 'skeleton_future_side_img' => '미래 측면 스켈레톤',
    'fhp_img' => 'FHP', 'face_asymmetry_img' => '얼굴비대칭',
    'left_genu_varus_img' => 'O/X 좌', 'right_genu_varus_img' => 'O/X 우',
];

$footImgCols = [
    'footprint_img' => '족문', 'heatmap_img' => '히트맵',
    'left_footprint_img' => '왼발 측정', 'right_footprint_img' => '오른발 측정',
    'hallux_valgus_left_img' => '무지외반 좌', 'hallux_valgus_right_img' => '무지외반 우',
    'orthotic_img' => '오소틱',
];

// 이미지 렌더링 헬퍼
function renderImgGrid($report, $imgCols, $urlFunc) {
    $html = '<div class="row g-2">';
    $hasAny = false;
    foreach ($imgCols as $col => $label) {
        if (!empty($report[$col])) {
            $hasAny = true;
            $url = $urlFunc($report[$col]);
            $html .= '<div class="col-md-2 col-4 text-center">';
            $html .= '<a href="' . htmlspecialchars($url) . '" target="_blank">';
            $html .= '<img src="' . htmlspecialchars($url) . '" class="img-fluid rounded border" style="max-height:120px;" alt="' . $label . '">';
            $html .= '</a>';
            $html .= '<small class="text-muted d-block mt-1">' . $label . '</small>';
            $html .= '</div>';
        }
    }
    $html .= '</div>';
    return $hasAny ? $html : '<p class="text-muted small mb-0">이미지 없음</p>';
}

if ($hasAnyReport):
?>
<h6 class="mb-3"><i class="bi bi-images"></i> 근거 데이터</h6>

<?php // ─── 자세분석: 이전 → 이후 ─── ?>
<?php if ($curPostureReport || $prevPostureReport): ?>
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-bold"><span class="badge bg-primary">자세분석</span> 이미지</div>
    <div class="card-body">
        <div class="row">
            <?php if ($prevPostureReport): ?>
            <div class="<?= $curPostureReport ? 'col-md-6' : 'col-12' ?> <?= $curPostureReport ? 'border-end' : '' ?>">
                <h6 class="text-center mb-2">
                    <span class="badge bg-secondary">이전</span>
                    <small class="text-muted"><?= $prevPostureSession ? substr($prevPostureSession['captured_at'], 0, 10) : '' ?></small>
                </h6>
                <?= renderImgGrid($prevPostureReport, $postureImgCols, 'postureImgUrl') ?>
            </div>
            <?php endif; ?>
            <?php if ($curPostureReport): ?>
            <div class="<?= $prevPostureReport ? 'col-md-6' : 'col-12' ?>">
                <h6 class="text-center mb-2">
                    <span class="badge bg-primary">이후</span>
                    <small class="text-muted"><?= $curPostureSession ? substr($curPostureSession['captured_at'], 0, 10) : '' ?></small>
                </h6>
                <?= renderImgGrid($curPostureReport, $postureImgCols, 'postureImgUrl') ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<?php // ─── 족부분석: 이전 → 이후 ─── ?>
<?php if ($curFootReport || $prevFootReport): ?>
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-bold"><span class="badge bg-info text-dark">족부분석</span> 이미지</div>
    <div class="card-body">
        <div class="row">
            <?php if ($prevFootReport): ?>
            <div class="<?= $curFootReport ? 'col-md-6' : 'col-12' ?> <?= $curFootReport ? 'border-end' : '' ?>">
                <h6 class="text-center mb-2">
                    <span class="badge bg-secondary">이전</span>
                    <small class="text-muted"><?= $prevFootSession ? substr($prevFootSession['captured_at'], 0, 10) : '' ?></small>
                </h6>
                <?= renderImgGrid($prevFootReport, $footImgCols, 'footImgUrl') ?>
            </div>
            <?php endif; ?>
            <?php if ($curFootReport): ?>
            <div class="<?= $prevFootReport ? 'col-md-6' : 'col-12' ?>">
                <h6 class="text-center mb-2">
                    <span class="badge bg-info text-dark">이후</span>
                    <small class="text-muted"><?= $curFootSession ? substr($curFootSession['captured_at'], 0, 10) : '' ?></small>
                </h6>
                <?= renderImgGrid($curFootReport, $footImgCols, 'footImgUrl') ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<?php endif; ?>

<!-- ─── 소견 내용 ─── -->
<h6 class="mb-3 mt-4"><i class="bi bi-file-text"></i> 상담 소견</h6>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-bold">종합 소견</div>
    <div class="card-body">
        <p class="mb-0" style="white-space:pre-line;"><?= htmlspecialchars($consultation['overall_assessment']) ?></p>
    </div>
</div>

<?php if ($consultation['improvement_note']): ?>
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-bold text-success"><i class="bi bi-graph-up-arrow"></i> 개선된 점</div>
    <div class="card-body">
        <p class="mb-0" style="white-space:pre-line;"><?= htmlspecialchars($consultation['improvement_note']) ?></p>
    </div>
</div>
<?php endif; ?>

<?php if ($consultation['concern_note']): ?>
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-bold text-warning"><i class="bi bi-exclamation-triangle"></i> 우려/주의 사항</div>
    <div class="card-body">
        <p class="mb-0" style="white-space:pre-line;"><?= htmlspecialchars($consultation['concern_note']) ?></p>
    </div>
</div>
<?php endif; ?>

<?php if ($consultation['recommendation']): ?>
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-bold text-primary"><i class="bi bi-lightbulb"></i> 향후 권장 사항</div>
    <div class="card-body">
        <p class="mb-0" style="white-space:pre-line;"><?= htmlspecialchars($consultation['recommendation']) ?></p>
    </div>
</div>
<?php endif; ?>

<?php if ($consultation['comparison_summary']): ?>
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-bold"><i class="bi bi-arrow-left-right"></i> 전/후 비교 요약</div>
    <div class="card-body">
        <p class="mb-0" style="white-space:pre-line;"><?= htmlspecialchars($consultation['comparison_summary']) ?></p>
    </div>
</div>
<?php endif; ?>
