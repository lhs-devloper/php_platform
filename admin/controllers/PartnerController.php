<?php
class PartnerController extends Controller
{
    private $partnerModel;
    private $adminModel;
    private $tenantModel;

    public function __construct()
    {
        parent::__construct();
        $this->partnerModel = new PartnerModel();
        $this->adminModel = new PartnerAdminModel();
        $this->tenantModel = new PartnerTenantModel();
    }

    public function list()
    {
        $this->requireAuth();

        $keyword = $this->input('search', '');
        $status  = $this->input('status', '');
        $page    = max(1, (int)$this->input('page', 1));

        $result = $this->partnerModel->search($keyword, $status, $page, ITEMS_PER_PAGE);
        $pagination = new Pagination($result['total'], ITEMS_PER_PAGE, $page);

        $this->view('partner/list', [
            'pageTitle'  => '협력업체 관리',
            'partners'   => $result['rows'],
            'pagination' => $pagination,
            'keyword'    => $keyword,
            'status'     => $status,
        ]);
    }

    public function create()
    {
        $this->requireAuth();
        $this->requireRole(['SUPER_ADMIN', 'ADMIN', 'OPERATOR']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCsrf();
            $data = $this->getPartnerData();

            $v = new Validator($data);
            $v->required('company_name', '업체명');

            if (!$v->passes()) {
                $this->flash('danger', $v->firstError());
                $_SESSION['form_data'] = $data;
                $this->redirect('partner/create');
                return;
            }

            $id = $this->partnerModel->insert($data);
            $this->auditLog('CREATE', 'partner', $id, "협력업체 등록: {$data['company_name']}");
            $this->flash('success', '협력업체가 등록되었습니다.');
            $this->redirect('partner/detail', ['id' => $id]);
        }

        $formData = isset($_SESSION['form_data']) ? $_SESSION['form_data'] : [];
        unset($_SESSION['form_data']);

        $this->view('partner/form', [
            'pageTitle' => '협력업체 등록',
            'partner'   => $formData,
            'isEdit'    => false,
            'csrfToken' => Auth::generateCsrfToken(),
        ]);
    }

    public function edit()
    {
        $this->requireAuth();
        $this->requireRole(['SUPER_ADMIN', 'ADMIN', 'OPERATOR']);

        $id = (int)$this->input('id');
        $partner = $this->partnerModel->findById($id);
        if (!$partner) {
            $this->flash('danger', '협력업체를 찾을 수 없습니다.');
            $this->redirect('partner/list');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCsrf();
            $before = $partner;
            $data = $this->getPartnerData();

            $this->partnerModel->update($id, $data);
            $after = $this->partnerModel->findById($id);
            $this->auditLog('UPDATE', 'partner', $id, "협력업체 수정: {$data['company_name']}", $before, $after);
            $this->flash('success', '협력업체 정보가 수정되었습니다.');
            $this->redirect('partner/detail', ['id' => $id]);
        }

        $this->view('partner/form', [
            'pageTitle' => '협력업체 수정',
            'partner'   => $partner,
            'isEdit'    => true,
            'csrfToken' => Auth::generateCsrfToken(),
        ]);
    }

    public function detail()
    {
        $this->requireAuth();

        $id = (int)$this->input('id');
        $partner = $this->partnerModel->findById($id);
        if (!$partner) {
            $this->flash('danger', '협력업체를 찾을 수 없습니다.');
            $this->redirect('partner/list');
            return;
        }

        $this->view('partner/detail', [
            'pageTitle'       => $partner['company_name'],
            'partner'         => $partner,
            'admins'          => $this->adminModel->findByPartnerId($id),
            'mappedTenants'   => $this->tenantModel->findByPartnerId($id),
            'availableTenants'=> $this->tenantModel->findAvailableTenants($id),
            'csrfToken'       => Auth::generateCsrfToken(),
        ]);
    }

    public function delete()
    {
        $this->requireAuth();
        $this->requireRole(['SUPER_ADMIN', 'ADMIN']);
        $this->validateCsrf();

        $id = (int)$this->input('id');
        $partner = $this->partnerModel->findById($id);
        if ($partner) {
            $this->partnerModel->update($id, ['status' => 'TERMINATED']);
            $this->auditLog('DELETE', 'partner', $id, "협력업체 해지: {$partner['company_name']}", $partner);
            $this->flash('success', '협력업체가 해지 처리되었습니다.');
        }
        $this->redirect('partner/list');
    }

