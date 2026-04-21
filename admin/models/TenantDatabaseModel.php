<?php
class TenantDatabaseModel extends Model
{
    protected $table = 'tenant_database';

    public function findByTenantId($tenantId)
    {
        $stmt = $this->db->prepare('SELECT * FROM tenant_database WHERE tenant_id = ? LIMIT 1');
        $stmt->execute([$tenantId]);
        $row = $stmt->fetch();
        return $row !== false ? $row : null;
    }
}
