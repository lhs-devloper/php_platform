<!-- 대시보드 요약 -->
<div class="row g-4 mb-4">
    <!-- 전체 가맹점 -->
    <div class="col-xl-3 col-md-6">
        <div class="card border-left-primary h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1" style="font-size: 0.7rem;">
                            전체 가맹점</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($tenantStats['total']) ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-building fs-2 text-gray-300 opacity-25"></i>
                    </div>
                </div>
                <div class="mt-2">
                    <span class="text-success small fw-bold"><?= $tenantStats['ACTIVE'] ?></span> <span class="text-muted small">운영중</span>
                    <span class="text-warning small fw-bold ms-2"><?= $tenantStats['PENDING'] ?></span> <span class="text-muted small">대기</span>
                </div>
            </div>
        </div>
    </div>

    <!-- 협력업체 -->
    <div class="col-xl-3 col-md-6">
        <div class="card border-left-success h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1" style="font-size: 0.7rem;">
                            협력업체</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($partnerStats['total']) ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-people fs-2 text-gray-300 opacity-25"></i>
                    </div>
                </div>
                <div class="mt-2 text-muted small">
                    활성: <span class="fw-bold text-dark"><?= $partnerStats['ACTIVE'] ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- 열람 요청 -->
    <div class="col-xl-3 col-md-6">
        <div class="card border-left-warning h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1" style="font-size: 0.7rem;">
                            열람 요청 (대기)</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $pendingAccess ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-shield-lock fs-2 text-gray-300 opacity-25"></i>
                    </div>
                </div>
                <div class="mt-2">
                    <?php if ($pendingAccess > 0): ?>
                    <a href="index.php?route=access_request/list&status=PENDING" class="btn btn-sm btn-warning py-0 px-2 fw-bold" style="font-size: 0.7rem;">
                        처리대기 <i class="bi bi-arrow-right"></i>
                    </a>
                    <?php else: ?>
                    <span class="text-muted small">대기중인 요청 없음</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- 바로가기 -->
    <div class="col-xl-3 col-md-6">
        <div class="card border-left-info h-100 py-2 shadow-none bg-primary-subtle border-0">
            <div class="card-body d-flex flex-column justify-content-center align-items-center">
                <div class="text-primary small fw-bold mb-2">QUICK ACTION</div>
                <div class="d-flex gap-2">
                    <a href="index.php?route=tenant/create" class="btn btn-sm btn-primary shadow-sm">
                        <i class="bi bi-plus-lg"></i> 가맹점
                    </a>
                    <a href="index.php?route=partner/create" class="btn btn-sm btn-outline-primary bg-white shadow-sm">
                        <i class="bi bi-plus-lg"></i> 협력업체
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$stats = $analysisStats;
$totals = $stats['totals'];
$monthly = $stats['monthly'];

// 차트용 월별 라벨/데이터
$chartLabels = [];
$chartPosture = [];
$chartFoot = [];
for ($i = 5; $i >= 0; $i--) {
    $ym = date('Y-m', strtotime("-{$i} months"));
    $chartLabels[] = date('n월', strtotime($ym . '-01'));
    $chartPosture[] = $monthly[$ym]['posture'] ?? 0;
    $chartFoot[] = $monthly[$ym]['foot'] ?? 0;
}
?>

<!-- 분석 사용량 요약 -->
<div class="row g-4 mb-4">
    <div class="col-xl-4 col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <i class="bi bi-people fs-3 text-primary opacity-50"></i>
                <div class="h4 mb-0 fw-bold mt-1"><?= number_format($totals['members']) ?></div>
                <div class="small text-muted">전체 회원</div>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <i class="bi bi-person-standing fs-3 text-success opacity-50"></i>
                <div class="h4 mb-0 fw-bold mt-1"><?= number_format($totals['posture']) ?></div>
                <div class="small text-muted">자세분석 총 건수</div>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <i class="bi bi-fingerprint fs-3 text-info opacity-50"></i>
                <div class="h4 mb-0 fw-bold mt-1"><?= number_format($totals['foot']) ?></div>
                <div class="small text-muted">족부분석 총 건수</div>
            </div>
        </div>
    </div>
</div>

