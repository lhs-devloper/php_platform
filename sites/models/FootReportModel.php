<?php
class FootReportModel extends Model
{
    protected $table = 'foot_report';

    public function getBySessionId($sessionId)
    {
        $stmt = $this->db->prepare('SELECT * FROM foot_report WHERE session_id = ? LIMIT 1');
        $stmt->execute([$sessionId]);
        $row = $stmt->fetch();
        return $row !== false ? $row : null;
    }
}
