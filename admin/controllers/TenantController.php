<?php
class TenantController extends Controller
{
    private $tenantModel;
    private $contactModel;
    private $dbModel;

    public function __construct()
    {
        parent::__construct();
        $this->tenantModel = new TenantModel();
        $this->contactModel = new TenantContactModel();
        $this->dbModel = new TenantDatabaseModel();
    }

    /**
     * 가맹점 목록
     */
    public function list()
    {
        $this->requireAuth();

        $keyword     = $this->input('search', '');
        $status      = $this->input('status', '');
        $serviceType = $this->input('service_type', '');
        $page        = max(1, (int)$this->input('page', 1));

        // 협력업체는 소속 가맹점만 조회
        $tenantIds = Auth::isPartner() ? Auth::getPartnerTenantIds() : [];

        $result = $this->tenantModel->search($keyword, $status, $serviceType, $page, ITEMS_PER_PAGE, $tenantIds);
        $pagination = new Pagination($result['total'], ITEMS_PER_PAGE, $page);

        $this->view('tenant/list', [
            'pageTitle'   => Auth::isPartner() ? '소속 가맹점' : '가맹점 관리',
            'tenants'     => $result['rows'],
            'pagination'  => $pagination,
            'keyword'     => $keyword,
            'status'      => $status,
            'serviceType' => $serviceType,
        ]);
    }

    /**
     * 가맹점 등록/수정 폼 + 처리
     */
    public function create()
    {
        $this->requireAuth();
        $this->requireRole(['SUPER_ADMIN', 'ADMIN', 'OPERATOR']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCsrf();
            $data = $this->getTenantData();

            $v = new Validator($data);
            $v->required('company_name', '업체명')
              ->maxLength('company_name', 100, '업체명')
              ->inList('status', ['PENDING','ACTIVE','SUSPENDED','TERMINATED'], '상태')
              ->inList('service_type', ['POSTURE','FOOT','BOTH'], '서비스 유형');

            if (!$v->passes()) {
                $this->flash('danger', $v->firstError());
                $_SESSION['form_data'] = $data;
                $this->redirect('tenant/create');
                return;
            }

            $id = $this->tenantModel->insert($data);
            $this->auditLog('CREATE', 'tenant', $id, "가맹점 등록: {$data['company_name']}");

            // 자동 DB 프로비저닝 (auto_provision 체크 시)
            $autoProvision = $this->input('auto_provision');
            if ($autoProvision) {
                $slug = strtolower(trim($this->input('slug', '')));

                // 슬러그 유효성 검증
                $slugError = ProvisionService::validateSlug($slug);
                if ($slugError) {
                    $this->flash('warning', "가맹점은 등록되었으나 프로비저닝 실패: {$slugError}");
                    $this->redirect('tenant/detail', ['id' => $id, 'tab' => 'database']);
                    return;
                }

                $tenant = $this->tenantModel->findById($id);
                $provision = new ProvisionService();
                $result = $provision->provision($id, $tenant, $slug, Auth::user()['id']);

                if ($result['success']) {
                    $this->auditLog('PROVISION', 'tenant_database', $id,
                        "DB 자동 프로비저닝 완료: {$result['db_name']} → {$result['domain']}", null, null, $id);
                    $this->flash('success', "가맹점이 등록되었습니다. {$result['domain']} 접속 가능합니다.");
                } else {
                    $this->flash('warning', "가맹점은 등록되었으나 프로비저닝에 실패했습니다: {$result['message']}");
                }
            } else {
                $this->flash('success', '가맹점이 등록되었습니다. (DB 수동 프로비저닝 필요)');
            }

            $this->redirect('tenant/detail', ['id' => $id]);
        }

        $formData = isset($_SESSION['form_data']) ? $_SESSION['form_data'] : [];
        unset($_SESSION['form_data']);

        $this->view('tenant/form', [
            'pageTitle' => '가맹점 등록',
            'tenant'    => $formData,
            'isEdit'    => false,
            'csrfToken' => Auth::generateCsrfToken(),
        ]);
    }

