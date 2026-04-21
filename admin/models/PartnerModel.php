<?php
class PartnerModel extends Model
{
    protected $table = 'partner';

    public function search($keyword = '', $status = '', $page = 1, $perPage = 20)
    {
        $where = [];
        $params = [];

        if ($keyword !== '') {
            $where[] = '(company_name LIKE ? OR business_number LIKE ? OR ceo_name LIKE ?)';
            $params[] = "%{$keyword}%";
            $params[] = "%{$keyword}%";
            $params[] = "%{$keyword}%";
        }
        if ($status !== '') {
            $where[] = 'status = ?';
            $params[] = $status;
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $stmt = $this->db->prepare("SELECT COUNT(*) FROM partner {$whereClause}");
        $stmt->execute($params);
        $total = (int)$stmt->fetchColumn();

        $offset = ($page - 1) * $perPage;
        $dataParams = array_merge($params, [(int)$perPage, (int)$offset]);
        $stmt = $this->db->prepare("SELECT * FROM partner {$whereClause} ORDER BY created_at DESC LIMIT ? OFFSET ?");
        $stmt->execute($dataParams);

        return ['rows' => $stmt->fetchAll(), 'total' => $total];
    }
}
