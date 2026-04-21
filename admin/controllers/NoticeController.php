<?php
class NoticeController extends Controller
{
    private $noticeModel;

    public function __construct()
    {
        parent::__construct();
        $this->noticeModel = new NoticeModel();
    }

    public function list()
    {
        $this->requireAuth();
        $keyword    = $this->input('search', '');
        $targetType = $this->input('target_type', '');
        $published  = $this->input('is_published', '');
        $page       = max(1, (int)$this->input('page', 1));

        $result = $this->noticeModel->search($keyword, $targetType, $published, $page, ITEMS_PER_PAGE);

        $this->view('notice/list', [
            'pageTitle'  => '공지사항 관리',
            'notices'    => $result['rows'],
            'pagination' => new Pagination($result['total'], ITEMS_PER_PAGE, $page),
            'keyword'    => $keyword,
            'targetType' => $targetType,
            'published'  => $published,
        ]);
    }

    public function create()
    {
        $this->requireAuth();
        $this->requireRole(['SUPER_ADMIN', 'ADMIN', 'OPERATOR']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCsrf();
            $data = $this->getNoticeData();

            $v = new Validator($data);
            $v->required('title', '제목')->required('content', '내용');
            if (!$v->passes()) {
                $this->flash('danger', $v->firstError());
                $_SESSION['form_data'] = $data;
                $_SESSION['form_data']['_tenant_ids'] = isset($_POST['tenant_ids']) ? $_POST['tenant_ids'] : [];
                $this->redirect('notice/create');
                return;
            }

            $id = $this->noticeModel->insert($data);

            // 복수 가맹점 매핑 저장
            if ($data['target_type'] === 'SPECIFIC') {
                $tenantIds = isset($_POST['tenant_ids']) ? $_POST['tenant_ids'] : [];
                $this->noticeModel->saveTenantIds($id, $tenantIds);
            }

            $this->auditLog('CREATE', 'notice', $id, "공지사항 등록: {$data['title']}");
            $this->flash('success', '공지사항이 등록되었습니다.');
            $this->redirect('notice/detail', ['id' => $id]);
        }

        $formData = isset($_SESSION['form_data']) ? $_SESSION['form_data'] : [];
        unset($_SESSION['form_data']);

        $tenantModel = new TenantModel();

        $this->view('notice/form', [
            'pageTitle'       => '공지사항 등록',
            'notice'          => $formData,
            'isEdit'          => false,
            'tenants'         => $tenantModel->findAll([], 'company_name ASC', 1000, 0),
            'selectedTenants' => $formData['_tenant_ids'] ?? [],
            'csrfToken'       => Auth::generateCsrfToken(),
        ]);
    }

    public function edit()
    {
        $this->requireAuth();
        $this->requireRole(['SUPER_ADMIN', 'ADMIN', 'OPERATOR']);

        $id = (int)$this->input('id');
        $notice = $this->noticeModel->findById($id);
        if (!$notice) { $this->flash('danger', '공지사항을 찾을 수 없습니다.'); $this->redirect('notice/list'); return; }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCsrf();
            $before = $notice;
            $data = $this->getNoticeData();
            $this->noticeModel->update($id, $data);

            // 복수 가맹점 매핑 업데이트
            if ($data['target_type'] === 'SPECIFIC') {
                $tenantIds = isset($_POST['tenant_ids']) ? $_POST['tenant_ids'] : [];
                $this->noticeModel->saveTenantIds($id, $tenantIds);
            } else {
                $this->noticeModel->saveTenantIds($id, []); // 전체 대상이면 매핑 삭제
            }

            $after = $this->noticeModel->findById($id);
            $this->auditLog('UPDATE', 'notice', $id, "공지사항 수정: {$data['title']}", $before, $after);
            $this->flash('success', '공지사항이 수정되었습니다.');
            $this->redirect('notice/detail', ['id' => $id]);
        }

        $tenantModel = new TenantModel();

