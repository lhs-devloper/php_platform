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
        $page = max(1, (int)$this->input('page', 1));
        $result = $this->noticeModel->getList($page, ITEMS_PER_PAGE);

        $this->view('notice/list', [
            'pageTitle'  => '공지사항',
            'notices'    => $result['rows'],
            'pagination' => new Pagination($result['total'], ITEMS_PER_PAGE, $page),
        ]);
    }

    public function detail()
    {
        $this->requireAuth();
        $id = (int)$this->input('id');
        $notice = $this->noticeModel->findById($id);

        if (!$notice) {
            $this->flash('danger', '공지사항을 찾을 수 없습니다.');
            $this->redirect('notice/list');
            return;
        }

        $this->view('notice/detail', [
            'pageTitle' => $notice['title'],
            'notice'    => $notice,
        ]);
    }
}
