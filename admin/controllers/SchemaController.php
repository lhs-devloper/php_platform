<?php
/**
 * 스키마 업데이트 컨트롤러
 * 운영 중인 테넌트 DB에 마이그레이션 SQL 적용 관리
 */
class SchemaController extends Controller
{
    private $schemaService;

    public function __construct()
    {
        parent::__construct();
        $this->schemaService = new SchemaUpdateService();
    }

    /**
     * 마이그레이션 목록 및 테넌트별 적용 현황
     */
    public function list()
    {
        $this->requireAuth();
        $this->requireRole(['SUPER_ADMIN', 'ADMIN']);

        $migrations = $this->schemaService->getAvailableMigrations();
        $tenantStatus = $this->schemaService->getStatusAll();

        // 요약 통계
        $totalTenants = count($tenantStatus);
        $upToDate = count(array_filter($tenantStatus, function ($t) { return $t['up_to_date']; }));
        $pending = $totalTenants - $upToDate;

        $this->view('schema/list', [
            'pageTitle'    => '스키마 업데이트',
            'migrations'   => $migrations,
            'tenantStatus' => $tenantStatus,
            'totalTenants' => $totalTenants,
            'upToDate'     => $upToDate,
            'pendingCount' => $pending,
            'csrfToken'    => Auth::generateCsrfToken(),
        ]);
    }

    /**
     * 단일 테넌트 마이그레이션 실행 (POST)
     */
    public function execute()
    {
        $this->requireAuth();
        $this->requireRole(['SUPER_ADMIN', 'ADMIN']);
        $this->validateCsrf();

        $tenantId = (int)$this->input('tenant_id');
        $adminId = Auth::user()['id'];

        $result = $this->schemaService->applyToTenant($tenantId, $adminId);

        $this->auditLog(
            'PROVISION',
            'tenant_database',
            $tenantId,
            '스키마 업데이트: ' . $result['message'],
            null,
            json_encode($result['results'], JSON_UNESCAPED_UNICODE),
            $tenantId
        );

        if ($result['success']) {
            $this->flash('success', $result['message']);
        } else {
            $this->flash('danger', $result['message']);
        }

        $this->redirect('schema/list');
    }

    /**
     * 전체 테넌트 일괄 마이그레이션 실행 (POST)
     */
    public function executeAll()
    {
        $this->requireAuth();
        $this->requireRole(['SUPER_ADMIN']);
        $this->validateCsrf();

        set_time_limit(600); // 테넌트가 많을 경우를 대비

        $adminId = Auth::user()['id'];
        $summary = $this->schemaService->applyToAll($adminId);

        $this->auditLog(
            'PROVISION',
            'tenant_database',
            null,
            sprintf('일괄 스키마 업데이트: 전체 %d, 성공 %d, 실패 %d, 스킵 %d',
                $summary['total'], $summary['success'], $summary['failed'], $summary['skipped']),
            null,
            null,
            null
        );

        $_SESSION['schema_update_result'] = $summary;
        $this->flash(
            $summary['failed'] === 0 ? 'success' : 'warning',
            sprintf('일괄 업데이트 완료: 성공 %d / 실패 %d / 최신 %d (전체 %d)',
                $summary['success'], $summary['failed'], $summary['skipped'], $summary['total'])
        );
        $this->redirect('schema/list');
    }
}
