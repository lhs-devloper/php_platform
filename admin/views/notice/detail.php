<?php $n = $notice; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-1">
            <?php if ($n['is_pinned']): ?><i class="bi bi-pin-fill text-danger"></i> <?php endif; ?>
            <?= htmlspecialchars($n['title']) ?>
            <?= $n['is_published'] ? '<span class="badge bg-success">게시</span>' : '<span class="badge bg-secondary">비게시</span>' ?>
        </h5>
        <small class="text-muted">
            작성자: <?= htmlspecialchars($n['admin_name'] ?: '-') ?> |
            대상: <?php if ($n['target_type'] === 'ALL'): ?>
                <span class="badge bg-primary">전체</span>
            <?php else: ?>
                <?php
                $names = $n['tenant_names'] ?? '';
                if ($names):
                    foreach (explode(', ', $names) as $tname): ?>
                        <span class="badge bg-info text-dark me-1"><?= htmlspecialchars($tname) ?></span>
                    <?php endforeach;
                else: ?>
                    <span class="badge bg-secondary">미지정</span>
                <?php endif; ?>
            <?php endif; ?> |
            작성일: <?= $n['created_at'] ?>
            <?php if ($n['published_at']): ?> | 게시일: <?= $n['published_at'] ?><?php endif; ?>
        </small>
    </div>
    <div class="d-flex gap-2">
        <?php if (Auth::hasRole(['SUPER_ADMIN','ADMIN','OPERATOR'])): ?>
        <!-- 게시/비게시 토글 -->
        <form method="POST" action="index.php?route=notice/toggle_publish" style="display:inline;">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>">
            <input type="hidden" name="id" value="<?= $n['id'] ?>">
            <button type="submit" class="btn btn-sm <?= $n['is_published'] ? 'btn-outline-secondary' : 'btn-success' ?>">
                <i class="bi bi-<?= $n['is_published'] ? 'eye-slash' : 'eye' ?>"></i>
                <?= $n['is_published'] ? '비게시' : '게시하기' ?>
            </button>
        </form>
        <a href="index.php?route=notice/edit&id=<?= $n['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i> 수정</a>
        <?php endif; ?>
        <?php if (Auth::hasRole(['SUPER_ADMIN','ADMIN'])): ?>
        <button class="btn btn-sm btn-outline-danger"
                data-confirm="이 공지사항을 삭제하시겠습니까?"
                data-confirm-title="공지사항 삭제"
                data-action="index.php?route=notice/delete"
                data-id="<?= $n['id'] ?>">
            <i class="bi bi-trash"></i> 삭제
        </button>
        <?php endif; ?>
        <a href="index.php?route=notice/list" class="btn btn-sm btn-outline-secondary">목록</a>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="border rounded p-4 bg-white notice-content" style="min-height:200px;">
            <?= $n['content'] ?>
        </div>
    </div>
</div>

<?php include BASE_PATH . '/views/components/modal_confirm.php'; ?>
