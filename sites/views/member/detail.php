<?php $activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'posture'; ?>

<!-- 회원 요약 정보 헤더 -->
<div class="card border-0 shadow-sm mb-4 overflow-hidden">
    <div class="bg-accent p-1"></div>
    <div class="card-body p-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="d-flex align-items-center">
                <div class="rounded-circle bg-accent-subtle d-flex align-items-center justify-content-center text-accent me-4 shadow-sm" style="width: 64px; height: 64px;">
                    <i class="bi bi-person-fill fs-2"></i>
                </div>
                <div>
                    <h4 class="mb-1 fw-bold text-dark">
                        <?= htmlspecialchars($member['name']) ?>
                        <span class="ms-2"><?= genderBadge($member['gender']) ?></span>
                        <span class="ms-1"><?= statusBadge($member['status']) ?></span>
                    </h4>
                    <div class="d-flex flex-wrap gap-3 text-muted small fw-semibold">
                        <?php if ($member['phone']): ?><span><i class="bi bi-telephone me-1"></i> <?= htmlspecialchars($member['phone']) ?></span><?php endif; ?>
                        <?php if ($member['birth_date']): ?><span><i class="bi bi-calendar me-1"></i> <?= $member['birth_date'] ?></span><?php endif; ?>
                        <?php if ($member['height']): ?><span><i class="bi bi-rulers me-1"></i> <?= $member['height'] ?>cm</span><?php endif; ?>
                        <?php if ($member['weight']): ?><span><i class="bi bi-speedometer me-1"></i> <?= $member['weight'] ?>kg</span><?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="d-flex gap-2 align-items-center">
                <?php if (Auth::hasRole(['SUPER','ADMIN'])): ?>
                <form method="POST" action="index.php?route=member/toggle_consultation" class="me-2">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>">
                    <input type="hidden" name="id" value="<?= $member['id'] ?>">
                    <div class="form-check form-switch bg-light border rounded-pill px-3 py-1 d-flex align-items-center gap-2">
                        <input class="form-check-input ms-0" type="checkbox" role="switch" id="consultSwitch" 
                               <?= $member['consultation_enabled'] ? 'checked' : '' ?> onchange="this.form.submit()">
                        <label class="form-check-label small fw-bold text-dark mt-1" for="consultSwitch">AI 상담소견</label>
                    </div>
                </form>
                <a href="index.php?route=member/edit&id=<?= $member['id'] ?>" class="btn btn-sm btn-outline-accent px-3">
                    <i class="bi bi-pencil me-1"></i> 정보 수정
                </a>
                <?php endif; ?>
                <a href="index.php?route=member/list" class="btn btn-sm btn-light border px-3">목록</a>
            </div>
        </div>
        
        <?php if ($member['class_name'] || $member['instructor_name']): ?>
        <div class="mt-3 pt-3 border-top d-flex gap-4">
            <?php if ($member['class_name']): ?>
            <div class="small">
                <span class="text-muted">소속반:</span>
                <span class="ms-1 fw-bold text-dark"><?= htmlspecialchars($member['class_name']) ?></span>
            </div>
            <?php endif; ?>
            <?php if ($member['instructor_name']): ?>
            <div class="small">
                <span class="text-muted">담당강사:</span>
                <span class="ms-1 fw-bold text-dark"><?= htmlspecialchars($member['instructor_name']) ?></span>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- 탭 메뉴 -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-0">
        <ul class="nav nav-pills nav-fill p-1 bg-light rounded-3" role="tablist">
            <li class="nav-item">
                <a class="nav-link py-3 <?= $activeTab === 'posture' ? 'active shadow-sm bg-white text-accent fw-bold' : 'text-muted opacity-75' ?>"
                   href="index.php?route=member/detail&id=<?= $member['id'] ?>&tab=posture">
                   <i class="bi bi-body-text me-1"></i> 자세분석 <span class="badge <?= $activeTab === 'posture' ? 'bg-accent' : 'bg-secondary' ?> ms-1"><?= count($postureSessions) ?></span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link py-3 <?= $activeTab === 'foot' ? 'active shadow-sm bg-white text-accent fw-bold' : 'text-muted opacity-75' ?>"
                   href="index.php?route=member/detail&id=<?= $member['id'] ?>&tab=foot">
                    <i class="bi bi-footprints me-1"></i> 족부분석 <span class="badge <?= $activeTab === 'foot' ? 'bg-accent' : 'bg-secondary' ?> ms-1"><?= count($footSessions) ?></span>
                </a>
            </li>
            <?php if ($member['consultation_enabled']): ?>
            <li class="nav-item">
                <a class="nav-link py-3 <?= $activeTab === 'consultation' ? 'active shadow-sm bg-white text-accent fw-bold' : 'text-muted opacity-75' ?>"
                   href="index.php?route=member/detail&id=<?= $member['id'] ?>&tab=consultation">
                   <i class="bi bi-chat-dots me-1"></i> 상담소견 <span class="badge <?= $activeTab === 'consultation' ? 'bg-accent' : 'bg-secondary' ?> ms-1"><?= count($consultations) ?></span>
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </div>
</div>

<div class="tab-content">
<?php if ($activeTab === 'posture'): ?>
    <?php include BASE_PATH . '/views/member/_posture_sessions.php'; ?>
<?php elseif ($activeTab === 'foot'): ?>
    <?php include BASE_PATH . '/views/member/_foot_sessions.php'; ?>
<?php elseif ($activeTab === 'consultation' && $member['consultation_enabled']): ?>
    <?php include BASE_PATH . '/views/member/_consultations.php'; ?>
<?php endif; ?>
</div>

<?php include BASE_PATH . '/views/components/modal_confirm.php'; ?>
