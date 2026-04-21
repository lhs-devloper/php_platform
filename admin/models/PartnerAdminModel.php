<?php
class PartnerAdminModel extends Model
{
    protected $table = 'partner_admin';

    public function findByPartnerId($partnerId)
    {
        return $this->query()
            ->where('partner_id', $partnerId)
            ->orderBy('id ASC')
            ->get();
    }
}
