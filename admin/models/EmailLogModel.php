<?php
/**
 * 이메일 발송 로그 모델
 */
class EmailLogModel extends Model
{
    protected $table = 'email_log';

    /**
     * 검색 (키워드, 상태, 발송자별 필터)
     */
    public function search(string $keyword = '', string $status = '', int $page = 1, int $perPage = 20): array
    {
        $where = [];
        $params = [];

        if ($keyword !== '') {
            $where[] = "(el.subject LIKE ? OR el.to_email LIKE ?)";
            $params[] = '%' . $keyword . '%';
            $params[] = '%' . $keyword . '%';
        }

        if ($status !== '') {
            $where[] = "el.status = ?";
            $params[] = $status;
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        // 카운트
        $countSql = "SELECT COUNT(*) FROM `email_log` el {$whereSql}";
        $stmt = $this->db->prepare($countSql);
        $stmt->execute($params);
        $total = (int)$stmt->fetchColumn();

        // 목록
        $offset = ($page - 1) * $perPage;
        $sql = "SELECT el.*, ca.name AS admin_name
                FROM `email_log` el
                LEFT JOIN `central_admin` ca ON el.admin_id = ca.id
                {$whereSql}
                ORDER BY el.id DESC
                LIMIT ? OFFSET ?";
        $params[] = (int)$perPage;
        $params[] = (int)$offset;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return ['rows' => $stmt->fetchAll(), 'total' => $total];
    }

    /**
     * 상세 조회 (관리자명 포함)
     */
    public function findByIdWithRelations(int $id): ?array
    {
        $sql = "SELECT el.*, ca.name AS admin_name
                FROM `email_log` el
                LEFT JOIN `central_admin` ca ON el.admin_id = ca.id
                WHERE el.id = ?
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row !== false ? $row : null;
    }

    /**
     * 통계 (대시보드용)
     */
    public function getStats(): array
    {
        $sql = "SELECT
                    COUNT(*) AS total,
                    SUM(status = 'SENT') AS sent,
                    SUM(status = 'FAILED') AS failed,
                    SUM(status = 'PENDING') AS pending
                FROM `email_log`";
        $stmt = $this->db->query($sql);
        return $stmt->fetch();
    }
}
