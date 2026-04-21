/**
 * CentralAdmin - 공통 JS
 */

// 삭제 확인 모달
document.addEventListener('click', function(e) {
    var btn = e.target.closest('[data-confirm]');
    if (!btn) return;

    e.preventDefault();
    var modal = document.getElementById('confirmModal');
    if (!modal) return;

    var bsModal = new bootstrap.Modal(modal);
    var titleEl = document.getElementById('confirmModalTitle');
    var msgEl = document.getElementById('confirmModalMessage');
    var formEl = document.getElementById('confirmModalForm');
    var idEl = document.getElementById('confirmModalId');

    titleEl.textContent = btn.getAttribute('data-confirm-title') || '확인';
    msgEl.textContent = btn.getAttribute('data-confirm') || '이 작업을 진행하시겠습니까?';
    formEl.action = btn.getAttribute('data-action') || '';
    idEl.value = btn.getAttribute('data-id') || '';

    bsModal.show();
});

// 알림 자동 닫기 (5초)
document.addEventListener('DOMContentLoaded', function() {
    var alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            var bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            bsAlert.close();
        }, 5000);
    });
});
