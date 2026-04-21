<?php
class AdminModel extends Model
{
    protected $table = 'central_admin';

    public function findByLoginId($loginId)
    {
        $stmt = $this->db->prepare('SELECT * FROM central_admin WHERE login_id = ? LIMIT 1');
        $stmt->execute([$loginId]);
        $row = $stmt->fetch();
        return $row !== false ? $row : null;
    }
}
