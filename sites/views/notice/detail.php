<?php $n = $notice; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-1">
            <?php if ($n['is_pinned']): ?><i class="bi bi-pin-fill text-danger"></i> <?php endif; ?>
            <?= htmlspecialchars($n['title']) ?>
        </h5>
        <small class="text-muted">
            게시일: <?= $n['published_at'] ?: $n['created_at'] ?>
            | <?= $n['target_type'] === 'ALL'
                ? '<span class="badge bg-primary">전체 공지</span>'
                : '<span class="badge bg-info text-dark">업체 전용</span>' ?>
        </small>
    </div>
    <a href="index.php?route=notice/list" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> 목록</a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="border rounded p-4 bg-white notice-content" style="min-height:200px;">
            <?= $n['content'] ?>
        </div>
    </div>
</div>
