<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1"><i class="bi bi-calendar-check text-accent"></i> 업데이트 예정사항</h4>
        <p class="text-muted mb-0">준비 중인 새로운 기능들을 확인하세요</p>
    </div>
    <span class="badge bg-secondary px-3 py-2"><i class="bi bi-hourglass-split me-1"></i> 준비중 <?= $totalCount ?>건</span>
</div>

<!-- 카테고리별 기능 카드 -->
<?php foreach ($categories as $catName => $catFeatures): ?>
<div class="mb-4">
    <h6 class="text-muted text-uppercase mb-3"><i class="bi bi-tag me-1"></i><?= htmlspecialchars($catName) ?></h6>
    <div class="row g-3">
        <?php foreach ($catFeatures as $feat): ?>
        <div class="col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm h-100 lab-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div class="lab-icon-wrap">
                            <i class="bi <?= $feat['icon'] ?> fs-4"></i>
                        </div>
                        <span class="badge bg-secondary">준비중</span>
                    </div>
                    <h6 class="card-title mb-1"><?= htmlspecialchars($feat['title']) ?></h6>
                    <p class="card-text text-muted small mb-3"><?= htmlspecialchars($feat['description']) ?></p>
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted"><?= htmlspecialchars($feat['version']) ?> 예정</small>
                        <button class="btn btn-sm btn-outline-secondary" disabled>
                            <i class="bi bi-lock me-1"></i> 준비중
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endforeach; ?>

<!-- 안내 배너 -->
<div class="card border-0 shadow-sm mt-2" style="background: linear-gradient(135deg, #1e293b 0%, #334155 100%);">
    <div class="card-body text-center py-4">
        <i class="bi bi-lightbulb text-warning fs-2"></i>
        <h6 class="text-white mt-2 mb-1">새로운 기능을 제안해주세요!</h6>
        <p class="text-white-50 small mb-0">가맹점 여러분의 피드백을 반영하여 업데이트가 진행됩니다.</p>
    </div>
</div>