<!-- 월별 추이 차트 + 가맹점별 사용량 -->
<div class="row g-4 mb-4">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent py-3">
                <h6 class="m-0 fw-bold text-primary"><i class="bi bi-bar-chart-line me-1"></i> 월별 분석 추이 (최근 6개월)</h6>
            </div>
            <div class="card-body">
                <div style="position:relative; height:250px;">
                    <canvas id="analysisChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent py-3">
                <h6 class="m-0 fw-bold text-primary"><i class="bi bi-building me-1"></i> 가맹점별 사용량</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size: 0.85rem;">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-3">가맹점</th>
                                <th class="text-end">회원</th>
                                <th class="text-end">자세분석</th>
                                <th class="text-end pe-3">족부분석</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($stats['perTenant'])): ?>
                            <tr><td colspan="4" class="text-center text-muted py-4">활성 가맹점이 없습니다.</td></tr>
                        <?php else: foreach ($stats['perTenant'] as $pt): ?>
                            <tr>
                                <td class="ps-3 fw-semibold">
                                    <a href="index.php?route=tenant/detail&id=<?= $pt['tenant_id'] ?>" class="text-decoration-none text-dark">
                                        <?= htmlspecialchars($pt['company_name']) ?>
                                    </a>
                                    <?php if (isset($pt['error'])): ?>
                                        <i class="bi bi-exclamation-triangle text-danger" title="<?= htmlspecialchars($pt['error']) ?>"></i>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end"><?= is_numeric($pt['members']) ? number_format($pt['members']) : $pt['members'] ?></td>
                                <td class="text-end">
                                    <?php if (is_numeric($pt['posture'])): ?>
                                        <span class="fw-bold text-success"><?= number_format($pt['posture']) ?></span>
                                    <?php else: ?>
                                        <?= $pt['posture'] ?>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-3">
                                    <?php if (is_numeric($pt['foot'])): ?>
                                        <span class="fw-bold text-info"><?= number_format($pt['foot']) ?></span>
                                    <?php else: ?>
                                        <?= $pt['foot'] ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                        <?php if (!empty($stats['perTenant']) && count($stats['perTenant']) > 1): ?>
                            <tr class="table-light fw-bold">
                                <td class="ps-3">합계</td>
                                <td class="text-end"><?= number_format($totals['members']) ?></td>
                                <td class="text-end text-success"><?= number_format($totals['posture']) ?></td>
                                <td class="text-end pe-3 text-info"><?= number_format($totals['foot']) ?></td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- 최근 등록 가맹점 -->
    <div class="col-lg-6">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-transparent py-3 d-flex align-items-center">
                <i class="bi bi-building me-2"></i>
                <h6 class="m-0 font-weight-bold text-primary flex-grow-1">최근 등록 가맹점</h6>
                <a href="index.php?route=tenant/list" class="btn btn-xs btn-outline-primary border-0 py-0"><i class="bi bi-plus-lg"></i> 더보기</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size: 0.85rem;">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">업체명</th>
                                <th>서비스</th>
                                <th>상태</th>
                                <th class="text-end pe-4">등록일</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($recentTenants)): ?>
                            <tr><td colspan="4" class="text-center text-muted py-4">등록된 가맹점이 없습니다.</td></tr>
                        <?php else: ?>
                            <?php foreach ($recentTenants as $t): ?>
                            <tr>
                                <td class="ps-4 fw-bold text-dark">
                                    <a href="index.php?route=tenant/detail&id=<?= $t['id'] ?>" class="text-decoration-none text-dark hover-primary">
                                        <?= htmlspecialchars($t['company_name']) ?>
                                    </a>
                                </td>
                                <td><?= serviceTypeBadge($t['service_type']) ?></td>
                                <td><?= statusBadge($t['status']) ?></td>
                                <td class="text-end pe-4 text-muted small"><?= substr($t['created_at'], 0, 10) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- 최근 활동 로그 -->
    <div class="col-lg-6">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-transparent py-3 d-flex align-items-center">
                <i class="bi bi-activity me-2"></i>
                <h6 class="m-0 font-weight-bold text-primary">최근 활동 로그</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size: 0.85rem;">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">관리자</th>
                                <th>행위</th>
                                <th>설명</th>
                                <th class="text-end pe-4">일시</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($recentAudit)): ?>
                            <tr><td colspan="4" class="text-center text-muted py-4">활동 로그가 없습니다.</td></tr>
                        <?php else: ?>
                            <?php foreach ($recentAudit as $log): ?>
                            <tr>
                                <td class="ps-4 small fw-bold"><?= htmlspecialchars($log['admin_name'] ?: '-') ?></td>
                                <td><span class="badge bg-light text-dark border fw-normal"><?= htmlspecialchars($log['action']) ?></span></td>
                                <td><div class="text-truncate" style="max-width: 200px;" title="<?= htmlspecialchars($log['description']) ?>"><?= htmlspecialchars($log['description'] ?: '-') ?></div></td>
                                <td class="text-end pe-4 text-muted small"><?= substr($log['created_at'], 5, 11) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('analysisChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($chartLabels) ?>,
        datasets: [
            {
                label: '자세분석',
                data: <?= json_encode($chartPosture) ?>,
                backgroundColor: 'rgba(28, 200, 138, 0.7)',
                borderColor: 'rgba(28, 200, 138, 1)',
                borderWidth: 1,
                borderRadius: 4
            },
            {
                label: '족부분석',
                data: <?= json_encode($chartFoot) ?>,
                backgroundColor: 'rgba(54, 185, 204, 0.7)',
                borderColor: 'rgba(54, 185, 204, 1)',
                borderWidth: 1,
                borderRadius: 4
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'top' }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: { precision: 0 }
            }
        }
    }
});
</script>
