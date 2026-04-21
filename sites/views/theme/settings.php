<!-- 디자인 설정 - Level 1: 브랜딩 / 색상 / 폰트 -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <p class="text-muted mb-0">사이트의 브랜딩, 색상, 폰트를 커스텀할 수 있습니다.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="index.php?route=theme/layout" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-layout-split me-1"></i> 레이아웃 설정
        </a>
        <a href="index.php?route=theme/templates" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-code-slash me-1"></i> 템플릿 오버라이드
        </a>
    </div>
</div>

<!-- 탭 네비게이션 -->
<ul class="nav nav-tabs mb-4" role="tablist">
    <li class="nav-item">
        <a class="nav-link active" data-bs-toggle="tab" href="#tab-branding" role="tab">
            <i class="bi bi-image me-1"></i> 브랜딩
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#tab-colors" role="tab">
            <i class="bi bi-palette me-1"></i> 색상
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#tab-fonts" role="tab">
            <i class="bi bi-fonts me-1"></i> 폰트
        </a>
    </li>
</ul>

<div class="tab-content">
    <!-- 브랜딩 탭 -->
    <div class="tab-pane fade show active" id="tab-branding" role="tabpanel">
        <div class="row g-4">
            <!-- 로고 업로드 -->
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent border-0 py-3">
                        <h6 class="m-0 fw-bold text-dark"><i class="bi bi-image me-2"></i>로고</h6>
                    </div>
                    <div class="card-body">
                        <?php if ($branding['logo_url']): ?>
                            <div class="mb-3 p-3 bg-light rounded text-center">
                                <img src="<?= htmlspecialchars($branding['logo_url']) ?>" alt="현재 로고" style="max-height:80px;max-width:100%;">
                            </div>
                        <?php endif; ?>
                        <form method="POST" action="index.php?route=theme/upload_logo" enctype="multipart/form-data">
                            <input type="hidden" name="_csrf" value="<?= Auth::generateCsrfToken() ?>">
                            <div class="mb-3">
                                <label class="form-label small fw-bold">로고 이미지 업로드</label>
                                <input type="file" name="logo" class="form-control" accept="image/png,image/jpeg,image/gif,image/svg+xml,image/webp">
                                <div class="form-text">PNG, JPG, GIF, SVG, WebP (최대 500KB)</div>
                            </div>
                            <button type="submit" class="btn btn-accent btn-sm">
                                <i class="bi bi-upload me-1"></i> 업로드
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- 사이트 제목 -->
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent border-0 py-3">
                        <h6 class="m-0 fw-bold text-dark"><i class="bi bi-type-h1 me-2"></i>사이트 제목</h6>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="index.php?route=theme/save_branding">
                            <input type="hidden" name="_csrf" value="<?= Auth::generateCsrfToken() ?>">
                            <div class="mb-3">
                                <label class="form-label small fw-bold">사이트 표시 이름</label>
                                <input type="text" name="site_title" class="form-control"
                                       value="<?= htmlspecialchars($branding['site_title']) ?>"
                                       placeholder="<?= htmlspecialchars(APP_NAME) ?>">
                                <div class="form-text">비워두면 기본 이름(<?= htmlspecialchars(APP_NAME) ?>)이 사용됩니다.</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">파비콘 URL</label>
                                <input type="text" name="favicon_url" class="form-control"
                                       value="<?= htmlspecialchars($branding['favicon_url']) ?>"
                                       placeholder="https://example.com/favicon.ico">
                            </div>
                            <button type="submit" class="btn btn-accent btn-sm">
                                <i class="bi bi-check-lg me-1"></i> 저장
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 색상 탭 -->
    <div class="tab-pane fade" id="tab-colors" role="tabpanel">
        <form method="POST" action="index.php?route=theme/save_colors">
            <input type="hidden" name="_csrf" value="<?= Auth::generateCsrfToken() ?>">

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent border-0 py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 fw-bold text-dark"><i class="bi bi-palette me-2"></i>색상 커스텀</h6>
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="resetColors()">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> 기본값 복원
                    </button>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <?php foreach ($colorLabels as $key => $label): ?>
                        <div class="col-lg-3 col-md-4 col-sm-6">
                            <div class="d-flex align-items-center gap-2 p-2 rounded border bg-white">
                                <input type="color" name="color_<?= $key ?>" id="color_<?= $key ?>"
                                       value="<?= htmlspecialchars($currentColors[$key]) ?>"
                                       class="form-control form-control-color border-0 p-0"
                                       style="width:40px;height:40px;cursor:pointer;"
                                       data-default="<?= htmlspecialchars($defaultColors[$key]) ?>">
                                <div class="flex-grow-1">
                                    <div class="small fw-bold text-dark"><?= htmlspecialchars($label) ?></div>
                                    <code class="text-muted" style="font-size:0.7rem;" id="hex_<?= $key ?>"><?= htmlspecialchars($currentColors[$key]) ?></code>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- 미리보기 -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent border-0 py-3">
                    <h6 class="m-0 fw-bold text-dark"><i class="bi bi-eye me-2"></i>미리보기</h6>
                </div>
                <div class="card-body" id="color-preview">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="p-3 rounded text-white" id="preview-sidebar" style="background:var(--dark);">
                                <div class="fw-bold mb-2">사이드바</div>
                                <div class="p-2 rounded mb-1" id="preview-active" style="background:var(--accent);">활성 메뉴</div>
                                <div class="p-2 rounded opacity-50">비활성 메뉴</div>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="p-3 rounded" id="preview-body" style="background:var(--body-bg);">
                                <div class="d-flex gap-2 mb-3">
                                    <button class="btn btn-sm" id="preview-btn" style="background:var(--accent);color:#fff;">버튼</button>
                                    <span class="badge" id="preview-badge-success" style="background:var(--success);color:#fff;">성공</span>
                                    <span class="badge" id="preview-badge-info" style="background:var(--info);color:#fff;">정보</span>
                                    <span class="badge" id="preview-badge-warning" style="background:var(--warning);color:#fff;">경고</span>
                                    <span class="badge" id="preview-badge-danger" style="background:var(--danger);color:#fff;">위험</span>
                                </div>
                                <div class="bg-white p-2 rounded shadow-sm" style="border-left:4px solid var(--accent);">
                                    통계 카드 미리보기
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-accent">
                    <i class="bi bi-check-lg me-1"></i> 색상 저장
                </button>
            </div>
        </form>
    </div>

    <!-- 폰트 탭 -->
    <div class="tab-pane fade" id="tab-fonts" role="tabpanel">
        <form method="POST" action="index.php?route=theme/save_fonts">
            <input type="hidden" name="_csrf" value="<?= Auth::generateCsrfToken() ?>">

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-0 py-3">
                    <h6 class="m-0 fw-bold text-dark"><i class="bi bi-fonts me-2"></i>폰트 설정</h6>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-lg-6">
                            <label class="form-label small fw-bold">폰트 패밀리</label>
                            <select name="font_family" class="form-select">
                                <option value="">Pretendard (기본)</option>
                                <option value="Noto Sans KR" <?= $fonts['font_family'] === 'Noto Sans KR' ? 'selected' : '' ?>>Noto Sans KR</option>
                                <option value="Nanum Gothic" <?= $fonts['font_family'] === 'Nanum Gothic' ? 'selected' : '' ?>>나눔고딕</option>
                                <option value="Nanum Square" <?= $fonts['font_family'] === 'Nanum Square' ? 'selected' : '' ?>>나눔스퀘어</option>
                                <option value="Spoqa Han Sans Neo" <?= $fonts['font_family'] === 'Spoqa Han Sans Neo' ? 'selected' : '' ?>>스포카 한 산스 네오</option>
                                <option value="IBM Plex Sans KR" <?= $fonts['font_family'] === 'IBM Plex Sans KR' ? 'selected' : '' ?>>IBM Plex Sans KR</option>
                            </select>
                        </div>
                        <div class="col-lg-6">
                            <label class="form-label small fw-bold">커스텀 폰트 CDN URL</label>
                            <input type="text" name="font_url" class="form-control"
                                   value="<?= htmlspecialchars($fonts['font_url']) ?>"
                                   placeholder="https://fonts.googleapis.com/css2?family=...">
                            <div class="form-text">Google Fonts 등에서 가져온 CSS URL</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end mt-3">
                <button type="submit" class="btn btn-accent">
                    <i class="bi bi-check-lg me-1"></i> 폰트 저장
                </button>
            </div>
        </form>
    </div>
