<?php
class NoticeModel extends Model
{
    protected $table = 'notice';

    public function search($keyword = '', $targetType = '', $isPublished = '', $page = 1, $perPage = 20)
    {
        $where = [];
        $params = [];

        if ($keyword !== '') {
            $where[] = '(n.title LIKE ? OR n.content LIKE ?)';
            $params[] = "%{$keyword}%";
            $params[] = "%{$keyword}%";
        }
        if ($targetType !== '') { $where[] = 'n.target_type = ?'; $params[] = $targetType; }
        if ($isPublished !== '') { $where[] = 'n.is_published = ?'; $params[] = (int)$isPublished; }

        $w = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $stmt = $this->db->prepare("SELECT COUNT(*) FROM notice n {$w}");
        $stmt->execute($params);
        $total = (int)$stmt->fetchColumn();

        $offset = ($page - 1) * $perPage;
        $sql = "SELECT n.*, ca.name AS admin_name,
                       (SELECT GROUP_CONCAT(t2.company_name SEPARATOR ', ')
                        FROM notice_tenant nt2
                        JOIN tenant t2 ON t2.id = nt2.tenant_id
                        WHERE nt2.notice_id = n.id) AS tenant_names
                FROM notice n
                LEFT JOIN central_admin ca ON ca.id = n.admin_id
                {$w} ORDER BY n.is_pinned DESC, n.created_at DESC LIMIT ? OFFSET ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_merge($params, [(int)$perPage, (int)$offset]));

        return ['rows' => $stmt->fetchAll(), 'total' => $total];
    }

    public function findByIdWithRelations($id)
    {
        $stmt = $this->db->prepare(
            "SELECT n.*, ca.name AS admin_name,
                    (SELECT GROUP_CONCAT(t2.company_name SEPARATOR ', ')
                     FROM notice_tenant nt2
                     JOIN tenant t2 ON t2.id = nt2.tenant_id
                     WHERE nt2.notice_id = n.id) AS tenant_names
             FROM notice n
             LEFT JOIN central_admin ca ON ca.id = n.admin_id
             WHERE n.id = ? LIMIT 1"
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row !== false ? $row : null;
    }

    /**
     * 공지에 연결된 가맹점 ID 목록 조회
     */
    public function getTenantIds(int $noticeId): array
    {
        $stmt = $this->db->prepare('SELECT tenant_id FROM notice_tenant WHERE notice_id = ?');
        $stmt->execute([$noticeId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * 공지-가맹점 매핑 저장 (기존 삭제 후 재삽입)
     */
    public function saveTenantIds(int $noticeId, array $tenantIds): void
    {
        $this->db->prepare('DELETE FROM notice_tenant WHERE notice_id = ?')->execute([$noticeId]);
        if (!empty($tenantIds)) {
            $stmt = $this->db->prepare('INSERT INTO notice_tenant (notice_id, tenant_id) VALUES (?, ?)');
            foreach ($tenantIds as $tid) {
                $tid = (int)$tid;
                if ($tid > 0) {
                    $stmt->execute([$noticeId, $tid]);
                }
            }
        }
    }

    /**
     * 특정 가맹점이 볼 수 있는 공지 목록 (전체 공지 + 해당 가맹점 지정 공지)
     */
    public function getForTenant($tenantId, $page = 1, $perPage = 20)
    {
        $whereCondition = "n.is_published = 1 AND (n.target_type = 'ALL'
            OR (n.target_type = 'SPECIFIC' AND EXISTS (
                SELECT 1 FROM notice_tenant nt WHERE nt.notice_id = n.id AND nt.tenant_id = ?
            )))";

        $stmt = $this->db->prepare("SELECT COUNT(*) FROM notice n WHERE {$whereCondition}");
        $stmt->execute([$tenantId]);
        $total = (int)$stmt->fetchColumn();

        $offset = ($page - 1) * $perPage;
        $stmt = $this->db->prepare(
            "SELECT n.* FROM notice n WHERE {$whereCondition}
             ORDER BY n.is_pinned DESC, n.published_at DESC LIMIT ? OFFSET ?"
        );
        $stmt->execute([$tenantId, (int)$perPage, (int)$offset]);

        return ['rows' => $stmt->fetchAll(), 'total' => $total];
    }
}
