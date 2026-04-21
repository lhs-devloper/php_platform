<?php
// $pagination (Pagination 객체), $baseUrl (현재 필터가 포함된 URL) 필요
if (isset($pagination) && $pagination->totalPages() > 1):
?>
<div class="d-flex justify-content-between align-items-center mt-3">
    <small class="text-muted">
        총 <?= number_format($pagination->totalItems()) ?>건 / <?= $pagination->currentPage() ?> / <?= $pagination->totalPages() ?> 페이지
    </small>
    <?= $pagination->links($baseUrl) ?>
</div>
<?php endif; ?>
