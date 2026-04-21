<?php $e = $email; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-1">
            <?= htmlspecialchars($e['subject']) ?>
            <?php if ($e['status'] === 'SENT'): ?>
                <span class="badge bg-success">발송 완료</span>
            <?php elseif ($e['status'] === 'FAILED'): ?>
                <span class="badge bg-danger">실패</span>
            <?php else: ?>
                <span class="badge bg-warning text-dark">대기</span>
            <?php endif; ?>
        </h5>
        <small class="text-muted">
            발송자: <?= htmlspecialchars($e['admin_name'] ?: '-') ?> |
            발송일: <?= $e['sent_at'] ?: $e['created_at'] ?>
        </small>
    </div>
    <div class="d-flex gap-2">
        <?php if (Auth::hasRole(['SUPER_ADMIN','ADMIN','OPERATOR'])): ?>
        <form method="POST" action="index.php?route=email/resend" style="display:inline;">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>">
            <input type="hidden" name="id" value="<?= $e['id'] ?>">
            <button type="submit" class="btn btn-sm btn-outline-primary" onclick="return confirm('이 이메일을 재발송하시겠습니까?')">
                <i class="bi bi-arrow-repeat"></i> 재발송
            </button>
        </form>
        <?php endif; ?>
        <?php if (Auth::hasRole(['SUPER_ADMIN','ADMIN'])): ?>
        <button class="btn btn-sm btn-outline-danger"
                data-confirm="이 이메일 로그를 삭제하시겠습니까?"
                data-confirm-title="이메일 로그 삭제"
                data-action="index.php?route=email/delete"
                data-id="<?= $e['id'] ?>">
            <i class="bi bi-trash"></i> 삭제
        </button>
        <?php endif; ?>
        <a href="index.php?route=email/list" class="btn btn-sm btn-outline-secondary">목록</a>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-12">
                <div class="d-flex border-bottom pb-2 mb-2">
                    <strong class="text-muted me-3" style="min-width:80px;">수신자 (To)</strong>
                    <span><?= htmlspecialchars($e['to_email']) ?></span>
                </div>
            </div>
            <?php if (!empty($e['cc_email'])): ?>
            <div class="col-md-12">
                <div class="d-flex border-bottom pb-2 mb-2">
                    <strong class="text-muted me-3" style="min-width:80px;">참조 (CC)</strong>
                    <span><?= htmlspecialchars($e['cc_email']) ?></span>
                </div>
            </div>
            <?php endif; ?>
            <?php if (!empty($e['bcc_email'])): ?>
            <div class="col-md-12">
                <div class="d-flex border-bottom pb-2 mb-2">
                    <strong class="text-muted me-3" style="min-width:80px;">숨은참조 (BCC)</strong>
                    <span><?= htmlspecialchars($e['bcc_email']) ?></span>
                </div>
            </div>
            <?php endif; ?>
            <?php if ($e['status'] === 'FAILED' && !empty($e['error_message'])): ?>
            <div class="col-md-12">
                <div class="alert alert-danger mb-0">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    <strong>오류:</strong> <?= htmlspecialchars($e['error_message']) ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white">
        <strong>본문 내용</strong>
    </div>
    <div class="card-body">
        <div class="border rounded p-4 bg-white" style="min-height:200px;">
            <?= $e['body_html'] ?>
        </div>
    </div>
</div>

<?php include BASE_PATH . '/views/components/modal_confirm.php'; ?>