    public function edit()
    {
        $this->requireAuth();
        $this->requireRole(['SUPER_ADMIN', 'ADMIN', 'OPERATOR']);

        $id = (int)$this->input('id');
        $tenant = $this->tenantModel->findById($id);
        if (!$tenant) {
            $this->flash('danger', '가맹점을 찾을 수 없습니다.');
            $this->redirect('tenant/list');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCsrf();
            $before = $tenant;
            $data = $this->getTenantData();

            $v = new Validator($data);
            $v->required('company_name', '업체명');

            if (!$v->passes()) {
                $this->flash('danger', $v->firstError());
                $this->redirect('tenant/edit', ['id' => $id]);
                return;
            }

            $this->tenantModel->update($id, $data);
            $after = $this->tenantModel->findById($id);
            $this->auditLog('UPDATE', 'tenant', $id, "가맹점 수정: {$data['company_name']}", $before, $after, $id);
            $this->flash('success', '가맹점 정보가 수정되었습니다.');
            $this->redirect('tenant/detail', ['id' => $id]);
        }

        $this->view('tenant/form', [
            'pageTitle' => '가맹점 수정',
            'tenant'    => $tenant,
            'isEdit'    => true,
            'csrfToken' => Auth::generateCsrfToken(),
        ]);
    }

    /**
     * 가맹점 상세
     */
    public function detail()
    {
        $this->requireAuth();

        $id = (int)$this->input('id');
        $tenant = $this->tenantModel->findById($id);
        if (!$tenant) {
            $this->flash('danger', '가맹점을 찾을 수 없습니다.');
            $this->redirect('tenant/list');
            return;
        }

        // 협력업체는 소속 가맹점만 접근 가능
        if (!Auth::canAccessTenant($id)) {
            $this->flash('danger', '접근 권한이 없는 가맹점입니다.');
            $this->redirect('tenant/list');
            return;
        }

        $contacts = $this->contactModel->findByTenantId($id);
        $database = $this->dbModel->findByTenantId($id);

        $this->view('tenant/detail', [
            'pageTitle' => $tenant['company_name'],
            'tenant'    => $tenant,
            'contacts'  => $contacts,
            'database'  => $database,
            'csrfToken' => Auth::generateCsrfToken(),
        ]);
    }

    /**
     * 수동 DB 프로비저닝 (상세 페이지 DB탭에서 실행)
     */
    public function provision()
    {
        $this->requireAuth();
        $this->requireRole(['SUPER_ADMIN', 'ADMIN']);
        $this->validateCsrf();

        $id = (int)$this->input('id');
        $tenant = $this->tenantModel->findById($id);
        if (!$tenant) {
            $this->flash('danger', '가맹점을 찾을 수 없습니다.');
            $this->redirect('tenant/list');
            return;
        }

        // 이미 DB가 있는지 확인
        $existing = $this->dbModel->findByTenantId($id);
        if ($existing && $existing['status'] === 'ACTIVE') {
            $this->flash('warning', '이미 활성화된 DB 인스턴스가 존재합니다.');
            $this->redirect('tenant/detail', ['id' => $id, 'tab' => 'database']);
            return;
        }

        $slug = strtolower(trim($this->input('slug', '')));
        $slugError = ProvisionService::validateSlug($slug);
        if ($slugError) {
            $this->flash('danger', $slugError);
            $this->redirect('tenant/detail', ['id' => $id, 'tab' => 'database']);
            return;
        }

        $provision = new ProvisionService();
        $result = $provision->provision($id, $tenant, $slug, Auth::user()['id']);

        if ($result['success']) {
            $this->auditLog('PROVISION', 'tenant_database', $id,
                "DB 프로비저닝 완료: {$result['db_name']} → {$result['domain']}", null, null, $id);
            $this->flash('success', "{$result['domain']} 접속 가능합니다.");
        } else {
            $this->flash('danger', "프로비저닝 실패: {$result['message']}");
        }

        $this->redirect('tenant/detail', ['id' => $id, 'tab' => 'database']);
    }

    /**
     * 가맹점 해지 (소프트 딜리트)
     */
    public function delete()
    {
        $this->requireAuth();
        $this->requireRole(['SUPER_ADMIN', 'ADMIN']);
        $this->validateCsrf();

        $id = (int)$this->input('id');
        $tenant = $this->tenantModel->findById($id);
        if ($tenant) {
            $this->tenantModel->update($id, ['status' => 'TERMINATED']);
            $this->auditLog('DELETE', 'tenant', $id, "가맹점 해지: {$tenant['company_name']}", $tenant, null, $id);
            $this->flash('success', '가맹점이 해지 처리되었습니다.');
        }
        $this->redirect('tenant/list');
    }

