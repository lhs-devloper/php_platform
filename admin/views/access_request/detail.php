<?php
$r = $request;
$scopeLabels = ['FULL' => '전체 데이터', 'REPORT_ONLY' => '리포트만', 'STATS_ONLY' => '통계만'];
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-1">열람 요청 #<?= $r['id'] ?> <?= statusBadge($r['status']) ?></h5>
    </div>
    <a href="index.php?route=access_request/list" class="btn btn-sm btn-outline-secondary">목록</a>
</div>

<div class="row g-3">
    <!-- 요청 정보 -->
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white"><h6 class="mb-0">요청 정보</h6></div>
            <div class="card-body">
                <table class="table table-borderless table-sm mb-0">
                    <tr><th style="width:120px">협력업체</th><td><?= htmlspecialchars($r['partner_name'] ?: '-') ?></td></tr>
                    <tr><th>요청자</th><td><?= htmlspecialchars($r['requester_name'] ?: '-') ?> <?= $r['requester_login_id'] ? '(' . htmlspecialchars($r['requester_login_id']) . ')' : '' ?></td></tr>
                    <tr><th>대상 가맹점</th><td>
                        <a href="index.php?route=tenant/detail&id=<?= $r['requested_tenant_id'] ?>"><?= htmlspecialchars($r['tenant_name'] ?: '-') ?></a>
                        <?= $r['tenant_status'] ? statusBadge($r['tenant_status']) : '' ?>
                    </td></tr>
                    <tr><th>열람 범위</th><td><span class="badge bg-info text-dark"><?= $scopeLabels[$r['access_scope']] ?? $r['access_scope'] ?></span></td></tr>
                    <tr><th>요청 사유</th><td><?= nl2br(htmlspecialchars($r['reason'])) ?></td></tr>
                    <tr><th>요청일</th><td><?= $r['requested_at'] ?></td></tr>
                </table>
            </div>
        </div>
    </div>

    <!-- 처리 정보 -->
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white"><h6 class="mb-0">처리 정보</h6></div>
            <div class="card-body">
                <table class="table table-borderless table-sm mb-0">
                    <tr><th style="width:120px">상태</th><td><?= statusBadge($r['status']) ?></td></tr>
                    <tr><th>처리자</th><td><?= htmlspecialchars($r['approver_name'] ?: '-') ?></td></tr>
                    <tr><th>처리일</th><td><?= $r['processed_at'] ?: '-' ?></td></tr>
                    <?php if ($r['status'] === 'APPROVED'): ?>
                    <tr><th>허용 시작</th><td><?= $r['access_start'] ?></td></tr>
                    <tr><th>허용 종료</th><td><?= $r['access_end'] ?></td></tr>
                    <?php endif; ?>
                    <?php if ($r['reject_reason']): ?>
                    <tr><th>거절 사유</th><td class="text-danger"><?= htmlspecialchars($r['reject_reason']) ?></td></tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>

        <?php if ($r['status'] === 'PENDING' && Auth::hasRole(['SUPER_ADMIN', 'ADMIN'])): ?>
        <!-- 승인 폼 -->
        <div class="card border-0 shadow-sm mt-3 border-success">
            <div class="card-header bg-white"><h6 class="mb-0 text-success"><i class="bi bi-check-circle"></i> 승인</h6></div>
            <div class="card-body">
                <form method="POST" action="index.php?route=access_request/approve">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>">
                    <input type="hidden" name="id" value="<?= $r['id'] ?>">
                    <div class="row g-2 mb-2">
                        <div class="col-md-6">
                            <label class="form-label small">허용 시작일시</label>
                            <input type="datetime-local" name="access_start" class="form-control form-control-sm"
                                   value="<?= date('Y-m-d\TH:i') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">허용 종료일시</label>
                            <input type="datetime-local" name="access_end" class="form-control form-control-sm"
                                   value="<?= date('Y-m-d\TH:i', strtotime('+30 days')) ?>" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-sm btn-success"><i class="bi bi-check-lg"></i> 승인</button>
                </form>
            </div>
        </div>

        <!-- 거절 폼 -->
        <div class="card border-0 shadow-sm mt-3 border-danger">
            <div class="card-header bg-white"><h6 class="mb-0 text-danger"><i class="bi bi-x-circle"></i> 거절</h6></div>
            <div class="card-body">
                <form method="POST" action="index.php?route=access_request/reject">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>">
                    <input type="hidden" name="id" value="<?= $r['id'] ?>">
                    <div class="mb-2">
                        <textarea name="reject_reason" class="form-control form-control-sm" rows="2" placeholder="거절 사유 (선택)"></textarea>
                    </div>
                    <button type="submit" class="btn btn-sm btn-danger"><i class="bi bi-x-lg"></i> 거절</button>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($r['status'] === 'APPROVED' && Auth::hasRole(['SUPER_ADMIN', 'ADMIN'])): ?>
        <!-- 철회 -->
        <div class="card border-0 shadow-sm mt-3">
            <div class="card-body">
                <form method="POST" action="index.php?route=access_request/revoke">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>">
                    <input type="hidden" name="id" value="<?= $r['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-outline-dark"
                            onclick="return confirm('승인된 열람 권한을 철회하시겠습니까?')">
                        <i class="bi bi-shield-x"></i> 승인 철회
                    </button>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
