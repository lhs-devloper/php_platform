<?php
class MemberController extends Controller
{
    private $memberModel;

    public function __construct()
    {
        parent::__construct();
        $this->memberModel = new MemberModel();
    }

    public function list()
    {
        $this->requireAuth();
        $keyword      = $this->input('search', '');
        $status       = $this->input('status', '');
        $classCodeId  = $this->input('class_code_id', '');
        $instructorId = $this->input('instructor_id', '');
        $page         = max(1, (int)$this->input('page', 1));

        $result = $this->memberModel->search($keyword, $status, $classCodeId, $instructorId, $page, ITEMS_PER_PAGE);

        $this->view('member/list', [
            'pageTitle'   => '회원 관리',
            'members'     => $result['rows'],
            'pagination'  => new Pagination($result['total'], ITEMS_PER_PAGE, $page),
            'keyword'     => $keyword,
            'status'      => $status,
            'classCodeId' => $classCodeId,
            'instructorId'=> $instructorId,
            'instructors' => (new InstructorModel())->getActiveList(),
            'classCodes'  => (new ClassCodeModel())->getActiveList(),
        ]);
    }

    public function create()
    {
        $this->requireAuth();
        $this->requireRole(['SUPER', 'ADMIN']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCsrf();
            $data = $this->getMemberData();
            $v = new Validator($data);
            $v->required('name', '이름')->maxLength('name', 50, '이름');
            if (!$v->passes()) {
                $this->flash('danger', $v->firstError());
                $_SESSION['form_data'] = $data;
                $this->redirect('member/create');
                return;
            }
            $id = $this->memberModel->insert($data);
            $this->flash('success', '회원이 등록되었습니다.');
            $this->redirect('member/detail', ['id' => $id]);
        }

        $formData = isset($_SESSION['form_data']) ? $_SESSION['form_data'] : [];
        unset($_SESSION['form_data']);

        $this->view('member/form', [
            'pageTitle'   => '회원 등록',
            'member'      => $formData,
            'isEdit'      => false,
            'csrfToken'   => Auth::generateCsrfToken(),
            'instructors' => (new InstructorModel())->getActiveList(),
            'classCodes'  => (new ClassCodeModel())->getActiveList(),
        ]);
    }

    public function edit()
    {
        $this->requireAuth();
        $this->requireRole(['SUPER', 'ADMIN']);
        $id = (int)$this->input('id');
        $member = $this->memberModel->findById($id);
        if (!$member) { $this->flash('danger', '회원을 찾을 수 없습니다.'); $this->redirect('member/list'); return; }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCsrf();
            $data = $this->getMemberData();
            $this->memberModel->update($id, $data);
            $this->flash('success', '회원 정보가 수정되었습니다.');
            $this->redirect('member/detail', ['id' => $id]);
        }

        $this->view('member/form', [
            'pageTitle'   => '회원 수정',
            'member'      => $member,
            'isEdit'      => true,
            'csrfToken'   => Auth::generateCsrfToken(),
            'instructors' => (new InstructorModel())->getActiveList(),
            'classCodes'  => (new ClassCodeModel())->getActiveList(),
        ]);
    }

    public function detail()
    {
        $this->requireAuth();
        $id = (int)$this->input('id');
        $member = $this->memberModel->findByIdWithRelations($id);
        if (!$member) { $this->flash('danger', '회원을 찾을 수 없습니다.'); $this->redirect('member/list'); return; }

        $aiEnabled = $member['consultation_enabled']
            && (new AiConfigModel())->isAvailable(Auth::franchiseId());

        $this->view('member/detail', [
            'pageTitle'       => $member['name'],
            'member'          => $member,
            'postureSessions' => (new PostureSessionModel())->getByMember($id),
            'footSessions'    => (new FootSessionModel())->getByMember($id),
            'consultations'   => (new ConsultationModel())->getByMember($id),
            'csrfToken'       => Auth::generateCsrfToken(),
            'aiEnabled'       => $aiEnabled,
        ]);
    }

    public function delete()
    {
        $this->requireAuth();
        $this->requireRole(['SUPER', 'ADMIN']);
        $this->validateCsrf();
        $id = (int)$this->input('id');
        $this->memberModel->update($id, ['status' => 'WITHDRAWN']);
        $this->flash('success', '회원이 퇴원 처리되었습니다.');
        $this->redirect('member/list');
    }

    public function toggleConsultation()
    {
        $this->requireAuth();
        $this->requireRole(['SUPER', 'ADMIN']);
        $this->validateCsrf();
        $id = (int)$this->input('id');
        $this->memberModel->toggleConsultation($id);
        $this->flash('success', '상담소견 기능이 변경되었습니다.');
        $this->redirect('member/detail', ['id' => $id]);
    }

    private function getMemberData()
    {
        return [
            'franchise_id'          => Auth::franchiseId(),
            'name'                  => $this->input('name', ''),
            'phone'                 => $this->input('phone', ''),
            'gender'                => $this->input('gender', '') ?: null,
            'birth_date'            => $this->input('birth_date', '') ?: null,
            'height'                => $this->input('height', '') ?: null,
            'weight'                => $this->input('weight', '') ?: null,
            'class_code_id'         => $this->input('class_code_id', '') ?: null,
            'instructor_id'         => $this->input('instructor_id', '') ?: null,
            'memo'                  => $this->input('memo', '') ?: null,
            'consultation_enabled'  => $this->input('consultation_enabled') ? 1 : 0,
            'status'                => $this->input('status', 'ACTIVE'),
        ];
    }
}
