<?php
class PartnerAdminModel extends Model
{
    protected $table = 'partner_admin';

    public function findByPartnerId($partnerId)
    {
        $stmt = $this->db->prepare('SELECT * FROM partner_admin WHERE partner_id = ? ORDER BY id ASC');
        $stmt->execute([$partnerId]);
        return $stmt->fetchAll();
    }
}
