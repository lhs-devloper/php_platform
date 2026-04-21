<!-- 필터 바 -->
<div class="filter-bar shadow-sm mb-4">
    <form method="GET" class="row g-3 align-items-end">
        <input type="hidden" name="route" value="member/list">
        <div class="col-md-3">
            <label class="form-label small fw-bold text-accent">회원 검색</label>
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                <input type="text" name="search" class="form-control form-control-sm border-start-0 ps-0" 
                       placeholder="이름 또는 전화번호" value="<?= htmlspecialchars($keyword) ?>">
            </div>
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-bold text-accent">상태</label>
            <select name="status" class="form-select form-select-sm">
                <option value="">전체 상태</option>
                <?php foreach (['ACTIVE'=>'수강','PAUSED'=>'휴원','HONORARY'=>'명예','WITHDRAWN'=>'퇴원'] as $v=>$l): ?>
                <option value="<?= $v ?>" <?= $status === $v ? 'selected' : '' ?>><?= $l ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-bold text-accent">수강반</label>
            <select name="class_code_id" class="form-select form-select-sm">
                <option value="">전체 반</option>
                <?php foreach ($classCodes as $cc): ?>
                <option value="<?= $cc['id'] ?>" <?= $classCodeId == $cc['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cc['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-bold text-accent">담당강사</label>
            <select name="instructor_id" class="form-select form-select-sm">
                <option value="">전체 강사</option>
                <?php foreach ($instructors as $inst): ?>
                <option value="<?= $inst['id'] ?>" <?= $instructorId == $inst['id'] ? 'selected' : '' ?>><?= htmlspecialchars($inst['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3 text-end">
            <button type="submit" class="btn btn-sm btn-accent px-3">검색</button>
            <a href="index.php?route=member/list" class="btn btn-sm btn-outline-secondary px-3">초기화</a>
            <?php if (Auth::hasRole(['SUPER','ADMIN'])): ?>
            <a href="index.php?route=member/create" class="btn btn-sm btn-accent px-3 ms-2">
                <i class="bi bi-person-plus"></i> 등록
            </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- 회원 목록 테이블 -->
<div class="card border-0 shadow-sm overflow-hidden">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-muted">
                    <tr>
                        <th class="ps-4">이름</th>
                        <th>전화번호</th>
                        <th>성별</th>
                        <th>수강반</th>
                        <th>담당강사</th>
                        <th>상태</th>
                        <th class="text-center">상담소견</th>
                        <th class="text-end pe-4">등록일</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($members)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-5">등록된 회원이 없습니다.</td></tr>
                <?php else: foreach ($members as $m): ?>
                    <tr style="cursor: pointer;" onclick="location.href='index.php?route=member/detail&id=<?= $m['id'] ?>'">
                        <td class="ps-4 fw-bold text-dark"><?= htmlspecialchars($m['name']) ?></td>
                        <td><small class="text-muted"><?= htmlspecialchars($m['phone'] ?: '-') ?></small></td>
                        <td><?= genderBadge($m['gender']) ?></td>
                        <td><span class="badge bg-light text-dark border fw-normal"><?= htmlspecialchars($m['class_name'] ?: '미지정') ?></span></td>
                        <td><small class="fw-semibold text-secondary"><?= htmlspecialchars($m['instructor_name'] ?: '-') ?></small></td>
                        <td><?= statusBadge($m['status']) ?></td>
                        <td class="text-center">
                            <?php if ($m['consultation_enabled']): ?>
                                <i class="bi bi-chat-dots-fill text-success" title="상담소견 사용중"></i>
                            <?php else: ?>
                                <i class="bi bi-chat-dots text-muted opacity-25"></i>
                            <?php endif; ?>
                        </td>
                        <td class="text-end pe-4 text-muted small"><?= substr($m['created_at'], 0, 10) ?></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
$baseUrl = 'index.php?route=member/list&search=' . urlencode($keyword) . '&status=' . urlencode($status)
         . '&class_code_id=' . urlencode($classCodeId) . '&instructor_id=' . urlencode($instructorId);
include BASE_PATH . '/views/components/pagination.php';
?>
