<!-- CodeMirror CDN -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/codemirror.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/theme/dracula.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/addon/hint/show-hint.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/addon/fold/foldgutter.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/addon/dialog/dialog.min.css">
<style>
    .CodeMirror { border-radius: 0 0 0.75rem 0.75rem; font-size: 0.82rem; line-height: 1.6; }
    .cm-html-editor .CodeMirror { height: 480px; }
    .cm-css-editor .CodeMirror { height: 180px; }
    .CodeMirror-gutters { border-right: 1px solid #3a3a5c; }
    /* Mustache 구문 하이라이트 */
    .cm-mustache { color: #50fa7b !important; font-weight: bold; }
</style>

<!-- 템플릿 편집 - Level 3 -->
<div class="mb-4">
    <a href="index.php?route=theme/templates" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> 목록으로
    </a>
</div>

<form method="POST" action="index.php?route=theme/template_save" id="template-form">
    <input type="hidden" name="_csrf" value="<?= Auth::generateCsrfToken() ?>">
    <input type="hidden" name="view_path" value="<?= htmlspecialchars($viewPath) ?>">

    <div class="row g-4">
        <!-- 에디터 영역 -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-0 py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 fw-bold text-dark">
                        <i class="bi bi-code-slash me-2"></i>HTML 템플릿
                    </h6>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="loadDefault()">
                            <i class="bi bi-file-code me-1"></i> 기본 소스 불러오기
                        </button>
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="changeFontSize(-1)" title="글자 축소">
                                <i class="bi bi-dash"></i>
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="changeFontSize(1)" title="글자 확대">
                                <i class="bi bi-plus"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0 cm-html-editor">
                    <textarea name="html_content" id="html-editor"><?= htmlspecialchars($existing ? $existing['html_content'] : '') ?></textarea>
                </div>
            </div>

            <!-- CSS 에디터 -->
            <div class="card border-0 shadow-sm mt-3">
                <div class="card-header bg-transparent border-0 py-3">
                    <h6 class="m-0 fw-bold text-dark">
                        <i class="bi bi-filetype-css me-2"></i>스코프 CSS <span class="text-muted small fw-normal">(선택)</span>
                    </h6>
                </div>
                <div class="card-body p-0 cm-css-editor">
                    <textarea name="css_content" id="css-editor"><?= htmlspecialchars($existing ? ($existing['css_content'] ?? '') : '') ?></textarea>
                </div>
            </div>

            <div class="d-flex justify-content-between mt-3">
                <div class="text-muted small d-flex align-items-center gap-3">
                    <span><kbd>Ctrl+S</kbd> 저장</span>
                    <span><kbd>Ctrl+F</kbd> 검색</span>
                    <span><kbd>Ctrl+H</kbd> 바꾸기</span>
                    <span><kbd>Ctrl+/</kbd> 주석</span>
                </div>
                <div class="d-flex gap-2">
                    <a href="index.php?route=theme/templates" class="btn btn-outline-secondary">취소</a>
                    <button type="button" class="btn btn-outline-info" onclick="runPreview()">
                        <i class="bi bi-eye me-1"></i> 미리보기
                    </button>
                    <button type="submit" class="btn btn-accent">
                        <i class="bi bi-check-lg me-1"></i> 저장
                    </button>
                </div>
            </div>
        </div>

        <!-- 사이드 패널 -->
        <div class="col-lg-4">
            <!-- 사용 가능 변수 -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-transparent border-0 py-3">
                    <h6 class="m-0 fw-bold text-dark"><i class="bi bi-braces me-2"></i>사용 가능 변수</h6>
                </div>
                <div class="card-body p-0">
                    <?php if (!empty($variables)): ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($variables as $varName => $varDesc): ?>
                        <div class="list-group-item py-2 px-3" style="cursor:pointer;" onclick="insertVariable('<?= htmlspecialchars($varName) ?>')" title="클릭하면 에디터에 삽입됩니다">
                            <code class="text-accent small">{{<?= htmlspecialchars($varName) ?>}}</code>
                            <i class="bi bi-box-arrow-in-down-left float-end text-muted" style="font-size:0.7rem;"></i>
                            <div class="text-muted" style="font-size:0.75rem;"><?= htmlspecialchars($varDesc) ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="p-3 text-muted small">이 뷰에 대한 변수 문서가 없습니다.</div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 스니펫 -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-transparent border-0 py-3">
                    <h6 class="m-0 fw-bold text-dark"><i class="bi bi-lightning me-2"></i>빠른 삽입</h6>
                </div>
                <div class="card-body p-2">
                    <div class="d-flex flex-wrap gap-1">
                        <button type="button" class="btn btn-outline-secondary btn-sm" style="font-size:0.75rem;" onclick="insertSnippet('if')">
                            #if 블록
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" style="font-size:0.75rem;" onclick="insertSnippet('each')">
                            #each 반복
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" style="font-size:0.75rem;" onclick="insertSnippet('card')">
                            카드
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" style="font-size:0.75rem;" onclick="insertSnippet('table')">
                            테이블
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" style="font-size:0.75rem;" onclick="insertSnippet('row')">
                            Bootstrap Row
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" style="font-size:0.75rem;" onclick="insertSnippet('stat')">
                            통계 카드
                        </button>
                    </div>
                </div>
            </div>

            <!-- 상태 정보 -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-0 py-3">
                    <h6 class="m-0 fw-bold text-dark"><i class="bi bi-info-circle me-2"></i>정보</h6>
                </div>
                <div class="card-body small">
                    <table class="table table-sm mb-0 small">
                        <tr>
                            <td class="text-muted fw-bold">뷰 경로</td>
                            <td><code><?= htmlspecialchars($viewPath) ?></code></td>
                        </tr>
                        <tr>
                            <td class="text-muted fw-bold">상태</td>
                            <td>
                                <?php if ($existing): ?>
                                    <?php if ($existing['is_active']): ?>
                                        <span class="badge bg-accent">커스텀 활성</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">커스텀 비활성</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="badge bg-light text-muted border">신규</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php if ($existing): ?>
                        <tr>
                            <td class="text-muted fw-bold">마지막 수정</td>
                            <td><?= htmlspecialchars($existing['updated_at']) ?></td>
                        </tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
        </div>
    </div>
</form>

<!-- 풀스크린 미리보기 모달 -->
<div class="modal fade" id="previewModal" tabindex="-1" aria-labelledby="previewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content">
            <div class="modal-header py-2 px-4" style="background:var(--dark);border:none;">
                <div class="d-flex align-items-center gap-3">
                    <h6 class="modal-title text-white mb-0" id="previewModalLabel">
                        <i class="bi bi-eye me-2"></i>미리보기
                    </h6>
                    <span class="badge bg-info-subtle text-info" style="font-size:0.7rem;">샘플 데이터</span>
                    <code class="text-white-50 small"><?= htmlspecialchars($viewPath) ?></code>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-outline-light btn-sm active" onclick="setPreviewSize('100%','100%',this)" title="데스크톱">
                            <i class="bi bi-display"></i>
                        </button>
                        <button type="button" class="btn btn-outline-light btn-sm" onclick="setPreviewSize('768px','100%',this)" title="태블릿">
                            <i class="bi bi-tablet"></i>
                        </button>
                        <button type="button" class="btn btn-outline-light btn-sm" onclick="setPreviewSize('375px','100%',this)" title="모바일">
                            <i class="bi bi-phone"></i>
                        </button>
                    </div>
                    <button type="button" class="btn btn-outline-light btn-sm" onclick="refreshPreview()">
                        <i class="bi bi-arrow-clockwise"></i>
                    </button>
                    <button type="button" class="btn-close btn-close-white ms-2" data-bs-dismiss="modal" aria-label="닫기"></button>
                </div>
            </div>
            <div class="modal-body p-0 d-flex justify-content-center" style="background:#e2e8f0;">
                <iframe id="preview-iframe"
                        style="width:100%;height:100%;border:none;background:#fff;transition:width 0.3s ease;">
                </iframe>
            </div>
        </div>
    </div>
</div>

<!-- CodeMirror JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/codemirror.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/mode/xml/xml.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/mode/javascript/javascript.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/mode/css/css.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/mode/htmlmixed/htmlmixed.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/addon/edit/closetag.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/addon/edit/closebrackets.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/addon/edit/matchbrackets.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/addon/edit/matchtags.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/addon/fold/foldcode.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/addon/fold/foldgutter.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/addon/fold/xml-fold.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/addon/fold/brace-fold.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/addon/comment/comment.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/addon/search/search.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/addon/search/searchcursor.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/addon/search/jump-to-line.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/addon/dialog/dialog.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/addon/hint/show-hint.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/addon/hint/html-hint.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.18/addon/hint/css-hint.min.js"></script>
<script>
// ─── CodeMirror 초기화 ───
var cmHtml = CodeMirror.fromTextArea(document.getElementById('html-editor'), {
    mode: 'htmlmixed',
    theme: 'dracula',
    lineNumbers: true,
    lineWrapping: true,
    autoCloseTags: true,
    autoCloseBrackets: true,
    matchBrackets: true,
    matchTags: { bothTags: true },
    foldGutter: true,
    gutters: ['CodeMirror-linenumbers', 'CodeMirror-foldgutter'],
    indentUnit: 4,
    tabSize: 4,
    indentWithTabs: false,
    extraKeys: {
        'Ctrl-S': function() { document.getElementById('template-form').submit(); },
        'Ctrl-Space': 'autocomplete',
        'Ctrl-/': 'toggleComment',
        'Tab': function(cm) {
            if (cm.somethingSelected()) { cm.indentSelection('add'); }
            else { cm.replaceSelection('    ', 'end'); }
        },
        'Shift-Tab': function(cm) { cm.indentSelection('subtract'); }
    }
});

var cmCss = CodeMirror.fromTextArea(document.getElementById('css-editor'), {
    mode: 'css',
    theme: 'dracula',
    lineNumbers: true,
    lineWrapping: true,
    autoCloseBrackets: true,
    matchBrackets: true,
    foldGutter: true,
    gutters: ['CodeMirror-linenumbers', 'CodeMirror-foldgutter'],
    indentUnit: 4,
    tabSize: 4,
    indentWithTabs: false,
    extraKeys: {
        'Ctrl-S': function() { document.getElementById('template-form').submit(); },
        'Ctrl-Space': 'autocomplete',
        'Ctrl-/': 'toggleComment',
        'Tab': function(cm) {
            if (cm.somethingSelected()) { cm.indentSelection('add'); }
            else { cm.replaceSelection('    ', 'end'); }
        },
        'Shift-Tab': function(cm) { cm.indentSelection('subtract'); }
    }
});

// Mustache {{}} 구문 하이라이트 오버레이
CodeMirror.defineMode('mustache-overlay', function() {
    return {
        token: function(stream) {
            if (stream.match(/\{\{\{?#?\/?/)) {
                while (!stream.match(/\}\}\}?/, true)) {
                    if (stream.next() == null) break;
                }
                return 'mustache';
            }
            stream.next();
            return null;
        }
    };
});
cmHtml.addOverlay('mustache-overlay');

// ─── 변수/데이터 ───
var defaultSource = <?= json_encode($defaultSource, JSON_UNESCAPED_UNICODE) ?>;
var csrfToken = <?= json_encode(Auth::generateCsrfToken()) ?>;
var viewPath = <?= json_encode($viewPath) ?>;
var currentFontSize = 0.82;

// ─── 에디터 기능 ───
function loadDefault() {
    if (!confirm('현재 에디터 내용을 기본 뷰 소스로 대체합니다. 계속하시겠습니까?')) return;
    cmHtml.setValue(defaultSource);
}

function insertVariable(varName) {
    var text = '{{' + varName + '}}';
    cmHtml.replaceSelection(text);
    cmHtml.focus();
}

function insertSnippet(type) {
    var snippets = {
        'if': '{{#if variable}}\n    \n{{/if}}',
        'each': '{{#each items}}\n    <div>{{@index}}: {{this}}</div>\n{{/each}}',
        'card': '<div class="card border-0 shadow-sm">\n    <div class="card-header bg-transparent border-0 py-3">\n        <h6 class="m-0 fw-bold text-dark">제목</h6>\n    </div>\n    <div class="card-body">\n        내용\n    </div>\n</div>',
        'table': '<div class="table-responsive">\n    <table class="table table-hover align-middle mb-0">\n        <thead>\n            <tr>\n                <th>컬럼1</th>\n                <th>컬럼2</th>\n            </tr>\n        </thead>\n        <tbody>\n            {{#each items}}\n            <tr>\n                <td>{{field1}}</td>\n                <td>{{field2}}</td>\n            </tr>\n            {{/each}}\n        </tbody>\n    </table>\n</div>',
        'row': '<div class="row g-4">\n    <div class="col-md-6">\n        \n    </div>\n    <div class="col-md-6">\n        \n    </div>\n</div>',
        'stat': '<div class="card stat-card h-100 py-2 shadow-sm">\n    <div class="card-body">\n        <div class="row no-gutters align-items-center">\n            <div class="col mr-2">\n                <div class="stat-label">라벨</div>\n                <div class="stat-value">{{value}}</div>\n            </div>\n            <div class="col-auto">\n                <i class="bi bi-graph-up fs-2 opacity-25"></i>\n            </div>\n        </div>\n    </div>\n</div>'
    };
    if (snippets[type]) {
        cmHtml.replaceSelection(snippets[type]);
        cmHtml.focus();
    }
}

function changeFontSize(delta) {
    currentFontSize = Math.max(0.6, Math.min(1.2, currentFontSize + delta * 0.04));
    document.querySelectorAll('.CodeMirror').forEach(function(el) {
        el.style.fontSize = currentFontSize + 'rem';
    });
    cmHtml.refresh();
    cmCss.refresh();
}

// ─── 미리보기 모달 ───
var previewModalObj = null;

function runPreview() {
    cmHtml.save();
    cmCss.save();
    var htmlContent = cmHtml.getValue();
    var cssContent = cmCss.getValue();

    if (!htmlContent.trim()) {
        alert('HTML 템플릿을 먼저 입력해주세요.');
        return;
    }

    if (!previewModalObj) {
        previewModalObj = new bootstrap.Modal(document.getElementById('previewModal'));
    }
    previewModalObj.show();

    var iframe = document.getElementById('preview-iframe');
    iframe.srcdoc = '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><div style="display:flex;align-items:center;justify-content:center;height:100vh;font-family:sans-serif;color:#64748b;"><div class="text-center"><div class="spinner-border text-secondary mb-3" role="status"></div><div>렌더링 중...</div></div></div>';

    var formData = new FormData();
    formData.append('view_path', viewPath);
    formData.append('html_content', htmlContent);
    formData.append('css_content', cssContent);

    fetch('index.php?route=theme/preview', {
        method: 'POST',
        headers: { 'X-CSRF-Token': csrfToken },
        body: formData
    })
    .then(function(resp) { return resp.json(); })
    .then(function(data) {
        if (data.success) {
            iframe.srcdoc = data.html;
        } else {
            iframe.srcdoc = '<div style="padding:2rem;color:#ef4444;font-family:sans-serif;"><strong>렌더링 오류</strong><br>' + (data.message || '알 수 없는 오류') + '</div>';
        }
        if (data.newToken) {
            csrfToken = data.newToken;
            var csrfInput = document.querySelector('input[name="_csrf"]');
            if (csrfInput) csrfInput.value = data.newToken;
        }
    })
    .catch(function(err) {
        iframe.srcdoc = '<div style="padding:2rem;color:#ef4444;font-family:sans-serif;"><strong>요청 실패</strong><br>' + err.message + '</div>';
    });
}

function setPreviewSize(w, h, btn) {
    var iframe = document.getElementById('preview-iframe');
    iframe.style.width = w;
    iframe.style.height = h;
    btn.closest('.btn-group').querySelectorAll('.btn').forEach(function(b) { b.classList.remove('active'); });
    btn.classList.add('active');
}

function refreshPreview() { runPreview(); }

// form 제출 시 CodeMirror 값 동기화
document.getElementById('template-form').addEventListener('submit', function() {
    cmHtml.save();
    cmCss.save();
});
</script>
