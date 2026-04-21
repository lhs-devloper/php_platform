<?php
class DashboardModel extends Model
{
    public function getMemberStats()
    {
        $stmt = $this->db->query('SELECT status, COUNT(*) AS cnt FROM member GROUP BY status');
        $r = ['total' => 0, 'ACTIVE' => 0, 'PAUSED' => 0, 'HONORARY' => 0, 'WITHDRAWN' => 0];
        foreach ($stmt->fetchAll() as $row) {
            $r[$row['status']] = (int)$row['cnt'];
            $r['total'] += (int)$row['cnt'];
        }
        return $r;
    }

    public function getTodayCounts()
    {
        $today = date('Y-m-d');
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM posture_session WHERE DATE(captured_at) = ?");
        $stmt->execute([$today]);
        $posture = (int)$stmt->fetchColumn();

        $stmt = $this->db->prepare("SELECT COUNT(*) FROM foot_session WHERE DATE(captured_at) = ?");
        $stmt->execute([$today]);
        $foot = (int)$stmt->fetchColumn();

        return ['posture' => $posture, 'foot' => $foot, 'total' => $posture + $foot];
    }

    public function getConsultationEnabledCount()
    {
        return (int)$this->db->query("SELECT COUNT(*) FROM member WHERE consultation_enabled = 1")->fetchColumn();
    }

    public function getRecentPostureSessions($limit = 5)
    {
        $stmt = $this->db->prepare(
            'SELECT ps.*, m.name AS member_name FROM posture_session ps
             JOIN member m ON m.id = ps.member_id ORDER BY ps.captured_at DESC LIMIT ?'
        );
        $stmt->execute([(int)$limit]);
        return $stmt->fetchAll();
    }

    public function getRecentFootSessions($limit = 5)
    {
        $stmt = $this->db->prepare(
            'SELECT fs.*, m.name AS member_name FROM foot_session fs
             JOIN member m ON m.id = fs.member_id ORDER BY fs.captured_at DESC LIMIT ?'
        );
        $stmt->execute([(int)$limit]);
        return $stmt->fetchAll();
    }
}
