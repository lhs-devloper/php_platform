<?php
class InstructorModel extends Model
{
    protected $table = 'instructor';

    public function getActiveList()
    {
        return $this->db->query("SELECT id, name FROM instructor WHERE status = 'ACTIVE' ORDER BY name")->fetchAll();
    }
}
