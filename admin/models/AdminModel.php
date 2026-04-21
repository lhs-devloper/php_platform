<?php
class AdminModel extends Model
{
    protected $table = 'central_admin';

    public function findByLoginId($loginId)
    {
        return $this->firstWhere('login_id', $loginId);
    }
}
