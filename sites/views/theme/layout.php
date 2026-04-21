<!-- 레이아웃 설정 - Level 2 -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <p class="text-muted mb-0">사이드바 위치, 레이아웃 프리셋, 탑바 스타일을 변경할 수 있습니다.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="index.php?route=theme/settings" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-palette me-1"></i> 색상/브랜딩
        </a>
        <a href="index.php?route=theme/templates" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-code-slash me-1"></i> 템플릿 오버라이드
        </a>
    </div>
</div>

<form method="POST" action="index.php?route=theme/save_layout">
    <input type="hidden" name="_csrf" value="<?= Auth::generateCsrfToken() ?>">

    <!-- 사이드바 위치 -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-transparent border-0 py-3">
            <h6 class="m-0 fw-bold text-dark"><i class="bi bi-layout-sidebar me-2"></i>사이드바 위치</h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="d-block">
                        <input type="radio" name="sidebar_position" value="left" class="btn-check" id="side-left"
                               <?= $sidebarPosition === 'left' ? 'checked' : '' ?>>
                        <label for="side-left" class="btn btn-outline-secondary w-100 p-3 text-start">
                            <div class="d-flex align-items-center gap-3">
                                <div class="d-flex border rounded" style="width:80px;height:50px;overflow:hidden;">
                                    <div style="width:25%;background:var(--dark);"></div>
                                    <div style="width:75%;background:#f0f0f0;"></div>
                                </div>
                                <div>
                                    <div class="fw-bold">왼쪽 (기본)</div>
                                    <small class="text-muted">사이드바가 왼쪽에 고정</small>
                                </div>
                            </div>
                        </label>
                    </label>
                </div>
                <div class="col-md-6">
                    <label class="d-block">
                        <input type="radio" name="sidebar_position" value="right" class="btn-check" id="side-right"
                               <?= $sidebarPosition === 'right' ? 'checked' : '' ?>>
                        <label for="side-right" class="btn btn-outline-secondary w-100 p-3 text-start">
                            <div class="d-flex align-items-center gap-3">
                                <div class="d-flex border rounded" style="width:80px;height:50px;overflow:hidden;">
                                    <div style="width:75%;background:#f0f0f0;"></div>
                                    <div style="width:25%;background:var(--dark);"></div>
                                </div>
                                <div>
                                    <div class="fw-bold">오른쪽</div>
                                    <small class="text-muted">사이드바가 오른쪽에 고정</small>
                                </div>
                            </div>
                        </label>
                    </label>
                </div>
            </div>
        </div>
    </div>

    <!-- 레이아웃 프리셋 -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-transparent border-0 py-3">
            <h6 class="m-0 fw-bold text-dark"><i class="bi bi-grid-1x2 me-2"></i>레이아웃 프리셋</h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <input type="radio" name="layout_preset" value="default" class="btn-check" id="preset-default"
                           <?= $layoutPreset === 'default' ? 'checked' : '' ?>>
                    <label for="preset-default" class="btn btn-outline-secondary w-100 p-3">
                        <div class="mb-2">
                            <div class="border rounded mx-auto" style="width:100px;height:60px;position:relative;">
                                <div style="position:absolute;left:0;top:0;width:30%;height:100%;background:var(--dark);"></div>
                                <div style="position:absolute;left:30%;top:0;width:70%;height:20%;background:#e0e0e0;border-bottom:1px solid #ccc;"></div>
                                <div style="position:absolute;left:35%;top:25%;width:25%;height:30%;background:#f0f0f0;border:1px solid #ddd;border-radius:3px;"></div>
                                <div style="position:absolute;left:65%;top:25%;width:25%;height:30%;background:#f0f0f0;border:1px solid #ddd;border-radius:3px;"></div>
                            </div>
                        </div>
                        <div class="fw-bold">기본</div>
                        <small class="text-muted">표준 관리자 레이아웃</small>
                    </label>
                </div>
                <div class="col-md-4">
                    <input type="radio" name="layout_preset" value="compact" class="btn-check" id="preset-compact"
                           <?= $layoutPreset === 'compact' ? 'checked' : '' ?>>
                    <label for="preset-compact" class="btn btn-outline-secondary w-100 p-3">
                        <div class="mb-2">
                            <div class="border rounded mx-auto" style="width:100px;height:60px;position:relative;">
                                <div style="position:absolute;left:0;top:0;width:20%;height:100%;background:var(--dark);"></div>
                                <div style="position:absolute;left:20%;top:0;width:80%;height:15%;background:#e0e0e0;border-bottom:1px solid #ccc;"></div>
                                <div style="position:absolute;left:23%;top:18%;width:22%;height:25%;background:#f0f0f0;border:1px solid #ddd;border-radius:2px;"></div>
                                <div style="position:absolute;left:48%;top:18%;width:22%;height:25%;background:#f0f0f0;border:1px solid #ddd;border-radius:2px;"></div>
                                <div style="position:absolute;left:73%;top:18%;width:22%;height:25%;background:#f0f0f0;border:1px solid #ddd;border-radius:2px;"></div>
                            </div>
                        </div>
                        <div class="fw-bold">컴팩트</div>
                        <small class="text-muted">좁은 사이드바, 밀집 배치</small>
                    </label>
                </div>
                <div class="col-md-4">
                    <input type="radio" name="layout_preset" value="wide" class="btn-check" id="preset-wide"
                           <?= $layoutPreset === 'wide' ? 'checked' : '' ?>>
                    <label for="preset-wide" class="btn btn-outline-secondary w-100 p-3">
                        <div class="mb-2">
                            <div class="border rounded mx-auto" style="width:100px;height:60px;position:relative;">
                                <div style="position:absolute;left:0;top:0;width:25%;height:100%;background:var(--dark);"></div>
                                <div style="position:absolute;left:25%;top:0;width:75%;height:20%;background:#e0e0e0;border-bottom:1px solid #ccc;"></div>
                                <div style="position:absolute;left:28%;top:25%;width:67%;height:35%;background:#f0f0f0;border:1px solid #ddd;border-radius:3px;"></div>
                            </div>
                        </div>
                        <div class="fw-bold">와이드</div>
                        <small class="text-muted">넓은 콘텐츠 영역</small>
                    </label>
                </div>
            </div>
        </div>
    </div>

    <!-- 탑바 스타일 -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-transparent border-0 py-3">
            <h6 class="m-0 fw-bold text-dark"><i class="bi bi-window me-2"></i>탑바 스타일</h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <input type="radio" name="topbar_style" value="default" class="btn-check" id="topbar-default"
                           <?= $topbarStyle === 'default' ? 'checked' : '' ?>>
                    <label for="topbar-default" class="btn btn-outline-secondary w-100 p-3">
                        <div class="p-2 bg-white border rounded mb-2 text-center small">흰색 배경</div>
                        <div class="fw-bold">기본</div>
                    </label>
                </div>
                <div class="col-md-4">
                    <input type="radio" name="topbar_style" value="colored" class="btn-check" id="topbar-colored"
                           <?= $topbarStyle === 'colored' ? 'checked' : '' ?>>
                    <label for="topbar-colored" class="btn btn-outline-secondary w-100 p-3">
                        <div class="p-2 rounded mb-2 text-center small text-white" style="background:var(--accent);">메인 색상</div>
                        <div class="fw-bold">컬러</div>
                    </label>
                </div>
                <div class="col-md-4">
                    <input type="radio" name="topbar_style" value="dark" class="btn-check" id="topbar-dark"
                           <?= $topbarStyle === 'dark' ? 'checked' : '' ?>>
                    <label for="topbar-dark" class="btn btn-outline-secondary w-100 p-3">
                        <div class="p-2 rounded mb-2 text-center small text-white" style="background:var(--dark);">다크</div>
                        <div class="fw-bold">다크</div>
                    </label>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end">
        <button type="submit" class="btn btn-accent">
            <i class="bi bi-check-lg me-1"></i> 레이아웃 저장
        </button>
    </div>
</form>
