<!-- 수강반 관리 -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <p class="text-muted mb-0">회원에게 배정할 수강반을 등록/수정합니다.</p>
    </div>
    <?php if (Auth::hasRole(['SUPER', 'ADMIN'])): ?>
    <a href="index.php?route=class_code/create" class="btn btn-accent btn-sm">
        <i class="bi bi-plus-lg me-1"></i> 수강반 등록
    </a>
    <?php endif; ?>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-4" style="width:80px;">#</th>
                        <th style="width:150px;">코드</th>
                        <th>수강반명</th>
                        <th class="text-center" style="width:100px;">소속 회원</th>
                        <th class="text-center" style="width:100px;">상태</th>
                        <th class="text-end pe-4" style="width:200px;">작업</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($classCodes)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-5">등록된 수강반이 없습니다.</td></tr>
                <?php else: foreach ($classCodes as $c): ?>
                    <tr>
                        <td class="ps-4 text-muted"><?= $c['id'] ?></td>
                        <td><code class="text-dark fw-bold"><?= htmlspecialchars($c['code']) ?></code></td>
                        <td class="fw-bold text-dark"><?= htmlspecialchars($c['name']) ?></td>
                        <td class="text-center">
                            <?php if ($c['member_count'] > 0): ?>
                                <span class="badge bg-accent-subtle text-accent border border-accent-subtle">
                                    <?= $c['member_count'] ?>명
                                </span>
                            <?php else: ?>
                                <span class="text-muted small">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <?php if ($c['is_active']): ?>
                                <span class="badge bg-success-subtle text-success border border-success-subtle">활성</span>
                            <?php else: ?>
                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">비활성</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end pe-4">
                            <?php if (Auth::hasRole(['SUPER', 'ADMIN'])): ?>
                            <div class="d-flex gap-1 justify-content-end">
                                <a href="index.php?route=class_code/edit&id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-secondary" title="수정">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="POST" action="index.php?route=class_code/toggle" class="d-inline">
                                    <input type="hidden" name="_csrf" value="<?= Auth::generateCsrfToken() ?>">
                                    <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-secondary" title="<?= $c['is_active'] ? '비활성화' : '활성화' ?>">
                                        <i class="bi bi-toggle-<?= $c['is_active'] ? 'on' : 'off' ?>"></i>
                                    </button>
                                </form>
                                <form method="POST" action="index.php?route=class_code/delete" class="d-inline"
                                      onsubmit="return confirm('이 수강반을 삭제하시겠습니까?\n(소속 회원이 있으면 삭제할 수 없습니다)');">
                                    <input type="hidden" name="_csrf" value="<?= Auth::generateCsrfToken() ?>">
                                    <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="삭제" <?= $c['member_count'] > 0 ? 'disabled' : '' ?>>
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                            <?php else: ?>
                                <span class="text-muted small">권한 없음</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="alert alert-info mt-3 small mb-0">
    <i class="bi bi-info-circle me-1"></i>
    <strong>안내:</strong> 비활성 상태의 수강반은 회원 등록 시 선택 목록에서 숨겨지지만, 기존 회원 배정은 유지됩니다.
    삭제는 소속 회원이 0명일 때만 가능합니다.
</div>
