<?php
class AuditLogModel extends Model
{
    protected $table = 'audit_log';

    public function findRecent($limit = 10)
    {
        $stmt = $this->db->prepare(
            'SELECT al.*, ca.name AS admin_name
             FROM audit_log al
             LEFT JOIN central_admin ca ON ca.id = al.admin_id
             ORDER BY al.created_at DESC
             LIMIT ?'
        );
        $stmt->execute([(int)$limit]);
        return $stmt->fetchAll();
    }
}
