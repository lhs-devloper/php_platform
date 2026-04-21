<?php
class FootController extends Controller
{
    public function report()
    {
        $this->requireAuth();
        $sessionId = (int)$this->input('id');

        $session = (new FootSessionModel())->findById($sessionId);
        if (!$session) { $this->flash('danger', '세션을 찾을 수 없습니다.'); $this->redirect('dashboard'); return; }

        $member = (new MemberModel())->findById($session['member_id']);
        $report = (new FootReportModel())->getBySessionId($sessionId);

        $this->view('foot/report', [
            'pageTitle' => 'AIoT족부분석 리포트',
            'session'   => $session,
            'member'    => $member,
            'report'    => $report,
        ]);
    }

    public function compare()
    {
        $this->requireAuth();
        $s1 = (int)$this->input('s1');
        $s2 = (int)$this->input('s2');

        $sm = new FootSessionModel();
        $rm = new FootReportModel();

        $session1 = $sm->findById($s1);
        $session2 = $sm->findById($s2);
        if (!$session1 || !$session2) { $this->flash('danger', '세션을 찾을 수 없습니다.'); $this->redirect('dashboard'); return; }

        if ($session1['captured_at'] > $session2['captured_at']) {
            list($session1, $session2) = [$session2, $session1];
        }

        $member = (new MemberModel())->findById($session1['member_id']);

        $this->view('foot/compare', [
            'pageTitle' => 'AIoT족부분석 전/후 비교',
            'member'    => $member,
            'sessionA'  => $session1,
            'sessionB'  => $session2,
            'reportA'   => $rm->getBySessionId($session1['id']),
            'reportB'   => $rm->getBySessionId($session2['id']),
        ]);
    }
}