</div>

<!-- 전체 초기화 -->
<div class="card border-0 shadow-sm mt-4 border-danger">
    <div class="card-body d-flex justify-content-between align-items-center">
        <div>
            <h6 class="mb-1 text-danger fw-bold"><i class="bi bi-exclamation-triangle me-1"></i> 전체 초기화</h6>
            <p class="text-muted mb-0 small">모든 디자인 설정(색상, 로고, 폰트, 레이아웃)을 기본값으로 되돌립니다.</p>
        </div>
        <form method="POST" action="index.php?route=theme/reset" onsubmit="return confirm('정말로 모든 디자인 설정을 초기화하시겠습니까?');">
            <input type="hidden" name="_csrf" value="<?= Auth::generateCsrfToken() ?>">
            <button type="submit" class="btn btn-outline-danger btn-sm">
                <i class="bi bi-arrow-counterclockwise me-1"></i> 초기화
            </button>
        </form>
    </div>
</div>

<script>
// 색상 입력 변경 시 실시간 미리보기 + hex 표시 업데이트
document.querySelectorAll('input[type="color"]').forEach(function(input) {
    input.addEventListener('input', function() {
        var key = this.name.replace('color_', '');
        var hexLabel = document.getElementById('hex_' + key);
        if (hexLabel) hexLabel.textContent = this.value;
        updatePreview();
    });
});

function updatePreview() {
    var mapping = {
        'accent': ['preview-active', 'preview-btn'],
        'dark': ['preview-sidebar'],
        'body_bg': ['preview-body'],
        'success': ['preview-badge-success'],
        'info': ['preview-badge-info'],
        'warning': ['preview-badge-warning'],
        'danger': ['preview-badge-danger']
    };

    for (var key in mapping) {
        var input = document.getElementById('color_' + key);
        if (!input) continue;
        mapping[key].forEach(function(elId) {
            var el = document.getElementById(elId);
            if (el) {
                if (elId === 'preview-body') {
                    el.style.background = input.value;
                } else {
                    el.style.background = input.value;
                }
            }
        });
    }

    // stat card border
    var accentInput = document.getElementById('color_accent');
    var statCard = document.querySelector('#preview-body .shadow-sm');
    if (accentInput && statCard) {
        statCard.style.borderLeftColor = accentInput.value;
    }
}

function resetColors() {
    if (!confirm('색상을 기본값으로 되돌리시겠습니까?')) return;
    document.querySelectorAll('input[type="color"]').forEach(function(input) {
        input.value = input.dataset.default;
        var key = input.name.replace('color_', '');
        var hexLabel = document.getElementById('hex_' + key);
        if (hexLabel) hexLabel.textContent = input.value;
    });
    updatePreview();
}
</script>
