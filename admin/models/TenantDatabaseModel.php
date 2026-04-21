<?php
class TenantDatabaseModel extends Model
{
    protected $table = 'tenant_database';

    public function findByTenantId($tenantId)
    {
        return $this->firstWhere('tenant_id', $tenantId);
    }
}
