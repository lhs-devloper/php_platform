<?php
// 서비스 타입 기반 표시 제어
$showPosture = in_array(TENANT_SERVICE_TYPE, ['POSTURE', 'BOTH'], true);
$showFoot    = in_array(TENANT_SERVICE_TYPE, ['FOOT', 'BOTH'], true);
$isBoth      = TENANT_SERVICE_TYPE === 'BOTH';
?>
<!-- 대시보드 요약 -->
<div class="row g-4 mb-4">
    <!-- 전체 회원 -->
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card h-100 py-2 shadow-sm">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="stat-label text-primary">전체 회원</div>
                        <div class="stat-value"><?= number_format($memberStats['total']) ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-people fs-2 text-slate-300 opacity-25"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2"><?= $memberStats['ACTIVE'] ?> 수강</span>
                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 ms-1"><?= $memberStats['PAUSED'] ?> 휴원</span>
                </div>
            </div>
        </div>
    </div>

    <!-- 오늘 분석 건수 -->
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card h-100 py-2 shadow-sm" style="border-left-color: var(--info);">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="stat-label text-info">오늘의 분석</div>
                        <div class="stat-value">
                            <?php if ($isBoth): ?>
                                <?= $todayCounts['total'] ?>
                            <?php elseif ($showPosture): ?>
                                <?= $todayCounts['posture'] ?>
                            <?php else: ?>
                                <?= $todayCounts['foot'] ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-graph-up-arrow fs-2 text-slate-300 opacity-25"></i>
                    </div>
                </div>
                <div class="mt-3 text-muted small fw-semibold">
                    <?php if ($showPosture): ?>
                        <i class="bi bi-body-text me-1"></i> 자세 <?= $todayCounts['posture'] ?>
                    <?php endif; ?>
                    <?php if ($isBoth): ?> / <?php endif; ?>
                    <?php if ($showFoot): ?>
                        <i class="bi bi-footprints <?= $isBoth ? 'ms-2' : '' ?> me-1"></i> 족부 <?= $todayCounts['foot'] ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- AI 상담 자동화 대상 -->
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card h-100 py-2 shadow-sm" style="border-left-color: var(--warning);">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="stat-label text-warning">AI 상담 활성 회원</div>
                        <div class="stat-value"><?= $consultationCnt ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-robot fs-2 text-slate-300 opacity-25"></i>
                    </div>
                </div>
                <div class="mt-3 text-muted small fw-semibold">
                    스마트 상담 소견 자동 생성 가능
                </div>
            </div>
        </div>
    </div>

    <!-- 바로가기 액션 -->
    <div class="col-xl-3 col-md-6">
        <div class="card h-100 border-0 bg-accent bg-gradient shadow-sm">
            <div class="card-body d-flex flex-column justify-content-center align-items-center text-white">
                <div class="small fw-bold mb-3 opacity-75">QUICK START</div>
                <div class="d-flex gap-2">
                    <a href="index.php?route=member/create" class="btn btn-sm btn-light text-accent shadow-sm fw-bold">
                        <i class="bi bi-person-plus-fill me-1"></i> 신규 회원 등록
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <?php if ($showPosture): ?>
    <!-- 최근 자세분석 -->
    <div class="<?= $isBoth ? 'col-lg-6' : 'col-12' ?>">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-0 py-3 d-flex align-items-center">
                <div class="bg-primary-subtle p-2 rounded-3 me-3 text-primary">
                    <i class="bi bi-body-text fs-5"></i>
                </div>
                <h6 class="m-0 font-weight-bold text-dark flex-grow-1">최근 자세분석 촬영</h6>
                <a href="index.php?route=member/list" class="btn btn-xs btn-link text-decoration-none p-0 small">전체보기</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size: 0.875rem;">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">회원명</th>
                                <th>상태</th>
                                <th class="text-end pe-4">촬영일시</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($recentPosture)): ?>
                            <tr><td colspan="3" class="text-center text-muted py-5">데이터가 없습니다.</td></tr>
                        <?php else: foreach ($recentPosture as $ps): ?>
                            <tr style="cursor: pointer;" onclick="location.href='index.php?route=posture/report&id=<?= $ps['id'] ?>'">
                                <td class="ps-4 fw-bold text-dark"><?= htmlspecialchars($ps['member_name']) ?></td>
                                <td><?= statusBadge($ps['status']) ?></td>
                                <td class="text-end pe-4 text-muted small"><?= substr($ps['captured_at'], 5, 11) ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($showFoot): ?>
    <!-- 최근 족부분석 -->
    <div class="<?= $isBoth ? 'col-lg-6' : 'col-12' ?>">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-0 py-3 d-flex align-items-center">
                <div class="bg-info-subtle p-2 rounded-3 me-3 text-info">
                    <i class="bi bi-footprints fs-5"></i>
                </div>
                <h6 class="m-0 font-weight-bold text-dark flex-grow-1">최근 족부분석 촬영</h6>
                <a href="index.php?route=member/list" class="btn btn-xs btn-link text-decoration-none p-0 small">전체보기</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size: 0.875rem;">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">회원명</th>
                                <th>상태</th>
                                <th class="text-end pe-4">촬영일시</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($recentFoot)): ?>
                            <tr><td colspan="3" class="text-center text-muted py-5">데이터가 없습니다.</td></tr>
                        <?php else: foreach ($recentFoot as $fs): ?>
                            <tr style="cursor: pointer;" onclick="location.href='index.php?route=foot/report&id=<?= $fs['id'] ?>'">
                                <td class="ps-4 fw-bold text-dark"><?= htmlspecialchars($fs['member_name']) ?></td>
                                <td><?= statusBadge($fs['status']) ?></td>
                                <td class="text-end pe-4 text-muted small"><?= substr($fs['captured_at'], 5, 11) ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
