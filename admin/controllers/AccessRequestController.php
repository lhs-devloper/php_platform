<?php
class AccessRequestController extends Controller
{
    private $requestModel;

    public function __construct()
    {
        parent::__construct();
        $this->requestModel = new AccessRequestModel();
    }

    public function list()
    {
        $this->requireAuth();

        $status = $this->input('status', '');
        $page   = max(1, (int)$this->input('page', 1));

        // 협력업체: 본인 업체 요청만 / 중앙관리자: 전체
        if (Auth::isPartner()) {
            $result = $this->requestModel->findByPartnerId(Auth::user()['partner_id'], $status, $page, ITEMS_PER_PAGE);
        } else {
            $result = $this->requestModel->findAllWithDetails($status, $page, ITEMS_PER_PAGE);
        }

        $pagination = new Pagination($result['total'], ITEMS_PER_PAGE, $page);

        $this->view('access_request/list', [
            'pageTitle'  => Auth::isPartner() ? '열람 요청' : '열람 요청 관리',
            'requests'   => $result['rows'],
            'pagination' => $pagination,
            'status'     => $status,
        ]);
    }

    public function detail()
    {
        $this->requireAuth();

        $id = (int)$this->input('id');
        $request = $this->requestModel->findByIdWithDetails($id);
        if (!$request) {
            $this->flash('danger', '요청을 찾을 수 없습니다.');
            $this->redirect('access_request/list');
            return;
        }

        // 협력업체는 본인 업체 요청만 열람 가능
        if (Auth::isPartner() && $request['partner_id'] != Auth::user()['partner_id']) {
            $this->flash('danger', '접근 권한이 없습니다.');
            $this->redirect('access_request/list');
            return;
        }

        $this->view('access_request/detail', [
            'pageTitle'  => '열람 요청 상세',
            'request'    => $request,
            'csrfToken'  => Auth::generateCsrfToken(),
        ]);
    }

    /**
     * 협력업체: 열람 요청 제출
     */
    public function create()
    {
        $this->requireAuth();
        if (!Auth::isPartner()) {
            $this->flash('danger', '협력업체 계정만 열람 요청을 할 수 있습니다.');
            $this->redirect('access_request/list');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCsrf();

            $user = Auth::user();
            $tenantId = (int)$this->input('tenant_id');
            $reason   = $this->input('reason', '');
            $scope    = $this->input('access_scope', 'REPORT_ONLY');

            if (!$tenantId || $reason === '') {
                $this->flash('danger', '대상 가맹점과 요청 사유를 입력해주세요.');
                $this->redirect('access_request/create');
                return;
            }

            $id = $this->requestModel->insert([
                'partner_id'          => $user['partner_id'],
                'requested_tenant_id' => $tenantId,
                'requester_admin_id'  => $user['id'],
                'reason'              => $reason,
                'access_scope'        => $scope,
                'status'              => 'PENDING',
            ]);

            $this->auditLog('CREATE', 'partner_access_request', $id,
                "열람 요청 제출: partner={$user['partner_id']}, tenant={$tenantId}");
            $this->flash('success', '열람 요청이 제출되었습니다. 관리자 승인을 기다려주세요.');
            $this->redirect('access_request/list');
            return;
        }

        // 요청 가능한 가맹점 목록 (전체 가맹점 중 TERMINATED 제외)
        $db = Database::getInstance();
        $stmt = $db->query("SELECT id, company_name, status FROM tenant WHERE status != 'TERMINATED' ORDER BY company_name ASC");
        $tenants = $stmt->fetchAll();

        $this->view('access_request/create', [
            'pageTitle' => '열람 요청',
            'tenants'   => $tenants,
            'csrfToken' => Auth::generateCsrfToken(),
        ]);
    }

    public function approve()
    {
        $this->requireAuth();
        $this->requireRole(['SUPER_ADMIN', 'ADMIN']);
        $this->validateCsrf();

        $id    = (int)$this->input('id');
        $start = $this->input('access_start', '');
        $end   = $this->input('access_end', '');

        if ($start === '' || $end === '') {
            $this->flash('danger', '열람 허용 기간을 지정해주세요.');
            $this->redirect('access_request/detail', ['id' => $id]);
            return;
        }

        $request = $this->requestModel->findByIdWithDetails($id);
        $this->requestModel->approve($id, Auth::user()['id'], $start, $end);
        $this->auditLog('UPDATE', 'partner_access_request', $id,
            "열람 요청 승인: {$request['partner_name']} → {$request['tenant_name']}", null, null, $request['requested_tenant_id']);

        // 협력업체 세션 캐시 무효화 (다음 접근 시 새로 로드)
        unset($_SESSION['partner_tenant_ids'], $_SESSION['partner_tenant_ids_ts']);

        $this->flash('success', '열람 요청이 승인되었습니다.');
        $this->redirect('access_request/detail', ['id' => $id]);
    }

    public function reject()
    {
        $this->requireAuth();
        $this->requireRole(['SUPER_ADMIN', 'ADMIN']);
        $this->validateCsrf();

        $id     = (int)$this->input('id');
        $reason = $this->input('reject_reason', '');

        $request = $this->requestModel->findByIdWithDetails($id);
        $this->requestModel->reject($id, Auth::user()['id'], $reason);
        $this->auditLog('UPDATE', 'partner_access_request', $id,
            "열람 요청 거절: {$request['partner_name']} → {$request['tenant_name']}", null, null, $request['requested_tenant_id']);
        $this->flash('success', '열람 요청이 거절되었습니다.');
        $this->redirect('access_request/detail', ['id' => $id]);
    }

    public function revoke()
    {
        $this->requireAuth();
        $this->requireRole(['SUPER_ADMIN', 'ADMIN']);
        $this->validateCsrf();

        $id = (int)$this->input('id');
        $request = $this->requestModel->findByIdWithDetails($id);
        $this->requestModel->revoke($id);

        unset($_SESSION['partner_tenant_ids'], $_SESSION['partner_tenant_ids_ts']);

        $this->auditLog('UPDATE', 'partner_access_request', $id,
            "열람 승인 철회: {$request['partner_name']} → {$request['tenant_name']}");
        $this->flash('success', '열람 승인이 철회되었습니다.');
        $this->redirect('access_request/detail', ['id' => $id]);
    }
}
