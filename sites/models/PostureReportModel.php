<?php
class PostureReportModel extends Model
{
    protected $table = 'posture_report';

    public function getBySessionId($sessionId)
    {
        $stmt = $this->db->prepare('SELECT * FROM posture_report WHERE session_id = ? LIMIT 1');
        $stmt->execute([$sessionId]);
        $row = $stmt->fetch();
        return $row !== false ? $row : null;
    }
}