        $this->view('notice/form', [
            'pageTitle'       => '공지사항 수정',
            'notice'          => $notice,
            'isEdit'          => true,
            'tenants'         => $tenantModel->findAll([], 'company_name ASC', 1000, 0),
            'selectedTenants' => $this->noticeModel->getTenantIds($id),
            'csrfToken'       => Auth::generateCsrfToken(),
        ]);
    }

    public function detail()
    {
        $this->requireAuth();
        $id = (int)$this->input('id');
        $notice = $this->noticeModel->findByIdWithRelations($id);
        if (!$notice) { $this->flash('danger', '공지사항을 찾을 수 없습니다.'); $this->redirect('notice/list'); return; }

        $this->view('notice/detail', [
            'pageTitle' => $notice['title'],
            'notice'    => $notice,
            'csrfToken' => Auth::generateCsrfToken(),
        ]);
    }

    public function delete()
    {
        $this->requireAuth();
        $this->requireRole(['SUPER_ADMIN', 'ADMIN']);
        $this->validateCsrf();

        $id = (int)$this->input('id');
        $notice = $this->noticeModel->findById($id);
        if ($notice) {
            $this->noticeModel->delete($id); // notice_tenant은 CASCADE로 자동 삭제
            $this->auditLog('DELETE', 'notice', $id, "공지사항 삭제: {$notice['title']}", $notice);
            $this->flash('success', '공지사항이 삭제되었습니다.');
        }
        $this->redirect('notice/list');
    }

    public function togglePublish()
    {
        $this->requireAuth();
        $this->requireRole(['SUPER_ADMIN', 'ADMIN', 'OPERATOR']);
        $this->validateCsrf();

        $id = (int)$this->input('id');
        $notice = $this->noticeModel->findById($id);
        if ($notice) {
            $newState = $notice['is_published'] ? 0 : 1;
            $updateData = ['is_published' => $newState];
            if ($newState && !$notice['published_at']) {
                $updateData['published_at'] = date('Y-m-d H:i:s');
            }
            $this->noticeModel->update($id, $updateData);
            $label = $newState ? '게시' : '비게시';
            $this->auditLog('UPDATE', 'notice', $id, "공지사항 {$label}: {$notice['title']}");
            $this->flash('success', "공지사항이 {$label} 처리되었습니다.");
        }
        $this->redirect('notice/detail', ['id' => $id]);
    }

    public function uploadImage()
    {
        $this->requireAuth();
        $this->requireRole(['SUPER_ADMIN', 'ADMIN', 'OPERATOR']);

        header('Content-Type: application/json');

        if (!isset($_FILES['upload']) || $_FILES['upload']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['error' => ['message' => '파일 업로드에 실패했습니다.']]);
            exit;
        }

        $file = $_FILES['upload'];
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, $allowed)) {
            echo json_encode(['error' => ['message' => '허용되지 않는 파일 형식입니다. (JPG, PNG, GIF, WebP만 가능)']]);
            exit;
        }

        $maxSize = 5 * 1024 * 1024;
        if ($file['size'] > $maxSize) {
            echo json_encode(['error' => ['message' => '파일 크기는 5MB 이하만 가능합니다.']]);
            exit;
        }

        $uploadDir = BASE_PATH . '/public/uploads/notice/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
        $filename = date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext[$mime];

        if (!move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
            echo json_encode(['error' => ['message' => '파일 저장에 실패했습니다.']]);
            exit;
        }

        echo json_encode(['url' => 'public/uploads/notice/' . $filename]);
        exit;
    }

    private function getNoticeData()
    {
        $targetType = $this->input('target_type', 'ALL');
        return [
            'admin_id'     => Auth::user()['id'],
            'target_type'  => $targetType,
            'title'        => $this->input('title', ''),
            'content'      => isset($_POST['content']) ? $_POST['content'] : '',
            'is_published' => $this->input('is_published') ? 1 : 0,
            'is_pinned'    => $this->input('is_pinned') ? 1 : 0,
            'published_at' => $this->input('is_published') ? date('Y-m-d H:i:s') : null,
        ];
    }
}
