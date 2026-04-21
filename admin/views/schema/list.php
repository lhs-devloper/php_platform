<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-database-gear"></i> 스키마 업데이트</h4>
    <?php if ($pendingCount > 0 && Auth::hasRole(['SUPER_ADMIN'])): ?>
    <form method="POST" action="index.php?route=schema/execute_all"
          onsubmit="return confirm('전체 <?= $pendingCount ?>개 테넌트에 마이그레이션을 일괄 적용합니다. 계속하시겠습니까?');">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>">
        <button type="submit" class="btn btn-danger">
            <i class="bi bi-lightning-charge"></i> 전체 일괄 적용 (<?= $pendingCount ?>개 대기)
        </button>
    </form>
    <?php endif; ?>
</div>

<!-- 요약 카드 -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <div class="fs-3 fw-bold"><?= count($migrations) ?></div>
                <small class="text-muted">마이그레이션 파일</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <div class="fs-3 fw-bold"><?= $totalTenants ?></div>
                <small class="text-muted">활성 테넌트</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <div class="fs-3 fw-bold text-success"><?= $upToDate ?></div>
                <small class="text-muted">최신 상태</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <div class="fs-3 fw-bold <?= $pendingCount > 0 ? 'text-warning' : 'text-success' ?>"><?= $pendingCount ?></div>
                <small class="text-muted">업데이트 필요</small>
            </div>
        </div>
    </div>
</div>

<!-- 마이그레이션 파일 목록 -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white fw-bold">
        <i class="bi bi-file-earmark-code"></i> 마이그레이션 파일 목록
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width:80px;">버전</th>
                    <th>마이그레이션 명칭</th>
                    <th>파일명</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($migrations)): ?>
                <tr><td colspan="3" class="text-center text-muted py-3">마이그레이션 파일이 없습니다. (migrations/ 폴더)</td></tr>
            <?php else: foreach ($migrations as $m): ?>
                <tr>
                    <td><span class="badge bg-dark"><?= htmlspecialchars($m['version']) ?></span></td>
                    <td><?= htmlspecialchars($m['name']) ?></td>
                    <td><code class="small"><?= htmlspecialchars($m['filename']) ?></code></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- 테넌트별 적용 현황 -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white fw-bold">
        <i class="bi bi-building"></i> 테넌트별 적용 현황
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>가맹점</th>
                    <th>DB명</th>
                    <th>도메인</th>
                    <th>적용 상태</th>
                    <th>미적용 버전</th>
                    <th style="width:120px;">작업</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($tenantStatus)): ?>
                <tr><td colspan="6" class="text-center text-muted py-3">활성 테넌트가 없습니다.</td></tr>
            <?php else: foreach ($tenantStatus as $t): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($t['company_name']) ?></strong></td>
                    <td><code class="small"><?= htmlspecialchars($t['db_name']) ?></code></td>
                    <td><small class="text-muted"><?= htmlspecialchars($t['domain']) ?></small></td>
                    <td>
                        <?php if ($t['up_to_date']): ?>
                            <span class="badge bg-success"><i class="bi bi-check-circle"></i> 최신</span>
                        <?php else: ?>
                            <span class="badge bg-warning text-dark"><i class="bi bi-exclamation-triangle"></i> 업데이트 필요</span>
                        <?php endif; ?>
                        <?php if (!empty($t['applied'])): ?>
                            <small class="text-muted ms-1">적용: <?= implode(', ', $t['applied']) ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!empty($t['pending'])): ?>
                            <?php foreach ($t['pending'] as $ver): ?>
                                <span class="badge bg-outline-warning border border-warning text-warning"><?= htmlspecialchars($ver) ?></span>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <span class="text-muted small">-</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!$t['up_to_date']): ?>
                        <form method="POST" action="index.php?route=schema/execute" style="display:inline;"
                              onsubmit="return confirm('<?= htmlspecialchars($t['company_name']) ?>에 마이그레이션을 적용합니다. 계속하시겠습니까?');">
                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>">
                            <input type="hidden" name="tenant_id" value="<?= $t['tenant_id'] ?>">
                            <button type="submit" class="btn btn-sm btn-primary">
                                <i class="bi bi-play-fill"></i> 적용
                            </button>
                        </form>
                        <?php else: ?>
                            <span class="text-muted small">-</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- 일괄 적용 결과 (세션에 저장된 경��) -->
<?php if (isset($_SESSION['schema_update_result'])): ?>
<?php $updateResult = $_SESSION['schema_update_result']; unset($_SESSION['schema_update_result']); ?>
<div class="card border-0 shadow-sm mt-4">
    <div class="card-header bg-white fw-bold">
        <i class="bi bi-clipboard-check"></i> 최근 일괄 적용 결과
    </div>
    <div class="card-body p-0">
        <table class="table table-sm mb-0">
            <thead class="table-light">
                <tr><th>가맹점</th><th>DB</th><th>결과</th><th>상세</th></tr>
            </thead>
            <tbody>
            <?php foreach ($updateResult['details'] as $d): ?>
                <tr>
                    <td><?= htmlspecialchars($d['company_name']) ?></td>
                    <td><code class="small"><?= htmlspecialchars($d['db_name']) ?></code></td>
                    <td>
                        <?php if ($d['result']['success']): ?>
                            <span class="badge bg-success">성공</span>
                        <?php elseif (empty($d['result']['results'])): ?>
                            <span class="badge bg-secondary">최신</span>
                        <?php else: ?>
                            <span class="badge bg-danger">실패</span>
                        <?php endif; ?>
                    </td>
                    <td><small><?= htmlspecialchars($d['result']['message']) ?></small></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
