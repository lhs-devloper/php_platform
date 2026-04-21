<?php
class TenantModel extends Model
{
    protected $table = 'tenant';

    /**
     * @param array $tenantIds  빈 배열이면 전체, 값이 있으면 해당 ID만 필터
     */
    public function search($keyword = '', $status = '', $serviceType = '', $page = 1, $perPage = 20, array $tenantIds = [])
    {
        $where = [];
        $params = [];

        // 협력업체 소속 가맹점만 필터
        if (!empty($tenantIds)) {
            $placeholders = implode(',', array_fill(0, count($tenantIds), '?'));
            $where[] = "t.id IN ({$placeholders})";
            $params = array_merge($params, $tenantIds);
        }

        if ($keyword !== '') {
            $where[] = '(t.company_name LIKE ? OR t.business_number LIKE ? OR t.ceo_name LIKE ?)';
            $params[] = "%{$keyword}%";
            $params[] = "%{$keyword}%";
            $params[] = "%{$keyword}%";
        }
        if ($status !== '') {
            $where[] = 't.status = ?';
            $params[] = $status;
        }
        if ($serviceType !== '') {
            $where[] = 't.service_type = ?';
            $params[] = $serviceType;
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        // 카운트
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM tenant t {$whereClause}");
        $stmt->execute($params);
        $total = (int)$stmt->fetchColumn();

        // 데이터 (도메인 정보 포함)
        $offset = ($page - 1) * $perPage;
        $dataParams = array_merge($params, [(int)$perPage, (int)$offset]);
        $stmt = $this->db->prepare(
            "SELECT t.*, td.domain AS site_domain
             FROM tenant t
             LEFT JOIN tenant_database td ON td.tenant_id = t.id AND td.status = 'ACTIVE'
             {$whereClause} ORDER BY t.created_at DESC LIMIT ? OFFSET ?"
        );
        $stmt->execute($dataParams);
        $rows = $stmt->fetchAll();

        return ['rows' => $rows, 'total' => $total];
    }

    public function countByStatus()
    {
        $stmt = $this->db->query('SELECT status, COUNT(*) AS cnt FROM tenant GROUP BY status');
        $result = [];
        foreach ($stmt->fetchAll() as $row) {
            $result[$row['status']] = (int)$row['cnt'];
        }
        return $result;
    }
}
