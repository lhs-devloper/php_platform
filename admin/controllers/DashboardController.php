<?php
class DashboardController extends Controller
{
    public function index()
    {
        $this->requireAuth();

        if (Auth::isPartner()) {
            $this->partnerDashboard();
        } else {
            $this->centralDashboard();
        }
    }

    /**
     * 중앙관리자 대시보드
     */
    private function centralDashboard()
    {
        $dashModel = new DashboardModel();
        $auditModel = new AuditLogModel();

        $this->view('dashboard/index', [
            'pageTitle'      => '대시보드',
            'tenantStats'    => $dashModel->getTenantStats(),
            'partnerStats'   => $dashModel->getPartnerStats(),
            'pendingAccess'  => $dashModel->getPendingAccessRequestCount(),
            'recentTenants'  => $dashModel->getRecentTenants(5),
            'recentAudit'    => $auditModel->findRecent(10),
            'analysisStats'  => $dashModel->getAnalysisStats(),
        ]);
    }

    /**
     * 협력업체 대시보드
     */
    private function partnerDashboard()
    {
        $tenantIds = Auth::getPartnerTenantIds();
        $db = Database::getInstance();

        // 소속 가맹점 수 및 상태 통계
        $tenantStats = ['total' => 0, 'ACTIVE' => 0, 'PENDING' => 0, 'SUSPENDED' => 0];
        if (!empty($tenantIds)) {
            $placeholders = implode(',', array_fill(0, count($tenantIds), '?'));
            $stmt = $db->prepare("SELECT status, COUNT(*) AS cnt FROM tenant WHERE id IN ({$placeholders}) GROUP BY status");
            $stmt->execute($tenantIds);
            foreach ($stmt->fetchAll() as $row) {
                $tenantStats[$row['status']] = (int)$row['cnt'];
                $tenantStats['total'] += (int)$row['cnt'];
            }
        }

        // 소속 가맹점 목록
        $tenants = [];
        if (!empty($tenantIds)) {
            $placeholders = implode(',', array_fill(0, count($tenantIds), '?'));
            $stmt = $db->prepare(
                "SELECT t.*, td.domain AS site_domain
                 FROM tenant t
                 LEFT JOIN tenant_database td ON td.tenant_id = t.id AND td.status = 'ACTIVE'
                 WHERE t.id IN ({$placeholders})
                 ORDER BY t.company_name ASC"
            );
            $stmt->execute($tenantIds);
            $tenants = $stmt->fetchAll();
        }

        $this->view('dashboard/partner', [
            'pageTitle'    => '대시보드',
            'tenantStats'  => $tenantStats,
            'tenants'      => $tenants,
        ]);
    }
}
