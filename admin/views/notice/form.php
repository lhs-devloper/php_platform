<?php
$n = $notice;
$selIds = $selectedTenants ?? [];
?>
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="POST" id="noticeForm" action="index.php?route=<?= $isEdit ? 'notice/edit&id=' . $n['id'] : 'notice/create' ?>">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>">

            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label">제목 <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($n['title'] ?? '') ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">대상</label>
                    <select name="target_type" class="form-select" id="targetType" onchange="toggleTenantSelector()">
                        <option value="ALL" <?= ($n['target_type'] ?? 'ALL') === 'ALL' ? 'selected' : '' ?>>전체 가맹점</option>
                        <option value="SPECIFIC" <?= ($n['target_type'] ?? '') === 'SPECIFIC' ? 'selected' : '' ?>>특정 가맹점</option>
                    </select>
                </div>

                <!-- 가맹점 검색 & 다중선택 -->
                <div class="col-12" id="tenantSelector" style="display:<?= ($n['target_type'] ?? '') === 'SPECIFIC' ? '' : 'none' ?>;">
                    <label class="form-label">대상 가맹점 선택</label>
                    <div class="row g-2">
                        <!-- 가맹점 검색 -->
                        <div class="col-md-6">
                            <div class="card border">
                                <div class="card-header bg-light py-2">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                                        <input type="text" id="tenantSearch" class="form-control" placeholder="가맹점명 검색..." oninput="filterTenants()">
                                    </div>
                                </div>
                                <div class="card-body p-0" style="max-height:220px; overflow-y:auto;">
                                    <div class="list-group list-group-flush" id="tenantAvailableList">
                                        <?php foreach ($tenants as $t): ?>
                                            <?php if (!in_array($t['id'], $selIds)): ?>
                                            <button type="button" class="list-group-item list-group-item-action py-2 d-flex justify-content-between align-items-center tenant-item"
                                                    data-id="<?= $t['id'] ?>" data-name="<?= htmlspecialchars($t['company_name']) ?>"
                                                    onclick="addTenant(<?= $t['id'] ?>, '<?= htmlspecialchars($t['company_name'], ENT_QUOTES) ?>')">
                                                <span><i class="bi bi-building me-2 text-muted"></i><?= htmlspecialchars($t['company_name']) ?></span>
                                                <span class="badge bg-primary-subtle text-primary"><i class="bi bi-plus"></i></span>
                                            </button>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>
                                    <div class="text-center text-muted py-3 d-none" id="tenantNoResult">검색 결과가 없습니다.</div>
                                </div>
                            </div>
                        </div>

                        <!-- 선택된 가맹점 -->
                        <div class="col-md-6">
                            <div class="card border border-primary">
                                <div class="card-header bg-primary-subtle py-2 d-flex justify-content-between align-items-center">
                                    <strong class="small text-primary">선택된 가맹점 (<span id="selectedCount"><?= count($selIds) ?></span>)</strong>
                                    <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" style="font-size:0.75rem;" onclick="clearAllTenants()">전체 해제</button>
                                </div>
                                <div class="card-body p-0" style="max-height:220px; overflow-y:auto;">
                                    <div class="list-group list-group-flush" id="tenantSelectedList">
                                        <?php foreach ($tenants as $t): ?>
                                            <?php if (in_array($t['id'], $selIds)): ?>
                                            <div class="list-group-item py-2 d-flex justify-content-between align-items-center" id="selected-<?= $t['id'] ?>">
                                                <span><i class="bi bi-building-check me-2 text-primary"></i><?= htmlspecialchars($t['company_name']) ?></span>
                                                <input type="hidden" name="tenant_ids[]" value="<?= $t['id'] ?>">
                                                <button type="button" class="btn btn-sm btn-outline-danger py-0 px-1" onclick="removeTenant(<?= $t['id'] ?>, '<?= htmlspecialchars($t['company_name'], ENT_QUOTES) ?>')">
                                                    <i class="bi bi-x"></i>
                                                </button>
                                            </div>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>
                                    <div class="text-center text-muted py-3 <?= count($selIds) > 0 ? 'd-none' : '' ?>" id="tenantEmpty">가맹점을 선택해주세요.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <label class="form-label">내용 <span class="text-danger">*</span></label>
                    <textarea name="content" id="editorContent" class="form-control" rows="10"><?= htmlspecialchars($n['content'] ?? '') ?></textarea>
                    <div class="invalid-feedback" id="contentError">내용을 입력해주세요.</div>
                </div>

                <div class="col-md-6">
                    <div class="form-check">
                        <input type="checkbox" name="is_published" value="1" class="form-check-input" id="isPublished"
                               <?= ($n['is_published'] ?? 0) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="isPublished">즉시 게시</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-check">
                        <input type="checkbox" name="is_pinned" value="1" class="form-check-input" id="isPinned"
                               <?= ($n['is_pinned'] ?? 0) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="isPinned">상단 고정</label>
                    </div>
                </div>
            </div>

            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> <?= $isEdit ? '수정' : '등록' ?></button>
                <a href="index.php?route=notice/list" class="btn btn-outline-secondary">취소</a>
            </div>
        </form>
    </div>