    /**
     * 가맹점 완전 삭제 (DB DROP + 레코드 삭제) - SUPER_ADMIN 전용
     */
    public function destroy()
    {
        $this->requireAuth();
        $this->requireRole(['SUPER_ADMIN']);
        $this->validateCsrf();

        $id = (int)$this->input('id');
        $tenant = $this->tenantModel->findById($id);
        if (!$tenant) {
            $this->flash('danger', '가맹점을 찾을 수 없습니다.');
            $this->redirect('tenant/list');
            return;
        }

        $database = $this->dbModel->findByTenantId($id);
        $adminId = Auth::user()['id'];

        // 1. DB가 있으면 DROP
        if ($database) {
            $provision = new ProvisionService();
            $result = $provision->destroy($id, $database, $adminId);
            if (!$result['success']) {
                $this->flash('danger', $result['message']);
                $this->redirect('tenant/detail', ['id' => $id]);
                return;
            }
        }

        // 2. FK 종속 테이블 데이터 순서대로 삭제
        $db = Database::getInstance();
        $dependentTables = [
            'provision_log',
            'partner_access_log',
            'partner_access_request',
            'partner_tenant',
            'usage_daily',
            'payment',
            'subscription',
            'inquiry',
            'notice',
            'tenant_contact',
            'tenant_database',
        ];
        foreach ($dependentTables as $table) {
            $col = ($table === 'partner_access_request') ? 'requested_tenant_id' : 'tenant_id';
            $stmt = $db->prepare("DELETE FROM `{$table}` WHERE `{$col}` = ?");
            $stmt->execute([$id]);
        }

        // 3. 감사 로그 기록 (tenant 삭제 전에 기록 — tenant_id FK가 audit_log에 없으므로 OK)
        $this->auditLog('DESTROY', 'tenant', $id,
            "가맹점 완전 삭제: {$tenant['company_name']}" . ($database ? " (DB: {$database['db_name']} DROP)" : ''),
            $tenant);

        // 4. 가맹점 레코드 삭제
        $this->tenantModel->delete($id);

        $this->flash('success', "'{$tenant['company_name']}' 가맹점이 완전히 삭제되었습니다." . ($database ? " (DB: {$database['db_name']} 삭제됨)" : ''));
        $this->redirect('tenant/list');
    }

    /**
     * 담당자 저장 (등록/수정)
     */
    public function saveContact()
    {
        $this->requireAuth();
        $this->requireRole(['SUPER_ADMIN', 'ADMIN', 'OPERATOR']);
        $this->validateCsrf();

        $tenantId  = (int)$this->input('tenant_id');
        $contactId = (int)$this->input('contact_id');

        $data = [
            'tenant_id'  => $tenantId,
            'name'       => $this->input('contact_name', ''),
            'phone'      => $this->input('contact_phone', ''),
            'email'      => $this->input('contact_email', ''),
            'role'       => $this->input('contact_role', 'MANAGER'),
            'is_primary' => $this->input('is_primary') ? 1 : 0,
        ];

        if ($contactId > 0) {
            $this->contactModel->update($contactId, $data);
            $this->auditLog('UPDATE', 'tenant_contact', $contactId, "담당자 수정: {$data['name']}", null, null, $tenantId);
        } else {
            $newId = $this->contactModel->insert($data);
            $this->auditLog('CREATE', 'tenant_contact', $newId, "담당자 등록: {$data['name']}", null, null, $tenantId);
        }

        $this->flash('success', '담당자 정보가 저장되었습니다.');
        $this->redirect('tenant/detail', ['id' => $tenantId, 'tab' => 'contacts']);
    }

    /**
     * 담당자 삭제
     */
    public function deleteContact()
    {
        $this->requireAuth();
        $this->requireRole(['SUPER_ADMIN', 'ADMIN']);
        $this->validateCsrf();

        $contactId = (int)$this->input('id');
        $tenantId  = (int)$this->input('tenant_id');
        $contact   = $this->contactModel->findById($contactId);

        if ($contact) {
            $this->contactModel->delete($contactId);
            $this->auditLog('DELETE', 'tenant_contact', $contactId, "담당자 삭제: {$contact['name']}", $contact, null, $tenantId);
            $this->flash('success', '담당자가 삭제되었습니다.');
        }
        $this->redirect('tenant/detail', ['id' => $tenantId, 'tab' => 'contacts']);
    }

