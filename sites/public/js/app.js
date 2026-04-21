/**
 * 가맹점 사이트 - 공통 JS
 */

// 삭제 확인 모달
document.addEventListener('click', function(e) {
    var btn = e.target.closest('[data-confirm]');
    if (!btn) return;
    e.preventDefault();
    var modal = document.getElementById('confirmModal');
    if (!modal) return;
    var bsModal = new bootstrap.Modal(modal);
    document.getElementById('confirmModalTitle').textContent = btn.getAttribute('data-confirm-title') || '확인';
    document.getElementById('confirmModalMessage').textContent = btn.getAttribute('data-confirm') || '이 작업을 진행하시겠습니까?';
    document.getElementById('confirmModalForm').action = btn.getAttribute('data-action') || '';
    document.getElementById('confirmModalId').value = btn.getAttribute('data-id') || '';
    bsModal.show();
});

// 알림 자동 닫기
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.alert-dismissible').forEach(function(el) {
        setTimeout(function() { bootstrap.Alert.getOrCreateInstance(el).close(); }, 5000);
    });
});

// 리포트 비교 세션 선택 (체크박스 2개 제한)
document.addEventListener('change', function(e) {
    if (!e.target.classList.contains('compare-check')) return;

    // 해당 체크박스가 속한 섹션 기준으로 범위 지정
    var section = e.target.closest('.compare-section') || e.target.closest('table');
    if (!section) return;

    var checked = section.querySelectorAll('.compare-check:checked');
    var all = section.querySelectorAll('.compare-check');
    var btn = section.closest('.compare-section')
            ? section.closest('.compare-section').querySelector('.btn-compare')
            : document.querySelector('.btn-compare');

    // 2개 초과 선택 방지
    if (checked.length >= 2) {
        all.forEach(function(cb) { if (!cb.checked) cb.disabled = true; });
    } else {
        all.forEach(function(cb) { cb.disabled = false; });
    }

    // 비교 버튼 활성화/비활성화
    if (btn) {
        if (checked.length === 2) {
            btn.classList.remove('disabled');
            btn.style.pointerEvents = 'auto';
            btn.style.opacity = '1';
            btn.href = btn.getAttribute('data-base-url') + '&s1=' + checked[0].value + '&s2=' + checked[1].value;
        } else {
            btn.classList.add('disabled');
            btn.style.pointerEvents = 'none';
            btn.style.opacity = '0.65';
            btn.href = 'javascript:void(0)';
        }
    }
});
