<?php
class TenantContactModel extends Model
{
    protected $table = 'tenant_contact';

    public function findByTenantId($tenantId)
    {
        return $this->query()
            ->where('tenant_id', $tenantId)
            ->orderBy('is_primary DESC, id ASC')
            ->get();
    }
}
