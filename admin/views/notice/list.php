<div class="filter-bar shadow-sm mb-4">
    <form method="GET" class="row g-3 align-items-end">
        <input type="hidden" name="route" value="notice/list">
        <div class="col-md-4">
            <label class="form-label small fw-bold text-primary">검색어</label>
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                <input type="text" name="search" class="form-control form-control-sm border-start-0 ps-0" 
                       placeholder="제목 또는 내용" value="<?= htmlspecialchars($keyword) ?>">
            </div>
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-bold text-primary">대상</label>
            <select name="target_type" class="form-select form-select-sm">
                <option value="">전체</option>
                <option value="ALL" <?= $targetType === 'ALL' ? 'selected' : '' ?>>전체 공지</option>
                <option value="SPECIFIC" <?= $targetType === 'SPECIFIC' ? 'selected' : '' ?>>특정 가맹점</option>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-bold text-primary">게시 상태</label>
            <select name="is_published" class="form-select form-select-sm">
                <option value="">전체</option>
                <option value="1" <?= $published === '1' ? 'selected' : '' ?>>게시</option>
                <option value="0" <?= $published === '0' ? 'selected' : '' ?>>비게시</option>
            </select>
        </div>
        <div class="col-md-4 text-end">
            <button type="submit" class="btn btn-sm btn-primary px-3">검색</button>
            <a href="index.php?route=notice/list" class="btn btn-sm btn-outline-secondary px-3">초기화</a>
            <?php if (Auth::hasRole(['SUPER_ADMIN','ADMIN','OPERATOR'])): ?>
            <a href="index.php?route=notice/create" class="btn btn-sm btn-primary px-3 ms-2">
                <i class="bi bi-plus-lg"></i> 공지 등록
            </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="card border-0 shadow-sm overflow-hidden">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4" style="width: 50px;"></th>
                        <th>제목</th>
                        <th>대상</th>
                        <th>작성자</th>
                        <th>상태</th>
                        <th class="text-end pe-4">게시일</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($notices)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-5">공지사항이 없습니다.</td></tr>
                <?php else: foreach ($notices as $n): ?>
                    <tr>
                        <td class="ps-4">
                            <?php if ($n['is_pinned']): ?>
                                <span class="badge bg-danger p-1"><i class="bi bi-pin-fill"></i></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="index.php?route=notice/detail&id=<?= $n['id'] ?>" class="text-decoration-none fw-bold text-dark hover-primary">
                                <?= htmlspecialchars($n['title']) ?>
                            </a>
                        </td>
                        <td>
                            <?php if ($n['target_type'] === 'ALL'): ?>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle">전체</span>
                            <?php else: ?>
                                <?php
                                $names = $n['tenant_names'] ?? '';
                                if ($names):
                                    $nameArr = explode(', ', $names);
                                    $display = count($nameArr) > 2 ? $nameArr[0] . ', ' . $nameArr[1] . ' 외 ' . (count($nameArr) - 2) . '곳' : $names;
                                ?>
                                <span class="badge bg-info-subtle text-info border border-info-subtle" title="<?= htmlspecialchars($names) ?>"><?= htmlspecialchars($display) ?></span>
                                <?php else: ?>
                                <span class="badge bg-secondary">미지정</span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                        <td><small class="fw-semibold"><?= htmlspecialchars($n['admin_name'] ?: '-') ?></small></td>
                        <td>
                            <?= $n['is_published'] ? '<span class="badge bg-success">게시중</span>' : '<span class="badge bg-secondary">비게시</span>' ?>
                        </td>
                        <td class="text-end pe-4"><small class="text-muted"><?= $n['published_at'] ? substr($n['published_at'], 0, 10) : '-' ?></small></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
$baseUrl = 'index.php?route=notice/list&search=' . urlencode($keyword)
         . '&target_type=' . urlencode($targetType) . '&is_published=' . urlencode($published);
include BASE_PATH . '/views/components/pagination.php';
?>
