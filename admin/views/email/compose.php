<?php $e = $email; ?>

<?php if (!$smtpConfigured): ?>
<div class="alert alert-warning mb-3">
    <i class="bi bi-exclamation-triangle me-1"></i>
    <strong>SMTP 미설정:</strong>
    <a href="index.php?route=email/settings">SMTP 설정</a>에서 메일 서버 정보를 입력해야 이메일을 발송할 수 있습니다.
    발송 시도 시 로그는 남지만 실제 발송은 되지 않습니다.
</div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="POST" action="index.php?route=email/compose">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>">

            <div class="row g-3">
                <!-- 수신자 프리셋 -->
                <div class="col-12">
                    <label class="form-label">수신자 빠른 선택 <small class="text-muted">(선택)</small></label>
                    <select class="form-select form-select-sm" id="recipientPreset" onchange="applyPreset(this)">
                        <option value="">-- 직접 입력 --</option>
                        <?php foreach ($tenants as $t): ?>
                            <?php if (!empty($t['email'])): ?>
                            <option value="<?= htmlspecialchars($t['email']) ?>">[<?= htmlspecialchars($t['company_name']) ?>] <?= htmlspecialchars($t['email']) ?></option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- 수신자 -->
                <div class="col-12">
                    <label class="form-label">수신자 (To) <span class="text-danger">*</span></label>
                    <input type="text" name="to_email" class="form-control"
                           value="<?= htmlspecialchars($e['to_email'] ?? '') ?>"
                           placeholder="email@example.com (여러명은 콤마로 구분)" required>
                </div>

                <!-- CC / BCC -->
                <div class="col-md-6">
                    <label class="form-label">참조 (CC)</label>
                    <input type="text" name="cc_email" class="form-control"
                           value="<?= htmlspecialchars($e['cc_email'] ?? '') ?>"
                           placeholder="콤마로 구분">
                </div>
                <div class="col-md-6">
                    <label class="form-label">숨은참조 (BCC)</label>
                    <input type="text" name="bcc_email" class="form-control"
                           value="<?= htmlspecialchars($e['bcc_email'] ?? '') ?>"
                           placeholder="콤마로 구분">
                </div>

                <!-- 제목 -->
                <div class="col-12">
                    <label class="form-label">제목 <span class="text-danger">*</span></label>
                    <input type="text" name="subject" class="form-control"
                           value="<?= htmlspecialchars($e['subject'] ?? '') ?>" required>
                </div>

                <!-- 본문 -->
                <div class="col-12">
                    <label class="form-label">본문 <span class="text-danger">*</span></label>
                    <textarea name="body_html" id="editorContent" class="form-control" rows="12"><?= htmlspecialchars($e['body_html'] ?? '') ?></textarea>
                    <div class="invalid-feedback" id="bodyError">본문을 입력해주세요.</div>
                </div>
            </div>

            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary" id="btnSend">
                    <i class="bi bi-send"></i> 발송
                </button>
                <a href="index.php?route=email/list" class="btn btn-outline-secondary">취소</a>
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
    content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; font-size: 14px; line-height: 1.6; } img { max-width: 100%; height: auto; }',
    promotion: false,
    branding: false,
    setup: function(editor) {
        editor.on('change keyup', function() {
            editor.save();                       // textarea에 내용 동기화
            document.getElementById('bodyError').style.display = 'none';
        });
    }
});

// 폼 제출 전 TinyMCE 내용 동기화 + 본문 빈 값 검증
document.querySelector('form').addEventListener('submit', function(ev) {
    tinymce.triggerSave();
    var body = document.getElementById('editorContent').value.replace(/<[^>]*>/g, '').trim();
    if (!body) {
        ev.preventDefault();
        document.getElementById('bodyError').style.display = 'block';
        return false;
    }
});

function applyPreset(sel) {
    if (sel.value) {
        var toField = document.querySelector('input[name="to_email"]');
        if (toField.value) {
            toField.value += ', ' + sel.value;
        } else {
            toField.value = sel.value;
        }
        sel.selectedIndex = 0;
    }
}
</script>
