<?php
class PartnerTenantModel extends Model
{
    protected $table = 'partner_tenant';

    public function findByPartnerId($partnerId)
    {
        $stmt = $this->db->prepare(
            'SELECT pt.*, t.company_name, t.status AS tenant_status, t.service_type
             FROM partner_tenant pt
             JOIN tenant t ON t.id = pt.tenant_id
             WHERE pt.partner_id = ?
             ORDER BY t.company_name ASC'
        );
        $stmt->execute([$partnerId]);
        return $stmt->fetchAll();
    }

    public function findAvailableTenants($partnerId)
    {
        $stmt = $this->db->prepare(
            "SELECT id, company_name, status FROM tenant
             WHERE id NOT IN (SELECT tenant_id FROM partner_tenant WHERE partner_id = ?)
             AND status != 'TERMINATED'
             ORDER BY company_name ASC"
        );
        $stmt->execute([$partnerId]);
        return $stmt->fetchAll();
    }

    public function add($partnerId, $tenantId)
    {
        $stmt = $this->db->prepare('INSERT INTO partner_tenant (partner_id, tenant_id) VALUES (?, ?)');
        $stmt->execute([$partnerId, $tenantId]);
        return (int)$this->db->lastInsertId();
    }

    public function remove($partnerId, $tenantId)
    {
        $stmt = $this->db->prepare('DELETE FROM partner_tenant WHERE partner_id = ? AND tenant_id = ?');
        return $stmt->execute([$partnerId, $tenantId]);
    }
}