</div>

<script src="public/js/tinymce/tinymce.min.js"></script>
<script>
tinymce.init({
    selector: '#editorContent',
    language: 'ko_KR',
    height: 400,
    menubar: 'file edit view insert format table',
    plugins: 'advlist autolink lists link image charmap preview anchor searchreplace visualblocks code fullscreen insertdatetime media table wordcount autoresize',
    toolbar: 'undo redo | heading | bold italic underline strikethrough | forecolor backcolor | bullist numlist | outdent indent alignleft aligncenter alignright | blockquote table hr | link image | code fullscreen',
    block_formats: '본문=p; 제목 2=h2; 제목 3=h3; 제목 4=h4',
    images_upload_url: 'index.php?route=notice/upload_image',
    images_upload_handler: function(blobInfo) {
        return new Promise(function(resolve, reject) {
            var formData = new FormData();
            formData.append('upload', blobInfo.blob(), blobInfo.filename());
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'index.php?route=notice/upload_image');
            xhr.onload = function() {
                if (xhr.status !== 200) return reject('업로드 실패: ' + xhr.status);
                var res = JSON.parse(xhr.responseText);
                if (res.error) return reject(res.error.message);
                resolve(res.url);
            };
            xhr.onerror = function() { reject('네트워크 오류'); };
            xhr.send(formData);
        });
    },
    content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; font-size: 14px; line-height: 1.6; } img { max-width: 100%; height: auto; }',
    promotion: false,
    branding: false,
    setup: function(editor) {
        editor.on('change keyup', function() {
            editor.save();
            document.getElementById('contentError').style.display = 'none';
        });
    }
});

// 폼 제출 전 검증
document.getElementById('noticeForm').addEventListener('submit', function(ev) {
    tinymce.triggerSave();
    var body = document.getElementById('editorContent').value.replace(/<[^>]*>/g, '').trim();
    if (!body) {
        ev.preventDefault();
        document.getElementById('contentError').style.display = 'block';
        return false;
    }
});

// === 가맹점 선택 기능 ===
function toggleTenantSelector() {
    document.getElementById('tenantSelector').style.display =
        document.getElementById('targetType').value === 'SPECIFIC' ? '' : 'none';
}

function filterTenants() {
    var query = document.getElementById('tenantSearch').value.toLowerCase();
    var items = document.querySelectorAll('#tenantAvailableList .tenant-item');
    var visibleCount = 0;
    items.forEach(function(item) {
        var name = item.getAttribute('data-name').toLowerCase();
        var show = name.indexOf(query) !== -1;
        item.style.display = show ? '' : 'none';
        if (show) visibleCount++;
    });
    document.getElementById('tenantNoResult').classList.toggle('d-none', visibleCount > 0);
}

function addTenant(id, name) {
    // 왼쪽 목록에서 숨기기
    var btn = document.querySelector('#tenantAvailableList .tenant-item[data-id="' + id + '"]');
    if (btn) btn.style.display = 'none';

    // 오른쪽에 추가
    var list = document.getElementById('tenantSelectedList');
    var div = document.createElement('div');
    div.className = 'list-group-item py-2 d-flex justify-content-between align-items-center';
    div.id = 'selected-' + id;
    div.innerHTML = '<span><i class="bi bi-building-check me-2 text-primary"></i>' + name + '</span>'
        + '<input type="hidden" name="tenant_ids[]" value="' + id + '">'
        + '<button type="button" class="btn btn-sm btn-outline-danger py-0 px-1" onclick="removeTenant(' + id + ', \'' + name.replace(/'/g, "\\'") + '\')"><i class="bi bi-x"></i></button>';
    list.appendChild(div);

    document.getElementById('tenantEmpty').classList.add('d-none');
    updateCount();
}

function removeTenant(id, name) {
    var el = document.getElementById('selected-' + id);
    if (el) el.remove();

    // 왼쪽 목록에 다시 표시
    var btn = document.querySelector('#tenantAvailableList .tenant-item[data-id="' + id + '"]');
    if (btn) btn.style.display = '';

    var remaining = document.querySelectorAll('#tenantSelectedList .list-group-item');
    if (remaining.length === 0) document.getElementById('tenantEmpty').classList.remove('d-none');
    updateCount();
}

function clearAllTenants() {
    document.querySelectorAll('#tenantSelectedList .list-group-item').forEach(function(el) { el.remove(); });
    document.querySelectorAll('#tenantAvailableList .tenant-item').forEach(function(el) { el.style.display = ''; });
    document.getElementById('tenantEmpty').classList.remove('d-none');
    updateCount();
}

function updateCount() {
    document.getElementById('selectedCount').textContent =
        document.querySelectorAll('#tenantSelectedList input[name="tenant_ids[]"]').length;
}
</script>
