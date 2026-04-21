<?php
class AuditLogModel extends Model
{
    protected $table = 'audit_log';

    public function findRecent($limit = 10)
    {
        return $this->query('al')
            ->select('al.*, ca.name AS admin_name')
            ->leftJoin('central_admin ca', 'ca.id = al.admin_id')
            ->orderBy('al.created_at DESC')
            ->limit((int)$limit)
            ->get();
    }
}
