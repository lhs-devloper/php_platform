<?php
/**
 * 공지사항 모델 (가맹점 사이트)
 * CentralAdmin DB에서 읽기 전용 조회
 */
class NoticeModel
{
    private $centralDb;
    private $tenantId;

    public function __construct()
    {
        // CentralAdmin DB에 별도 연결
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            CENTRAL_DB_HOST, CENTRAL_DB_PORT, CENTRAL_DB_NAME);
        $this->centralDb = new PDO($dsn, CENTRAL_DB_USER, CENTRAL_DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);

        // 현재 가맹점의 tenant_id
        $this->tenantId = isset($GLOBALS['tenant_db_info']['tenant_id'])
            ? (int)$GLOBALS['tenant_db_info']['tenant_id'] : 0;
    }

    /**
     * 이 가맹점이 볼 수 있는 공지 목록 (전체공지 + 이 가맹점 전용 공지)
     */
    public function getList($page = 1, $perPage = 20)
    {
        $stmt = $this->centralDb->prepare(
            "SELECT COUNT(*) FROM notice
             WHERE is_published = 1
             AND (target_type = 'ALL' OR (target_type = 'SPECIFIC' AND tenant_id = ?))"
        );
        $stmt->execute([$this->tenantId]);
        $total = (int)$stmt->fetchColumn();

        $offset = ($page - 1) * $perPage;
        $stmt = $this->centralDb->prepare(
            "SELECT id, title, content, target_type, is_pinned, published_at, created_at
             FROM notice
             WHERE is_published = 1
             AND (target_type = 'ALL' OR (target_type = 'SPECIFIC' AND tenant_id = ?))
             ORDER BY is_pinned DESC, published_at DESC
             LIMIT ? OFFSET ?"
        );
        $stmt->execute([$this->tenantId, (int)$perPage, (int)$offset]);

        return ['rows' => $stmt->fetchAll(), 'total' => $total];
    }

    /**
     * 공지 단건 조회 (이 가맹점이 볼 수 있는 것만)
     */
    public function findById($id)
    {
        $stmt = $this->centralDb->prepare(
            "SELECT * FROM notice
             WHERE id = ? AND is_published = 1
             AND (target_type = 'ALL' OR (target_type = 'SPECIFIC' AND tenant_id = ?))
             LIMIT 1"
        );
        $stmt->execute([$id, $this->tenantId]);
        $row = $stmt->fetch();
        return $row !== false ? $row : null;
    }

    /**
     * 읽지 않은(최근) 공지 수 (사이드바 뱃지용)
     */
    public function getRecentCount($days = 7)
    {
        $stmt = $this->centralDb->prepare(
            "SELECT COUNT(*) FROM notice
             WHERE is_published = 1
             AND published_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
             AND (target_type = 'ALL' OR (target_type = 'SPECIFIC' AND tenant_id = ?))"
        );
        $stmt->execute([$days, $this->tenantId]);
        return (int)$stmt->fetchColumn();
    }
}