    /**
     * 레거시 DB 마이그레이션 (SQL 파일 업로드 → 데이터 이관)
     */
    public function migrate()
    {
        $this->requireAuth();
        $this->requireRole(['SUPER_ADMIN', 'ADMIN']);
        $this->validateCsrf();

        $id = (int)$this->input('id');
        $tenant = $this->tenantModel->findById($id);
        if (!$tenant) {
            $this->flash('danger', '가맹점을 찾을 수 없습니다.');
            $this->redirect('tenant/list');
            return;
        }

        $database = $this->dbModel->findByTenantId($id);
        if (!$database || $database['status'] !== 'ACTIVE') {
            $this->flash('danger', '프로비저닝된 DB가 없습니다. 먼저 DB를 생성해주세요.');
            $this->redirect('tenant/detail', ['id' => $id, 'tab' => 'database']);
            return;
        }

        // 파일 업로드 검증
        if (!isset($_FILES['sql_file']) || $_FILES['sql_file']['error'] !== UPLOAD_ERR_OK) {
            $this->flash('danger', 'SQL 파일을 업로드해주세요.');
            $this->redirect('tenant/detail', ['id' => $id, 'tab' => 'database']);
            return;
        }

        $file = $_FILES['sql_file'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext !== 'sql') {
            $this->flash('danger', '.sql 파일만 업로드 가능합니다.');
            $this->redirect('tenant/detail', ['id' => $id, 'tab' => 'database']);
            return;
        }

        // 파일 크기 제한 (200MB)
        if ($file['size'] > 200 * 1024 * 1024) {
            $this->flash('danger', '파일 크기가 200MB를 초과합니다.');
            $this->redirect('tenant/detail', ['id' => $id, 'tab' => 'database']);
            return;
        }

        $migration = new MigrationService();
        $result = $migration->migrate($id, $database, $file['tmp_name'], Auth::user()['id']);

        if ($result['success']) {
            $this->auditLog('MIGRATE', 'tenant_database', $id,
                "레거시 데이터 마이그레이션 완료: " . json_encode($result['counts'], JSON_UNESCAPED_UNICODE),
                null, null, $id);
        }

        // 결과를 세션에 저장하여 뷰에서 표시
        $_SESSION['migration_result'] = $result;
        $this->redirect('tenant/detail', ['id' => $id, 'tab' => 'database']);
    }

    /**
     * 가맹점 사이트 바로 접속 (자동 로그인 토큰 발급)
     * 협력업체는 사이트 바로접속 불가
     */
    public function accessSite()
    {
        $this->requireAuth();

        if (Auth::isPartner()) {
            $this->flash('danger', '협력업체 계정은 사이트 바로접속 권한이 없습니다.');
            $this->redirect('tenant/list');
            return;
        }

        $id = (int)$this->input('id');
        $database = $this->dbModel->findByTenantId($id);

        if (!$database || !$database['domain']) {
            $this->flash('danger', '프로비저닝된 사이트가 없습니다.');
            $this->redirect('tenant/detail', ['id' => $id]);
            return;
        }

        // HMAC 서명 토큰 생성 (60초 유효)
        $timestamp = time();
        $adminUser = Auth::user();
        $payload = base64_encode(json_encode([
            'tenant_id' => $id,
            'admin_id'  => $adminUser['id'],
            'admin_name'=> $adminUser['name'],
            'ts'        => $timestamp,
        ]));
        $signature = hash_hmac('sha256', $payload, ADMIN_ACCESS_SECRET);
        $token = $payload . '.' . $signature;

        $url = 'http://' . $database['domain'] . '/index.php?route=auth/admin_access&token=' . urlencode($token);
        header('Location: ' . $url);
        exit;
    }

    private function getTenantData()
    {
        return [
            'company_name'    => $this->input('company_name', ''),
            'business_number' => $this->input('business_number', '') ?: null,
            'ceo_name'        => $this->input('ceo_name', ''),
            'phone'           => $this->input('phone', ''),
            'email'           => $this->input('email', ''),
            'zipcode'         => $this->input('zipcode', '') ?: null,
            'address'         => $this->input('address', ''),
            'address_detail'  => $this->input('address_detail', '') ?: null,
            'status'          => $this->input('status', 'PENDING'),
            'service_type'    => $this->input('service_type', 'BOTH'),
            'contract_start'  => $this->input('contract_start', '') ?: null,
            'contract_end'    => $this->input('contract_end', '') ?: null,
            'memo'            => $this->input('memo', '') ?: null,
        ];
    }
}
