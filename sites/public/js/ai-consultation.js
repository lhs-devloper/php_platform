/**
 * AI 상담 어시스턴트 - 프론트엔드 로직
 */
document.addEventListener('DOMContentLoaded', function () {
    var modal = document.getElementById('aiConsultModal');
    if (!modal) return;

    var serviceRadios     = document.querySelectorAll('input[name="ai_service_type"]');
    var postureSection    = document.getElementById('aiPostureSection');
    var footSection       = document.getElementById('aiFootSection');
    var generateBtn       = document.getElementById('aiGenerateBtn');
    var regenerateBtn     = document.getElementById('aiRegenerateBtn');
    var step1             = document.getElementById('aiStep1');
    var step2             = document.getElementById('aiStep2');
    var step3             = document.getElementById('aiStep3');
    var errorDiv          = document.getElementById('aiError');
    var csrfInput         = document.getElementById('aiCsrfToken');

    // 서비스 유형 변경 시 세션 섹션 토글
    serviceRadios.forEach(function (radio) {
        radio.addEventListener('change', function () {
            var val = this.value;
            postureSection.style.display = (val === 'POSTURE' || val === 'BOTH') ? '' : 'none';
            footSection.style.display    = (val === 'FOOT' || val === 'BOTH') ? '' : 'none';
        });
    });

    // 모달 열 때 초기화
    modal.addEventListener('show.bs.modal', function () {
        resetModal();
    });

    // 생성 버튼
    generateBtn.addEventListener('click', function () {
        doGenerate();
    });

    // 다시 생성 버튼
    regenerateBtn.addEventListener('click', function () {
        showStep(1);
        errorDiv.classList.add('d-none');
    });

    function getServiceType() {
        var checked = document.querySelector('input[name="ai_service_type"]:checked');
        return checked ? checked.value : 'POSTURE';
    }

    function doGenerate() {
        var serviceType = getServiceType();
        var curPosture  = document.getElementById('aiCurrentPosture').value;
        var curFoot     = document.getElementById('aiCurrentFoot').value;
        var prevPosture = document.getElementById('aiPrevPosture').value;
        var prevFoot    = document.getElementById('aiPrevFoot').value;

        // 클라이언트 검증
        if ((serviceType === 'POSTURE' || serviceType === 'BOTH') && !curPosture) {
            showError('현재 자세분석 세션을 선택해주세요.');
            return;
        }
        if ((serviceType === 'FOOT' || serviceType === 'BOTH') && !curFoot) {
            showError('현재 족부분석 세션을 선택해주세요.');
            return;
        }

        showStep(2);

        var memberId = document.querySelector('input[name="member_id"]').value;
        var csrfToken = csrfInput.value;

        var formData = new FormData();
        formData.append('member_id', memberId);
        formData.append('current_posture_session_id', curPosture || '');
        formData.append('current_foot_session_id', curFoot || '');
        formData.append('previous_posture_session_id', prevPosture || '');
        formData.append('previous_foot_session_id', prevFoot || '');

        fetch('index.php?route=consultation/generate', {
            method: 'POST',
            headers: { 'X-CSRF-Token': csrfToken },
            body: formData
        })
        .then(function (res) { return res.json(); })
        .then(function (data) {
            // CSRF 토큰 갱신
            if (data.csrf_token) {
                csrfInput.value = data.csrf_token;
            }

            if (data.success) {
                populateResult(data.data, serviceType, curPosture, curFoot, prevPosture, prevFoot, data.usage_log_id);
                showStep(3);
            } else {
                showStep(1);
                showError(data.message || 'AI 소견 생성에 실패했습니다.');
            }
        })
        .catch(function (err) {
            showStep(1);
            showError('네트워크 오류가 발생했습니다. 잠시 후 다시 시도해주세요.');
        });
    }

    function populateResult(data, serviceType, curPosture, curFoot, prevPosture, prevFoot, usageLogId) {
        document.getElementById('aiOverallAssessment').value  = data.overall_assessment || '';
        document.getElementById('aiImprovementNote').value    = data.improvement_note || '';
        document.getElementById('aiConcernNote').value        = data.concern_note || '';
        document.getElementById('aiRecommendation').value     = data.recommendation || '';
        document.getElementById('aiComparisonSummary').value  = data.comparison_summary || '';

        // hidden 필드 설정
        document.getElementById('aiSaveServiceType').value   = serviceType;
        document.getElementById('aiSaveCurPosture').value    = curPosture || '';
        document.getElementById('aiSaveCurFoot').value       = curFoot || '';
        document.getElementById('aiSavePrevPosture').value   = prevPosture || '';
        document.getElementById('aiSavePrevFoot').value      = prevFoot || '';
        document.getElementById('aiUsageLogId').value        = usageLogId || '';
    }

    function showStep(step) {
        step1.classList.toggle('d-none', step !== 1);
        step2.classList.toggle('d-none', step !== 2);
        step3.classList.toggle('d-none', step !== 3);
    }

    function showError(msg) {
        errorDiv.textContent = msg;
        errorDiv.classList.remove('d-none');
    }

    function resetModal() {
        showStep(1);
        errorDiv.classList.add('d-none');
        document.getElementById('aiOverallAssessment').value  = '';
        document.getElementById('aiImprovementNote').value    = '';
        document.getElementById('aiConcernNote').value        = '';
        document.getElementById('aiRecommendation').value     = '';
        document.getElementById('aiComparisonSummary').value  = '';
    }

    // ─── 리포트 미리보기 ───

    var previewModalEl = document.getElementById('reportPreviewModal');
    var previewModal   = previewModalEl ? new bootstrap.Modal(previewModalEl) : null;
    var previewTitle   = document.getElementById('reportPreviewTitle');
    var previewBody    = document.getElementById('reportPreviewBody');

    document.querySelectorAll('.ai-preview-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var type = this.getAttribute('data-type');
            var selectId = this.getAttribute('data-select');
            var sessionId = document.getElementById(selectId).value;

            if (!sessionId) {
                alert('세션을 먼저 선택해주세요.');
                return;
            }

            openPreview(type, sessionId);
        });
    });

    function openPreview(type, sessionId) {
        var typeLabel = type === 'posture' ? '자세분석' : '족부분석';
        previewTitle.innerHTML = '<i class="bi bi-eye"></i> ' + typeLabel + ' 리포트 미리보기';
        previewBody.innerHTML =
            '<div class="text-center py-4">' +
            '<div class="spinner-border spinner-border-sm text-primary" role="status"></div>' +
            '<span class="text-muted ms-2">리포트를 불러오는 중...</span></div>';

        previewModal.show();

        fetch('index.php?route=consultation/preview_report&type=' + type + '&session_id=' + sessionId)
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (!data.success) {
                    previewBody.innerHTML = '<div class="alert alert-warning mb-0">' + (data.message || '리포트를 불러올 수 없습니다.') + '</div>';
                    return;
                }
                previewBody.innerHTML = buildPreviewHtml(data);
            })
            .catch(function () {
                previewBody.innerHTML = '<div class="alert alert-danger mb-0">네트워크 오류가 발생했습니다.</div>';
            });
    }

    function buildPreviewHtml(data) {
        var html = '';

        // 세션 정보
        html += '<div class="d-flex gap-3 mb-3 p-2 bg-light rounded">';
        html += '<small><i class="bi bi-calendar"></i> <strong>' + data.session.date + '</strong></small>';
        if (data.session.height) html += '<small>키: ' + data.session.height + 'cm</small>';
        if (data.session.weight) html += '<small>몸무게: ' + data.session.weight + 'kg</small>';
        html += '</div>';

        // 이미지
        if (data.images && data.images.length > 0) {
            html += '<div class="mb-3"><h6 class="fw-bold mb-2"><i class="bi bi-images"></i> 이미지</h6>';
            html += '<div class="row g-2">';
            data.images.forEach(function (img) {
                html += '<div class="col-md-3 text-center">';
                html += '<img src="' + escapeHtml(img.url) + '" class="img-fluid rounded border" style="max-height:150px;cursor:pointer;" ';
                html += 'onclick="window.open(this.src,\'_blank\')" alt="' + escapeHtml(img.label) + '">';
                html += '<small class="text-muted d-block mt-1">' + escapeHtml(img.label) + '</small>';
                html += '</div>';
            });
            html += '</div></div>';
        }

        // 데이터 테이블
        if (data.data && data.data.length > 0) {
            html += '<h6 class="fw-bold mb-2"><i class="bi bi-table"></i> 분석 데이터</h6>';
            html += '<table class="table table-sm table-hover mb-0"><tbody>';
            data.data.forEach(function (item) {
                html += '<tr><td class="text-muted" style="width:45%;">' + escapeHtml(item.label) + '</td>';
                html += '<td><strong>' + escapeHtml(String(item.value)) + '</strong></td></tr>';
            });
            html += '</tbody></table>';
        }

        return html;
    }

    function escapeHtml(str) {
        var div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
});