    public function saveAdmin()
    {
        $this->requireAuth();
        $this->requireRole(['SUPER_ADMIN', 'ADMIN', 'OPERATOR']);
        $this->validateCsrf();

        $partnerId = (int)$this->input('partner_id');
        $adminId   = (int)$this->input('admin_id');

        $data = [
            'partner_id' => $partnerId,
            'login_id'   => $this->input('admin_login_id', ''),
            'name'       => $this->input('admin_name', ''),
            'email'      => $this->input('admin_email', ''),
            'phone'      => $this->input('admin_phone', ''),
            'role'       => $this->input('admin_role', 'PARTNER_STAFF'),
            'is_active'  => 1,
        ];

        if ($adminId > 0) {
            // 수정 (비밀번호는 입력 시에만 변경)
            $pw = $this->input('admin_password', '');
            if ($pw !== '') {
                $data['password'] = hash(HASH_ALGO, $pw);
            }
            unset($data['partner_id']); // FK 변경 방지
            unset($data['login_id']);    // 로그인 ID 변경 방지
            unset($data['is_active']);   // 활성 상태는 토글에서만 변경
            $before = $this->adminModel->findById($adminId);
            $this->adminModel->update($adminId, $data);
            $after = $this->adminModel->findById($adminId);
            $this->auditLog('UPDATE', 'partner_admin', $adminId, "협력업체 관리자 수정: {$data['name']}", $before, $after);
        } else {
            // 등록 (비밀번호 필수)
            $pw = $this->input('admin_password', '');
            if ($pw === '') {
                $this->flash('danger', '비밀번호를 입력해주세요.');
                $this->redirect('partner/detail', ['id' => $partnerId, 'tab' => 'admins']);
                return;
            }
            $data['password'] = hash(HASH_ALGO, $pw);
            $newId = $this->adminModel->insert($data);
            $this->auditLog('CREATE', 'partner_admin', $newId, "협력업체 관리자 등록: {$data['name']}");
        }

        $this->flash('success', '관리자 정보가 저장되었습니다.');
        $this->redirect('partner/detail', ['id' => $partnerId, 'tab' => 'admins']);
    }

    /**
     * 관리자 계정 활성/비활성 토글
     */
    public function toggleAdmin()
    {
        $this->requireAuth();
        $this->requireRole(['SUPER_ADMIN', 'ADMIN']);
        $this->validateCsrf();

        $adminId   = (int)$this->input('id');
        $partnerId = (int)$this->input('partner_id');
        $admin     = $this->adminModel->findById($adminId);

        if ($admin) {
            $newState = $admin['is_active'] ? 0 : 1;
            $this->adminModel->update($adminId, ['is_active' => $newState]);
            $label = $newState ? '활성화' : '비활성화';
            $this->auditLog('UPDATE', 'partner_admin', $adminId, "협력업체 관리자 {$label}: {$admin['name']}");
            $this->flash('success', "관리자가 {$label}되었습니다.");
        }
        $this->redirect('partner/detail', ['id' => $partnerId, 'tab' => 'admins']);
    }

    /**
     * 관리자 계정 완전 삭제
     */
    public function deleteAdmin()
    {
        $this->requireAuth();
        $this->requireRole(['SUPER_ADMIN']);
        $this->validateCsrf();

        $adminId   = (int)$this->input('id');
        $partnerId = (int)$this->input('partner_id');
        $admin     = $this->adminModel->findById($adminId);

        if ($admin) {
            $this->adminModel->delete($adminId);
            $this->auditLog('DELETE', 'partner_admin', $adminId, "협력업체 관리자 삭제: {$admin['name']}", $admin);
            $this->flash('success', '관리자 계정이 삭제되었습니다.');
        }
        $this->redirect('partner/detail', ['id' => $partnerId, 'tab' => 'admins']);
    }

    public function addTenant()
    {
        $this->requireAuth();
        $this->requireRole(['SUPER_ADMIN', 'ADMIN', 'OPERATOR']);
        $this->validateCsrf();

        $partnerId = (int)$this->input('partner_id');
        $tenantId  = (int)$this->input('tenant_id');

        if ($partnerId > 0 && $tenantId > 0) {
            $this->tenantModel->add($partnerId, $tenantId);
            $this->auditLog('CREATE', 'partner_tenant', null, "협력업체-가맹점 연결: partner={$partnerId}, tenant={$tenantId}", null, null, $tenantId);
            $this->flash('success', '가맹점이 연결되었습니다.');
        }
        $this->redirect('partner/detail', ['id' => $partnerId, 'tab' => 'tenants']);
    }

    public function removeTenant()
    {
        $this->requireAuth();
        $this->requireRole(['SUPER_ADMIN', 'ADMIN']);
        $this->validateCsrf();

        $partnerId = (int)$this->input('partner_id');
        $tenantId  = (int)$this->input('tenant_id');

        if ($partnerId > 0 && $tenantId > 0) {
            $this->tenantModel->remove($partnerId, $tenantId);
            $this->auditLog('DELETE', 'partner_tenant', null, "협력업체-가맹점 연결 해제: partner={$partnerId}, tenant={$tenantId}", null, null, $tenantId);
            $this->flash('success', '가맹점 연결이 해제되었습니다.');
        }
        $this->redirect('partner/detail', ['id' => $partnerId, 'tab' => 'tenants']);
    }

    private function getPartnerData()
    {
        return [
            'company_name'    => $this->input('company_name', ''),
            'business_number' => $this->input('business_number', '') ?: null,
            'ceo_name'        => $this->input('ceo_name', ''),
            'phone'           => $this->input('phone', ''),
            'email'           => $this->input('email', ''),
            'address'         => $this->input('address', ''),
            'service_type'    => $this->input('service_type', 'BOTH'),
            'status'          => $this->input('status', 'PENDING'),
            'contract_start'  => $this->input('contract_start', '') ?: null,
            'contract_end'    => $this->input('contract_end', '') ?: null,
            'memo'            => $this->input('memo', '') ?: null,
        ];
    }
}
