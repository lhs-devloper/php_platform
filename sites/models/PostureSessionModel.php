<?php
class PostureSessionModel extends Model
{
    protected $table = 'posture_session';

    public function getByMember($memberId)
    {
        $stmt = $this->db->prepare(
            'SELECT ps.*, (SELECT COUNT(*) FROM posture_report pr WHERE pr.session_id = ps.id) AS has_report
             FROM posture_session ps WHERE ps.member_id = ? ORDER BY ps.captured_at DESC'
        );
        $stmt->execute([$memberId]);
        return $stmt->fetchAll();
    }
}
