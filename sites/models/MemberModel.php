<?php
class MemberModel extends Model
{
    protected $table = 'member';

    public function search($keyword = '', $status = '', $classCodeId = '', $instructorId = '', $page = 1, $perPage = 20)
    {
        $where = [];
        $params = [];

        if ($keyword !== '') {
            $where[] = '(m.name LIKE ? OR m.phone LIKE ?)';
            $params[] = "%{$keyword}%";
            $params[] = "%{$keyword}%";
        }
        if ($status !== '') { $where[] = 'm.status = ?'; $params[] = $status; }
        if ($classCodeId !== '') { $where[] = 'm.class_code_id = ?'; $params[] = $classCodeId; }
        if ($instructorId !== '') { $where[] = 'm.instructor_id = ?'; $params[] = $instructorId; }

        $w = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $stmt = $this->db->prepare("SELECT COUNT(*) FROM member m {$w}");
        $stmt->execute($params);
        $total = (int)$stmt->fetchColumn();

        $offset = ($page - 1) * $perPage;
        $sql = "SELECT m.*, c.name AS class_name, i.name AS instructor_name
                FROM member m
                LEFT JOIN class_code c ON c.id = m.class_code_id
                LEFT JOIN instructor i ON i.id = m.instructor_id
                {$w} ORDER BY m.created_at DESC LIMIT ? OFFSET ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_merge($params, [(int)$perPage, (int)$offset]));

        return ['rows' => $stmt->fetchAll(), 'total' => $total];
    }

    public function findByIdWithRelations($id)
    {
        $stmt = $this->db->prepare(
            'SELECT m.*, c.name AS class_name, i.name AS instructor_name
             FROM member m
             LEFT JOIN class_code c ON c.id = m.class_code_id
             LEFT JOIN instructor i ON i.id = m.instructor_id
             WHERE m.id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row !== false ? $row : null;
    }

    public function toggleConsultation($id)
    {
        $stmt = $this->db->prepare('UPDATE member SET consultation_enabled = 1 - consultation_enabled WHERE id = ?');
        return $stmt->execute([$id]);
    }
}
