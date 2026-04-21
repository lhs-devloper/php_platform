<?php
class FootSessionModel extends Model
{
    protected $table = 'foot_session';

    public function getByMember($memberId)
    {
        $stmt = $this->db->prepare(
            'SELECT fs.*, (SELECT COUNT(*) FROM foot_report fr WHERE fr.session_id = fs.id) AS has_report
             FROM foot_session fs WHERE fs.member_id = ? ORDER BY fs.captured_at DESC'
        );
        $stmt->execute([$memberId]);
        return $stmt->fetchAll();
    }
}
