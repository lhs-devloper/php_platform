<?php
/**
 * 수강반 관리 컨트롤러
 */
class ClassCodeController extends Controller
{
    /**
     * 수강반 목록
     */
    public function index()
    {
        $this->requireAuth();

        $model = new ClassCodeModel();
        $list = $model->getAllWithMemberCount();

        $this->view('class_code/list', [
            'pageTitle' => '수강반 관리',
            'classCodes' => $list,
        ]);
    }

    /**
     * 등록 폼
     */
    public function create()
    {
        $this->requireAuth();
        $this->requireRole(['SUPER', 'ADMIN']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCsrf();
            $this->save(null);
            return;
        }

        $this->view('class_code/form', [
            'pageTitle' => '수강반 등록',
            'isEdit'    => false,
            'item'      => ['code' => '', 'name' => '', 'is_active' => 1],
        ]);
    }

    /**
     * 수정 폼
     */
    public function edit()
    {
        $this->requireAuth();
        $this->requireRole(['SUPER', 'ADMIN']);

        $id = (int)$this->input('id', 0);
        $model = new ClassCodeModel();
        $item = $model->findById($id);

        if (!$item) {
            $this->flash('danger', '수강반을 찾을 수 없습니다.');
            $this->redirect('class_code/list');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCsrf();
            $this->save($id);
            return;
        }

        $this->view('class_code/form', [
            'pageTitle' => '수강반 수정',
            'isEdit'    => true,
            'item'      => $item,
        ]);
    }

    /**
     * 등록/수정 저장
     */
    private function save($id)
    {
        $model = new ClassCodeModel();

        $code = trim($this->input('code', ''));
        $name = trim($this->input('name', ''));
        $isActive = (int)$this->input('is_active', 1);

        // 유효성 검증
        $v = new Validator();
        $err = $v->required('코드', $code)
                 ->maxLength('코드', $code, 20)
                 ->required('이름', $name)
                 ->maxLength('이름', $name, 100)
                 ->getError();

        if ($err) {
            $this->flash('danger', $err);
            $this->redirect($id ? 'class_code/edit' : 'class_code/create', $id ? ['id' => $id] : []);
            return;
        }

        // 코드 중복 체크
        if ($model->codeExists($code, $id)) {
            $this->flash('danger', '이미 존재하는 코드입니다.');
            $this->redirect($id ? 'class_code/edit' : 'class_code/create', $id ? ['id' => $id] : []);
            return;
        }

        $data = [
            'code'      => $code,
            'name'      => $name,
            'is_active' => $isActive,
        ];

        if ($id) {
            $model->update($id, $data);
            $this->flash('success', '수강반이 수정되었습니다.');
        } else {
            $data['franchise_id'] = Auth::franchiseId() ?: 1;
            $model->insert($data);
            $this->flash('success', '수강반이 등록되었습니다.');
        }

        $this->redirect('class_code/list');
    }

    /**
     * 활성/비활성 토글
     */
    public function toggle()
    {
        $this->requireAuth();
        $this->requireRole(['SUPER', 'ADMIN']);
        $this->validateCsrf();

        $id = (int)$this->input('id', 0);
        if ($id > 0) {
            $model = new ClassCodeModel();
            $model->toggleActive($id);
            $this->flash('success', '수강반 상태가 변경되었습니다.');
        }
        $this->redirect('class_code/list');
    }

    /**
     * 삭제 (소속 회원이 있으면 거부)
     */
    public function delete()
    {
        $this->requireAuth();
        $this->requireRole(['SUPER', 'ADMIN']);
        $this->validateCsrf();

        $id = (int)$this->input('id', 0);
        $model = new ClassCodeModel();

        $memberCount = $model->getMemberCount($id);
        if ($memberCount > 0) {
            $this->flash('danger', "소속 회원이 {$memberCount}명 있어 삭제할 수 없습니다. 먼저 회원의 수강반을 변경해주세요.");
            $this->redirect('class_code/list');
            return;
        }

        $model->delete($id);
        $this->flash('success', '수강반이 삭제되었습니다.');
        $this->redirect('class_code/list');
    }
}
