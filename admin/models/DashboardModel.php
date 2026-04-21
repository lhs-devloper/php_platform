<?php
class DashboardModel extends Model
{
    public function getTenantStats()
    {
        $stmt = $this->db->query('SELECT status, COUNT(*) AS cnt FROM tenant GROUP BY status');
        $result = ['total' => 0, 'PENDING' => 0, 'ACTIVE' => 0, 'SUSPENDED' => 0, 'TERMINATED' => 0];
        foreach ($stmt->fetchAll() as $row) {
            $result[$row['status']] = (int)$row['cnt'];
            $result['total'] += (int)$row['cnt'];
        }
        return $result;
    }

    public function getPartnerStats()
    {
        $stmt = $this->db->query('SELECT status, COUNT(*) AS cnt FROM partner GROUP BY status');
        $result = ['total' => 0, 'PENDING' => 0, 'ACTIVE' => 0, 'SUSPENDED' => 0, 'TERMINATED' => 0];
        foreach ($stmt->fetchAll() as $row) {
            $result[$row['status']] = (int)$row['cnt'];
            $result['total'] += (int)$row['cnt'];
        }
        return $result;
    }

    public function getPendingAccessRequestCount()
    {
        $stmt = $this->db->query("SELECT COUNT(*) FROM partner_access_request WHERE status = 'PENDING'");
        return (int)$stmt->fetchColumn();
    }

    public function getRecentTenants($limit = 5)
    {
        $stmt = $this->db->prepare('SELECT * FROM tenant ORDER BY created_at DESC LIMIT ?');
        $stmt->execute([(int)$limit]);
        return $stmt->fetchAll();
    }

    /**
     * 모든 활성 가맹점 DB에서 분석 사용량 통계 수집
     * 각 가맹점별 회원수, 자세분석 수, 족부분석 수 + 월별 추이
     */
    public function getAnalysisStats(): array
    {
        // 활성 가맹점 DB 목록 조회
        $stmt = $this->db->query(
            "SELECT td.tenant_id, t.company_name, td.db_name, td.db_host, td.db_port, td.db_user, td.db_password_enc
             FROM tenant_database td
             JOIN tenant t ON t.id = td.tenant_id
             WHERE td.status = 'ACTIVE'
             ORDER BY t.company_name ASC"
        );
        $tenantDbs = $stmt->fetchAll();

        $perTenant = [];
        $totals = ['members' => 0, 'posture' => 0, 'foot' => 0];
        $monthly = []; // YYYY-MM => [posture => n, foot => n]

        foreach ($tenantDbs as $tdb) {
            try {
                $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
                    $tdb['db_host'], $tdb['db_port'], $tdb['db_name']);
                $pdo = new PDO($dsn, $tdb['db_user'], $tdb['db_password_enc'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_TIMEOUT => 5,
                ]);

                // 회원 수
                $memberCount = (int)$pdo->query("SELECT COUNT(*) FROM member")->fetchColumn();

                // 자세분석 수
                $postureCount = (int)$pdo->query("SELECT COUNT(*) FROM posture_session")->fetchColumn();

                // 족부분석 수
                $footCount = (int)$pdo->query("SELECT COUNT(*) FROM foot_session")->fetchColumn();

                $perTenant[] = [
                    'tenant_id'    => $tdb['tenant_id'],
                    'company_name' => $tdb['company_name'],
                    'members'      => $memberCount,
                    'posture'      => $postureCount,
                    'foot'         => $footCount,
                ];

                $totals['members'] += $memberCount;
                $totals['posture'] += $postureCount;
                $totals['foot']    += $footCount;

                // 월별 추이 (최근 6개월)
                $rows = $pdo->query(
                    "SELECT DATE_FORMAT(captured_at, '%Y-%m') AS ym, COUNT(*) AS cnt
                     FROM posture_session
                     WHERE captured_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                     GROUP BY ym"
                )->fetchAll();
                foreach ($rows as $r) {
                    $monthly[$r['ym']]['posture'] = ($monthly[$r['ym']]['posture'] ?? 0) + (int)$r['cnt'];
                }

                $rows = $pdo->query(
                    "SELECT DATE_FORMAT(captured_at, '%Y-%m') AS ym, COUNT(*) AS cnt
                     FROM foot_session
                     WHERE captured_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                     GROUP BY ym"
                )->fetchAll();
                foreach ($rows as $r) {
                    $monthly[$r['ym']]['foot'] = ($monthly[$r['ym']]['foot'] ?? 0) + (int)$r['cnt'];
                }

            } catch (Exception $e) {
                $perTenant[] = [
                    'tenant_id'    => $tdb['tenant_id'],
                    'company_name' => $tdb['company_name'],
                    'members'      => '-',
                    'posture'      => '-',
                    'foot'         => '-',
                    'error'        => $e->getMessage(),
                ];
            }
        }

        // 월별 정렬
        ksort($monthly);

        return [
            'totals'    => $totals,
            'perTenant' => $perTenant,
            'monthly'   => $monthly,
        ];
    }
}
