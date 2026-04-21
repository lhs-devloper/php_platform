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
        $tenantModel = new TenantModel();

        $this->view('dashboard/partner', [
            'pageTitle'    => '대시보드',
            'tenantStats'  => $tenantModel->getStatsByIds($tenantIds),
            'tenants'      => $tenantModel->findByIdsWithDomain($tenantIds),
        ]);
    }
}
