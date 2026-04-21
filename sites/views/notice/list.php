<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr><th style="width:40px;"></th><th>제목</th><th>대상</th><th>게시일</th></tr>
            </thead>
            <tbody>
            <?php if (empty($notices)): ?>
                <tr><td colspan="4" class="text-center text-muted py-4">공지사항이 없습니다.</td></tr>
            <?php else: foreach ($notices as $n): ?>
                <tr>
                    <td><?= $n['is_pinned'] ? '<i class="bi bi-pin-fill text-danger"></i>' : '' ?></td>
                    <td>
                        <a href="index.php?route=notice/detail&id=<?= $n['id'] ?>" class="text-decoration-none fw-semibold">
                            <?= htmlspecialchars($n['title']) ?>
                        </a>
                        <?php
                        // 7일 이내 새 공지 표시
                        $pubDate = strtotime($n['published_at'] ?: $n['created_at']);
                        if ($pubDate > strtotime('-7 days')):
                        ?>
                            <span class="badge bg-danger ms-1">NEW</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?= $n['target_type'] === 'ALL'
                            ? '<span class="badge bg-primary">전체</span>'
                            : '<span class="badge bg-info text-dark">우리 업체</span>' ?>
                    </td>
                    <td><small><?= $n['published_at'] ? substr($n['published_at'], 0, 10) : substr($n['created_at'], 0, 10) ?></small></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$baseUrl = 'index.php?route=notice/list';
include BASE_PATH . '/views/components/pagination.php';
?>
