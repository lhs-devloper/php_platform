<?php
class PartnerTenantModel extends Model
{
    protected $table = 'partner_tenant';

    public function findByPartnerId($partnerId)
    {
        return $this->query('pt')
            ->select('pt.*, t.company_name, t.status AS tenant_status, t.service_type')
            ->join('tenant t', 't.id = pt.tenant_id')
            ->whereColumn('pt.partner_id', $partnerId)
            ->orderBy('t.company_name ASC')
            ->get();
    }

    public function findAvailableTenants($partnerId)
    {
        return (new QueryBuilder('tenant'))
            ->select('id, company_name, status')
            ->whereNotIn('id', 'SELECT tenant_id FROM partner_tenant WHERE partner_id = ?', [$partnerId])
            ->whereColumn('status', '!=', 'TERMINATED')
            ->orderBy('company_name ASC')
            ->get();
    }

    public function add($partnerId, $tenantId)
    {
        return $this->query()->insert([
            'partner_id' => $partnerId,
            'tenant_id'  => $tenantId,
        ]);
    }

    public function remove($partnerId, $tenantId)
    {
        return $this->query()
            ->where('partner_id', $partnerId)
            ->where('tenant_id', $tenantId)
            ->delete();
    }
}
