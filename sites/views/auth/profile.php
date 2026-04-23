<?php $a = $admin; ?>
<div class="row g-4">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-4">
                <div class="rounded-circle bg-accent-subtle text-accent d-inline-flex align-items-center justify-content-center mb-3"
                     style="width:72px; height:72px;">
                    <i class="bi bi-person-fill fs-1"></i>
                </div>
                <h5 class="fw-bold mb-1"><?= htmlspecialchars($a['name']) ?></h5>
                <p class="text-muted small mb-3"><?= htmlspecialchars($a['login_id']) ?></p>
                <div class="mb-2">
                    <?= roleBadge($a['role']) ?>
                </div>
                <?php if (!empty($a['last_login_at'])): ?>
                <div class="small text-muted">
                    <i class="bi bi-clock-history"></i>
                    마지막 로그인: <?= htmlspecialchars($a['last_login_at']) ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card border-0 shadow-sm mt-4">
            <div class="card-body">
                <h6 class="fw-bold mb-3">
                    <i class="bi bi-info-circle me-2 text-accent"></i> 계정 안내
                </h6>
                <p class="small text-muted mb-2">
                    <strong>웹 관리자 계정</strong>: 본 관리자 페이지 로그인용 계정입니다.
                </p>
                <p class="small text-muted mb-0">
                    <strong>앱 계정</strong>: 전용 앱 로그인용 별도 계정입니다. 아이디는 변경할 수 없으며,
                    비밀번호만 재설정 가능합니다.
                </p>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <!-- 웹 관리자 비밀번호 변경 -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-0 pt-3 pb-0">
                <h6 class="fw-bold mb-0">
                    <i class="bi bi-shield-lock me-2 text-accent"></i> 웹 관리자 비밀번호 변경
                </h6>
                <small class="text-muted">관리자 페이지 로그인에 사용</small>
            </div>
            <div class="card-body">
                <form method="POST" action="index.php?route=auth/update_password" autocomplete="off">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-muted">아이디</label>
                            <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($a['login_id']) ?>" disabled>
                            <small class="text-muted">아이디는 변경할 수 없습니다.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-muted">현재 비밀번호 <span class="text-danger">*</span></label>
                            <input type="password" name="current_password" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-muted">새 비밀번호 <span class="text-danger">*</span></label>
                            <input type="password" name="new_password" class="form-control" required
                                   minlength="6" placeholder="6자 이상">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-muted">새 비밀번호 확인 <span class="text-danger">*</span></label>
                            <input type="password" name="confirm_password" class="form-control" required minlength="6">
                        </div>
                    </div>
                    <div class="mt-3">
                        <button type="submit" class="btn btn-accent">
                            <i class="bi bi-check-lg"></i> 웹 비밀번호 변경
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 앱 비밀번호 변경 -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 pt-3 pb-0">
                <h6 class="fw-bold mb-0">
                    <i class="bi bi-phone me-2 text-accent"></i> 앱 비밀번호 변경
                </h6>
                <small class="text-muted">앱(모바일) 로그인용 별도 계정</small>
            </div>
            <div class="card-body">
                <?php if (empty($appId)): ?>
                <div class="alert alert-warning mb-0">
                    <i class="bi bi-exclamation-triangle"></i>
                    앱 계정이 아직 발급되지 않았습니다. 본사 관리자에게 문의해주세요.
                </div>
                <?php else: ?>
                <form method="POST" action="index.php?route=auth/update_app_password" autocomplete="off">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-muted">앱 아이디</label>
                            <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($appId) ?>" disabled>
                            <small class="text-muted">본사에서 발급된 ID는 변경할 수 없습니다.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-muted">현재 웹 비밀번호 <span class="text-danger">*</span></label>
                            <input type="password" name="current_password" class="form-control" required
                                   placeholder="본인 확인용 (웹 관리자 비밀번호)">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-muted">새 앱 비밀번호 <span class="text-danger">*</span></label>
                            <input type="password" name="new_app_password" class="form-control" required
                                   minlength="6" placeholder="6자 이상">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-muted">새 앱 비밀번호 확인 <span class="text-danger">*</span></label>
                            <input type="password" name="confirm_app_password" class="form-control" required minlength="6">
                        </div>
                    </div>
                    <div class="mt-3">
                        <button type="submit" class="btn btn-accent">
                            <i class="bi bi-check-lg"></i> 앱 비밀번호 변경
                        </button>
                    </div>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
