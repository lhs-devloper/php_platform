<?php $s = $smtp; ?>

<div class="row">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <strong><i class="bi bi-gear me-1"></i> SMTP 서버 설정</strong>
                <button type="button" id="btnTestSmtp" class="btn btn-sm btn-outline-info">
                    <i class="bi bi-plug"></i> 연결 테스트
                </button>
            </div>
            <div class="card-body">
                <form method="POST" action="index.php?route=email/settings" id="smtpForm">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>">

                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">SMTP 서버 <span class="text-danger">*</span></label>
                            <input type="text" name="smtp_host" class="form-control"
                                   value="<?= htmlspecialchars($s['smtp_host']) ?>"
                                   placeholder="smtp.gmail.com">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">포트 <span class="text-danger">*</span></label>
                            <input type="number" name="smtp_port" class="form-control"
                                   value="<?= htmlspecialchars($s['smtp_port']) ?>"
                                   placeholder="587">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">SMTP 계정</label>
                            <input type="text" name="smtp_user" class="form-control"
                                   value="<?= htmlspecialchars($s['smtp_user']) ?>"
                                   placeholder="your-email@gmail.com" autocomplete="off">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">SMTP 비밀번호</label>
                            <div class="input-group">
                                <input type="password" name="smtp_pass" id="smtpPass" class="form-control"
                                       value="<?= htmlspecialchars($s['smtp_pass']) ?>"
                                       placeholder="앱 비밀번호 입력" autocomplete="off">
                                <button type="button" class="btn btn-outline-secondary" onclick="togglePassword()">
                                    <i class="bi bi-eye" id="eyeIcon"></i>
                                </button>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">암호화 방식</label>
                            <select name="smtp_encryption" class="form-select">
                                <option value="tls" <?= $s['smtp_encryption'] === 'tls' ? 'selected' : '' ?>>TLS (포트 587)</option>
                                <option value="ssl" <?= $s['smtp_encryption'] === 'ssl' ? 'selected' : '' ?>>SSL (포트 465)</option>
                                <option value="" <?= $s['smtp_encryption'] === '' ? 'selected' : '' ?>>없음 (포트 25)</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">발신자 이메일 <span class="text-danger">*</span></label>
                            <input type="email" name="smtp_from_email" class="form-control"
                                   value="<?= htmlspecialchars($s['smtp_from_email']) ?>"
                                   placeholder="noreply@example.com">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">발신자 이름</label>
                            <input type="text" name="smtp_from_name" class="form-control"
                                   value="<?= htmlspecialchars($s['smtp_from_name']) ?>"
                                   placeholder="CentralAdmin">
                        </div>
                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg"></i> 저장
                        </button>
                        <a href="index.php?route=email/list" class="btn btn-outline-secondary">취소</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <!-- 안내 카드 -->
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white">
                <strong><i class="bi bi-info-circle me-1"></i> 설정 안내</strong>
            </div>
            <div class="card-body small">
                <p class="mb-2">SMTP 서버를 통해 관리자 이메일을 발송합니다. 주요 SMTP 서비스 설정:</p>

                <div class="fw-bold text-primary mt-2">Gmail</div>
                <ul class="mb-2 ps-3">
                    <li>서버: <code>smtp.gmail.com</code></li>
                    <li>포트: <code>587</code> (TLS)</li>
                    <li>계정: Gmail 주소</li>
                    <li>비밀번호: <a href="https://myaccount.google.com/apppasswords" target="_blank">앱 비밀번호</a> 사용</li>
                </ul>

                <div class="fw-bold text-primary">Naver</div>
                <ul class="mb-2 ps-3">
                    <li>서버: <code>smtp.naver.com</code></li>
                    <li>포트: <code>587</code> (TLS)</li>
                    <li>계정: 네이버 아이디@naver.com</li>
                    <li>비밀번호: 네이버 비밀번호</li>
                </ul>

                <div class="fw-bold text-primary">Daum/Kakao</div>
                <ul class="mb-0 ps-3">
                    <li>서버: <code>smtp.daum.net</code></li>
                    <li>포트: <code>465</code> (SSL)</li>
                </ul>
            </div>
        </div>

        <!-- 테스트 결과 -->
        <div class="card border-0 shadow-sm" id="testResultCard" style="display:none;">
            <div class="card-header bg-white">
                <strong><i class="bi bi-plug me-1"></i> 연결 테스트 결과</strong>
            </div>
            <div class="card-body" id="testResultBody">
            </div>
        </div>
    </div>
</div>

<script>
function togglePassword() {
    var input = document.getElementById('smtpPass');
    var icon = document.getElementById('eyeIcon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'bi bi-eye';
    }
}

document.getElementById('btnTestSmtp').addEventListener('click', function() {
    var btn = this;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> 테스트 중...';

    // 현재 폼 데이터로 테스트 (저장하지 않고)
    var form = document.getElementById('smtpForm');
    var formData = new FormData(form);

    fetch('index.php?route=email/test_smtp', {
        method: 'POST',
        body: formData
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        var card = document.getElementById('testResultCard');
        var body = document.getElementById('testResultBody');
        card.style.display = '';

        if (data.success) {
            body.innerHTML = '<div class="alert alert-success mb-0"><i class="bi bi-check-circle-fill me-1"></i> ' + data.message + '</div>';
        } else {
            body.innerHTML = '<div class="alert alert-danger mb-0"><i class="bi bi-exclamation-triangle-fill me-1"></i> ' + data.message + '</div>';
        }
    })
    .catch(function() {
        alert('테스트 요청 실패');
    })
    .finally(function() {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-plug"></i> 연결 테스트';
    });
});
</script>
