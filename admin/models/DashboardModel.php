<?php
class DashboardModel extends Model
{
    public function getTenantStats()
    {
        $rows = (new QueryBuilder('tenant'))
            ->selectRaw('status, COUNT(*) AS cnt')
            ->groupBy('status')
            ->get();

        $result = ['total' => 0, 'PENDING' => 0, 'ACTIVE' => 0, 'SUSPENDED' => 0, 'TERMINATED' => 0];
        foreach ($rows as $row) {
            $result[$row['status']] = (int)$row['cnt'];
            $result['total'] += (int)$row['cnt'];
        }
        return $result;
    }

    public function getPartnerStats()
    {
        $rows = (new QueryBuilder('partner'))
            ->selectRaw('status, COUNT(*) AS cnt')
            ->groupBy('status')
            ->get();

        $result = ['total' => 0, 'PENDING' => 0, 'ACTIVE' => 0, 'SUSPENDED' => 0, 'TERMINATED' => 0];
        foreach ($rows as $row) {
            $result[$row['status']] = (int)$row['cnt'];
            $result['total'] += (int)$row['cnt'];
        }
        return $result;
    }

    public function getPendingAccessRequestCount()
    {
        return (new QueryBuilder('partner_access_request'))
            ->where('status', 'PENDING')
            ->count();
    }

    public function getRecentTenants($limit = 5)
    {
        return (new QueryBuilder('tenant'))
            ->orderBy('created_at DESC')
            ->limit((int)$limit)
            ->get();
    }

    /**
     * 모든 활성 가맹점 DB에서 분석 사용량 통계 수집
     * (동적 외부 DB 연결 — raw SQL 유지)
     */
    public function getAnalysisStats(): array
    {
        $tenantDbs = (new QueryBuilder('tenant_database', 'td'))
            ->select('td.tenant_id, t.company_name, td.db_name, td.db_host, td.db_port, td.db_user, td.db_password_enc')
            ->join('tenant t', 't.id = td.tenant_id')
            ->whereColumn("td.status", 'ACTIVE')
            ->orderBy('t.company_name ASC')
            ->get();

        $perTenant = [];
        $totals = ['members' => 0, 'posture' => 0, 'foot' => 0];
        $monthly = [];

        foreach ($tenantDbs as $tdb) {
            try {
                $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
                    $tdb['db_host'], $tdb['db_port'], $tdb['db_name']);
                $pdo = new PDO($dsn, $tdb['db_user'], $tdb['db_password_enc'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_TIMEOUT => 5,
                ]);

                $memberCount  = (int)QueryBuilder::rawValue("SELECT COUNT(*) FROM member", [], $pdo);
                $postureCount = (int)QueryBuilder::rawValue("SELECT COUNT(*) FROM posture_session", [], $pdo);
                $footCount    = (int)QueryBuilder::rawValue("SELECT COUNT(*) FROM foot_session", [], $pdo);

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
                $rows = QueryBuilder::raw(
                    "SELECT DATE_FORMAT(captured_at, '%Y-%m') AS ym, COUNT(*) AS cnt
                     FROM posture_session
                     WHERE captured_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                     GROUP BY ym",
                    [], $pdo
                );
                foreach ($rows as $r) {
                    $monthly[$r['ym']]['posture'] = ($monthly[$r['ym']]['posture'] ?? 0) + (int)$r['cnt'];
                }

                $rows = QueryBuilder::raw(
                    "SELECT DATE_FORMAT(captured_at, '%Y-%m') AS ym, COUNT(*) AS cnt
                     FROM foot_session
                     WHERE captured_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                     GROUP BY ym",
                    [], $pdo
                );
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

        ksort($monthly);

        return [
            'totals'    => $totals,
            'perTenant' => $perTenant,
            'monthly'   => $monthly,
        ];
    }
}
