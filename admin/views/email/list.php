<div class="filter-bar shadow-sm mb-4">
    <form method="GET" class="row g-3 align-items-end">
        <input type="hidden" name="route" value="email/list">
        <div class="col-md-4">
            <label class="form-label small fw-bold text-primary">검색어</label>
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                <input type="text" name="search" class="form-control form-control-sm border-start-0 ps-0"
                       placeholder="제목 또는 수신자" value="<?= htmlspecialchars($keyword) ?>">
            </div>
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-bold text-primary">발송 상태</label>
            <select name="status" class="form-select form-select-sm">
                <option value="">전체</option>
                <option value="SENT" <?= $status === 'SENT' ? 'selected' : '' ?>>발송 완료</option>
                <option value="FAILED" <?= $status === 'FAILED' ? 'selected' : '' ?>>발송 실패</option>
                <option value="PENDING" <?= $status === 'PENDING' ? 'selected' : '' ?>>대기</option>
            </select>
        </div>
        <div class="col-md-6 text-end">
            <button type="submit" class="btn btn-sm btn-primary px-3">검색</button>
            <a href="index.php?route=email/list" class="btn btn-sm btn-outline-secondary px-3">초기화</a>
            <?php if (Auth::hasRole(['SUPER_ADMIN','ADMIN','OPERATOR'])): ?>
            <a href="index.php?route=email/compose" class="btn btn-sm btn-primary px-3 ms-2">
                <i class="bi bi-envelope-plus"></i> 이메일 작성
            </a>
            <?php endif; ?>
            <?php if (Auth::hasRole(['SUPER_ADMIN','ADMIN'])): ?>
            <a href="index.php?route=email/settings" class="btn btn-sm btn-outline-info px-3 ms-1" title="SMTP 설정">
                <i class="bi bi-gear"></i> SMTP 설정
            </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<?php if (!$smtpConfigured): ?>
<div class="alert alert-warning mb-4">
    <i class="bi bi-exclamation-triangle me-1"></i>
    <strong>SMTP 미설정:</strong>
    <a href="index.php?route=email/settings">SMTP 설정</a>에서 메일 서버 정보를 입력해야 이메일을 발송할 수 있습니다.
</div>
<?php endif; ?>

<div class="card border-0 shadow-sm overflow-hidden">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4" style="width:50px;">ID</th>
                        <th>제목</th>
                        <th>수신자</th>
                        <th>발송자</th>
                        <th>상태</th>
                        <th class="text-end pe-4">발송일</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($emails)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-5">발송 이력이 없습니다.</td></tr>
                <?php else: foreach ($emails as $e): ?>
                    <tr>
                        <td class="ps-4"><small class="text-muted">#<?= $e['id'] ?></small></td>
                        <td>
                            <a href="index.php?route=email/detail&id=<?= $e['id'] ?>" class="text-decoration-none fw-bold text-dark hover-primary">
                                <?= htmlspecialchars(mb_strimwidth($e['subject'], 0, 50, '...')) ?>
                            </a>
                        </td>
                        <td>
                            <small><?= htmlspecialchars(mb_strimwidth($e['to_email'], 0, 40, '...')) ?></small>
                        </td>
                        <td><small class="fw-semibold"><?= htmlspecialchars($e['admin_name'] ?: '-') ?></small></td>
                        <td>
                            <?php if ($e['status'] === 'SENT'): ?>
                                <span class="badge bg-success">발송 완료</span>
                            <?php elseif ($e['status'] === 'FAILED'): ?>
                                <span class="badge bg-danger">실패</span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark">대기</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end pe-4">
                            <small class="text-muted"><?= $e['sent_at'] ? substr($e['sent_at'], 0, 16) : ($e['created_at'] ? substr($e['created_at'], 0, 16) : '-') ?></small>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
$baseUrl = 'index.php?route=email/list&search=' . urlencode($keyword) . '&status=' . urlencode($status);
include BASE_PATH . '/views/components/pagination.php';
?>
