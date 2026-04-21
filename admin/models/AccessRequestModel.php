<?php
class AccessRequestModel extends Model
{
    protected $table = 'partner_access_request';

    public function findAllWithDetails($status = '', $page = 1, $perPage = 20)
    {
        $where = [];
        $params = [];

        if ($status !== '') {
            $where[] = 'par.status = ?';
            $params[] = $status;
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $stmt = $this->db->prepare("SELECT COUNT(*) FROM partner_access_request par {$whereClause}");
        $stmt->execute($params);
        $total = (int)$stmt->fetchColumn();

        $offset = ($page - 1) * $perPage;
        $sql = "SELECT par.*,
                       p.company_name AS partner_name,
                       t.company_name AS tenant_name,
                       pa.name AS requester_name,
                       ca.name AS approver_name
                FROM partner_access_request par
                LEFT JOIN partner p ON p.id = par.partner_id
                LEFT JOIN tenant t ON t.id = par.requested_tenant_id
                LEFT JOIN partner_admin pa ON pa.id = par.requester_admin_id
                LEFT JOIN central_admin ca ON ca.id = par.approved_by
                {$whereClause}
                ORDER BY par.created_at DESC
                LIMIT ? OFFSET ?";
        $dataParams = array_merge($params, [(int)$perPage, (int)$offset]);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($dataParams);

        return ['rows' => $stmt->fetchAll(), 'total' => $total];
    }

    public function findByIdWithDetails($id)
    {
        $sql = "SELECT par.*,
                       p.company_name AS partner_name, p.phone AS partner_phone,
                       t.company_name AS tenant_name, t.status AS tenant_status,
                       pa.name AS requester_name, pa.login_id AS requester_login_id,
                       ca.name AS approver_name
                FROM partner_access_request par
                LEFT JOIN partner p ON p.id = par.partner_id
                LEFT JOIN tenant t ON t.id = par.requested_tenant_id
                LEFT JOIN partner_admin pa ON pa.id = par.requester_admin_id
                LEFT JOIN central_admin ca ON ca.id = par.approved_by
                WHERE par.id = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row !== false ? $row : null;
    }

    public function approve($id, $approvedBy, $start, $end)
    {
        $stmt = $this->db->prepare(
            "UPDATE partner_access_request
             SET status = 'APPROVED', approved_by = ?, access_start = ?, access_end = ?, processed_at = NOW()
             WHERE id = ?"
        );
        return $stmt->execute([$approvedBy, $start, $end, $id]);
    }

    public function reject($id, $approvedBy, $reason)
    {
        $stmt = $this->db->prepare(
            "UPDATE partner_access_request
             SET status = 'REJECTED', approved_by = ?, reject_reason = ?, processed_at = NOW()
             WHERE id = ?"
        );
        return $stmt->execute([$approvedBy, $reason, $id]);
    }

    public function revoke($id)
    {
        $stmt = $this->db->prepare(
            "UPDATE partner_access_request SET status = 'REVOKED', processed_at = NOW() WHERE id = ?"
        );
        return $stmt->execute([$id]);
    }

    public function countPending()
    {
        $stmt = $this->db->query("SELECT COUNT(*) FROM partner_access_request WHERE status = 'PENDING'");
        return (int)$stmt->fetchColumn();
    }

    /**
     * 협력업체별 열람 요청 목록 (본인 업체 것만)
     */
    public function findByPartnerId(int $partnerId, string $status = '', int $page = 1, int $perPage = 20): array
    {
        $where = ['par.partner_id = ?'];
        $params = [$partnerId];

        if ($status !== '') {
            $where[] = 'par.status = ?';
            $params[] = $status;
        }

        $whereClause = 'WHERE ' . implode(' AND ', $where);

        $stmt = $this->db->prepare("SELECT COUNT(*) FROM partner_access_request par {$whereClause}");
        $stmt->execute($params);
        $total = (int)$stmt->fetchColumn();

        $offset = ($page - 1) * $perPage;
        $sql = "SELECT par.*,
                       p.company_name AS partner_name,
                       t.company_name AS tenant_name,
                       pa.name AS requester_name,
                       ca.name AS approver_name
                FROM partner_access_request par
                LEFT JOIN partner p ON p.id = par.partner_id
                LEFT JOIN tenant t ON t.id = par.requested_tenant_id
                LEFT JOIN partner_admin pa ON pa.id = par.requester_admin_id
                LEFT JOIN central_admin ca ON ca.id = par.approved_by
                {$whereClause}
                ORDER BY par.created_at DESC
                LIMIT ? OFFSET ?";
        $dataParams = array_merge($params, [(int)$perPage, (int)$offset]);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($dataParams);

        return ['rows' => $stmt->fetchAll(), 'total' => $total];
    }
}
