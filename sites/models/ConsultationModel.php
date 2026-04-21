<?php
class ConsultationModel extends Model
{
    protected $table = 'consultation';

    public function getByMember($memberId)
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM consultation WHERE member_id = ? ORDER BY consulted_at DESC'
        );
        $stmt->execute([$memberId]);
        return $stmt->fetchAll();
    }
}
