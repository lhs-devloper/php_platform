<?php
class TenantContactModel extends Model
{
    protected $table = 'tenant_contact';

    public function findByTenantId($tenantId)
    {
        $stmt = $this->db->prepare('SELECT * FROM tenant_contact WHERE tenant_id = ? ORDER BY is_primary DESC, id ASC');
        $stmt->execute([$tenantId]);
        return $stmt->fetchAll();
    }
}
