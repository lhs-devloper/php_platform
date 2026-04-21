<?php
class DashboardController extends Controller
{
    public function index()
    {
        $this->requireAuth();
        $dm = new DashboardModel();
        $this->view('dashboard/index', [
            'pageTitle'       => '대시보드',
            'memberStats'     => $dm->getMemberStats(),
            'todayCounts'     => $dm->getTodayCounts(),
            'consultationCnt' => $dm->getConsultationEnabledCount(),
            'recentPosture'   => $dm->getRecentPostureSessions(5),
            'recentFoot'      => $dm->getRecentFootSessions(5),
        ]);
    }
}
