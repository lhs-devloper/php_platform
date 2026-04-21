<!-- 템플릿 오버라이드 - Level 3 -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <p class="text-muted mb-0">특정 페이지의 HTML 템플릿을 직접 편집하여 완전히 다른 디자인을 적용할 수 있습니다.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="index.php?route=theme/settings" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-palette me-1"></i> 색상/브랜딩
        </a>
        <a href="index.php?route=theme/layout" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-layout-split me-1"></i> 레이아웃
        </a>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent border-0 py-3">
        <h6 class="m-0 fw-bold text-dark"><i class="bi bi-code-slash me-2"></i>오버라이드 가능한 뷰</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">뷰 경로</th>
                        <th>상태</th>
                        <th>마지막 수정</th>
                        <th class="text-end pe-4">작업</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($overridableViews as $vp): ?>
                    <?php $override = isset($overrideMap[$vp]) ? $overrideMap[$vp] : null; ?>
                    <tr>
                        <td class="ps-4">
                            <code class="text-dark fw-bold"><?= htmlspecialchars($vp) ?></code>
                        </td>
                        <td>
                            <?php if ($override): ?>
                                <?php if ($override['is_active']): ?>
                                    <span class="badge bg-accent">커스텀 활성</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">커스텀 비활성</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="badge bg-light text-muted border">기본 템플릿</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-muted small">
                            <?php if ($override): ?>
                                <?= htmlspecialchars($override['updated_at']) ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td class="text-end pe-4">
                            <div class="d-flex gap-1 justify-content-end">
                                <a href="index.php?route=theme/template_edit&view_path=<?= urlencode($vp) ?>"
                                   class="btn btn-sm btn-outline-accent">
                                    <i class="bi bi-pencil me-1"></i> 편집
                                </a>
                                <?php if ($override): ?>
                                    <form method="POST" action="index.php?route=theme/template_toggle" class="d-inline">
                                        <input type="hidden" name="_csrf" value="<?= Auth::generateCsrfToken() ?>">
                                        <input type="hidden" name="id" value="<?= $override['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-secondary"
                                                title="<?= $override['is_active'] ? '비활성화' : '활성화' ?>">
                                            <i class="bi bi-toggle-<?= $override['is_active'] ? 'on' : 'off' ?>"></i>
                                        </button>
                                    </form>
                                    <form method="POST" action="index.php?route=theme/template_delete" class="d-inline"
                                          onsubmit="return confirm('이 템플릿 오버라이드를 삭제하고 기본 뷰로 복원하시겠습니까?');">
                                        <input type="hidden" name="_csrf" value="<?= Auth::generateCsrfToken() ?>">
                                        <input type="hidden" name="view_path" value="<?= htmlspecialchars($vp) ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="삭제">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Mustache 문법 가이드 -->
<div class="card border-0 shadow-sm mt-4">
    <div class="card-header bg-transparent border-0 py-3">
        <h6 class="m-0 fw-bold text-dark"><i class="bi bi-book me-2"></i>템플릿 문법 가이드</h6>
    </div>
    <div class="card-body">
        <div class="row g-4">
            <div class="col-md-6">
                <h6 class="fw-bold small text-uppercase text-muted mb-2">변수 출력</h6>
                <table class="table table-sm small">
                    <tr>
                        <td><code>{{변수명}}</code></td>
                        <td>HTML 이스케이프 출력</td>
                    </tr>
                    <tr>
                        <td><code>{{{변수명}}}</code></td>
                        <td>HTML 그대로 출력 (raw)</td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <h6 class="fw-bold small text-uppercase text-muted mb-2">조건/반복</h6>
                <table class="table table-sm small">
                    <tr>
                        <td><code>{{#if 변수}}...{{/if}}</code></td>
                        <td>조건부 블록</td>
                    </tr>
                    <tr>
                        <td><code>{{#each 배열}}...{{/each}}</code></td>
                        <td>반복 ({{@index}}, {{필드명}})</td>
                    </tr>
                </table>
            </div>
        </div>
        <div class="alert alert-warning small mt-3 mb-0">
            <i class="bi bi-shield-check me-1"></i>
            <strong>보안 안내:</strong> PHP 코드(<code>&lt;?php&gt;</code>)와 JavaScript(<code>&lt;script&gt;</code>)는 자동으로 제거됩니다.
            HTML과 Mustache 문법만 사용할 수 있습니다.
        </div>
    </div>
</div>
