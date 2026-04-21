<!-- 삭제/상태변경 확인 모달 -->
<div class="modal fade" id="confirmModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="confirmModalTitle">확인</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p id="confirmModalMessage">이 작업을 진행하시겠습니까?</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
        <form id="confirmModalForm" method="POST" style="display:inline;">
          <input type="hidden" name="_csrf" value="<?= Auth::generateCsrfToken() ?>">
          <input type="hidden" name="id" id="confirmModalId" value="">
          <button type="submit" class="btn btn-danger" id="confirmModalSubmit">확인</button>
        </form>
      </div>
    </div>
  </div>
</div>
