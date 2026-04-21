<?php
$migrationResult = isset($_SESSION['migration_result']) ? $_SESSION['migration_result'] : null;
unset($_SESSION['migration_result']);
?>

<?php if ($database): ?>
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <table class="table table-borderless mb-0">
            <tr><th style="width:180px">DB 호스트</th><td><code><?= htmlspecialchars($database['db_host']) ?>:<?= $database['db_port'] ?></code></td></tr>
            <tr><th>DB명</th><td><code><?= htmlspecialchars($database['db_name']) ?></code></td></tr>
            <tr><th>DB 계정</th><td><code><?= htmlspecialchars($database['db_user']) ?></code></td></tr>
            <tr>
                <th>서비스 도메인</th>
                <td>
                    <?php if ($database['domain']): ?>
                        <a href="http://<?= htmlspecialchars($database['domain']) ?>" target="_blank">
                            <?= htmlspecialchars($database['domain']) ?> <i class="bi bi-box-arrow-up-right"></i>
                        </a>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </td>
            </tr>
            <tr><th>스키마 버전</th><td><span class="badge bg-info text-dark"><?= htmlspecialchars($database['db_version']) ?></span></td></tr>
            <tr><th>상태</th><td><?= statusBadge($database['status']) ?></td></tr>
            <tr><th>최초 배포일</th><td><?= $database['provisioned_at'] ?: '-' ?></td></tr>
            <tr><th>마지막 마이그레이션</th><td><?= $database['last_migration_at'] ?: '-' ?></td></tr>
            <tr><th>마지막 헬스체크</th><td><?= $database['last_health_check_at'] ?: '-' ?></td></tr>
        </table>
    </div>
</div>

<!-- 마이그레이션 결과 표시 -->
<?php if ($migrationResult): ?>
<div class="card border-0 shadow-sm mt-3">
    <div class="card-header bg-<?= $migrationResult['success'] ? 'success' : 'danger' ?> text-white">
        <i class="bi bi-<?= $migrationResult['success'] ? 'check-circle' : 'x-circle' ?>"></i>
        마이그레이션 <?= $migrationResult['success'] ? '완료' : '실패' ?>
    </div>
    <div class="card-body">
        <?php if (!empty($migrationResult['counts'])): ?>
        <div class="row g-2 mb-3">
            <?php foreach ($migrationResult['counts'] as $table => $count): ?>
            <div class="col-auto">
                <span class="badge bg-light text-dark border px-3 py-2">
                    <?= htmlspecialchars($table) ?>: <strong><?= number_format($count) ?></strong>건
                </span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <details <?= $migrationResult['success'] ? '' : 'open' ?>>
            <summary class="text-muted small mb-2" style="cursor:pointer;">실행 로그 보기</summary>
            <div class="bg-dark text-light rounded p-3" style="font-family:monospace; font-size:0.8rem; max-height:300px; overflow-y:auto;">
                <?php foreach ($migrationResult['log'] as $line): ?>
                <div><?= htmlspecialchars($line) ?></div>
                <?php endforeach; ?>
            </div>
        </details>
    </div>
</div>
<?php endif; ?>

<!-- 레거시 데이터 마이그레이션 -->
<?php if (Auth::hasRole(['SUPER_ADMIN', 'ADMIN'])): ?>
<div class="card border-0 shadow-sm mt-3">
    <div class="card-header">
        <i class="bi bi-database-gear"></i> 레거시 데이터 마이그레이션
    </div>
    <div class="card-body">
        <p class="text-muted small mb-3">
            기존 시스템의 SQL 덤프 파일(.sql)을 업로드하면 자동으로 데이터를 변환하여 이관합니다.<br>
            <strong>이관 대상:</strong> 회원(members), 수강반(b_classcode), AI자세분석(members_ai_report), AIoT족부분석(record_footprint)
        </p>
        <form method="POST" action="index.php?route=tenant/migrate" enctype="multipart/form-data"
              onsubmit="return confirm('레거시 데이터를 마이그레이션합니다.\n기존 프로비저닝된 신규 DB에 데이터가 추가됩니다.\n\n계속하시겠습니까?');">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>">
            <input type="hidden" name="id" value="<?= $tenant['id'] ?>">
            <div class="d-flex gap-2 align-items-end">
                <div class="flex-grow-1">
                    <label class="form-label small mb-1">SQL 덤프 파일</label>
                    <input type="file" name="sql_file" class="form-control form-control-sm" accept=".sql" required>
                </div>
                <button type="submit" class="btn btn-warning btn-sm">
                    <i class="bi bi-arrow-repeat"></i> 마이그레이션 실행
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php else: ?>
<div class="card border-0 shadow-sm">
    <div class="card-body text-center py-4">
        <i class="bi bi-database-x fs-1 text-muted"></i>
        <p class="mt-2 text-muted">배포된 DB 인스턴스가 없습니다.</p>
        <?php if (Auth::hasRole(['SUPER_ADMIN', 'ADMIN'])): ?>
        <form method="POST" action="index.php?route=tenant/provision" class="mt-3">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>">
            <input type="hidden" name="id" value="<?= $tenant['id'] ?>">
            <div class="d-flex gap-2 justify-content-center align-items-end">
                <div>
                    <label class="form-label small mb-1">서브도메인 (슬러그)</label>
                    <div class="input-group input-group-sm" style="width:300px;">
                        <input type="text" name="slug" class="form-control" placeholder="smartidea"
                               pattern="[a-z0-9\-]+" required>
                        <span class="input-group-text"><?= PROVISION_DOMAIN_SUFFIX ?></span>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="bi bi-database-add"></i> DB 프로비저닝 실행
                </button>
            </div>
        </form>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>
