<?php
/**
 * DB 설치 상태 점검 유틸리티
 * MySQL 서버 접속 → DB 존재 → 테이블 존재 → 초기 데이터 존재 순서로 검사
 */
class InstallChecker
{
    /** 필수 테이블 목록 (CentralAdmin.sql 기준) */
    private static $requiredTables = [
        'central_admin',
        'tenant',
        'tenant_contact',
        'tenant_database',
        'service',
        'plan',
        'subscription',
        'payment',
        'provision_log',
        'usage_daily',
        'audit_log',
        'notice',
        'inquiry',
        'partner',
        'partner_admin',
        'partner_tenant',
        'partner_access_request',
        'partner_access_log',
        'system_config',
        'email_log',
    ];

    /**
     * 전체 설치 상태를 점검하여 결과 배열을 반환
     * @return array ['installed' => bool, 'server_ok' => bool, 'db_exists' => bool,
     *               'tables_ok' => bool, 'seed_ok' => bool, 'missing_tables' => [], 'error' => string|null]
     */
    public static function check(): array
    {
        $result = [
            'installed'      => false,
            'server_ok'      => false,
            'db_exists'      => false,
            'tables_ok'      => false,
            'seed_ok'        => false,
            'missing_tables' => [],
            'error'          => null,
        ];

        // 1) MySQL 서버 접속 가능 여부
        try {
            $pdo = new PDO(
                sprintf('mysql:host=%s;port=%d;charset=%s', DB_HOST, DB_PORT, DB_CHARSET),
                DB_USER,
                DB_PASS,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            $result['server_ok'] = true;
        } catch (PDOException $e) {
            $result['error'] = 'MySQL 서버에 연결할 수 없습니다: ' . $e->getMessage();
            return $result;
        }

        // 2) DB 존재 여부
        $stmt = $pdo->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = " . $pdo->quote(DB_NAME));
        if (!$stmt->fetch()) {
            $result['error'] = "데이터베이스 '" . DB_NAME . "'이(가) 존재하지 않습니다.";
            return $result;
        }
        $result['db_exists'] = true;

        // 3) 테이블 존재 여부
        $pdo->exec('USE ' . DB_NAME);
        $stmt = $pdo->query("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = " . $pdo->quote(DB_NAME));
        $existing = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $existing[] = $row['TABLE_NAME'];
        }

        $missing = array_diff(self::$requiredTables, $existing);
        if ($missing) {
            $result['missing_tables'] = array_values($missing);
            $result['error'] = '누락된 테이블이 있습니다: ' . implode(', ', $missing);
            return $result;
        }
        $result['tables_ok'] = true;

        // 4) 초기 데이터 존재 여부 (superadmin 계정)
        $stmt = $pdo->query("SELECT COUNT(*) AS cnt FROM central_admin WHERE login_id = 'superadmin'");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $result['seed_ok'] = ($row['cnt'] > 0);

        $result['installed'] = true;
        return $result;
    }

    /**
     * CentralAdmin.sql을 실행하여 DB를 설치한다
     * @return array ['success' => bool, 'error' => string|null]
     */
    public static function install(string $dbHost, string $dbPort, string $dbUser, string $dbPass, string $dbName): array
    {
        try {
            $pdo = new PDO(
                sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $dbHost, (int)$dbPort),
                $dbUser,
                $dbPass,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );

            // DB 생성 (없으면)
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `{$dbName}`");

            // SQL 파일 읽기
            $sqlPath = realpath(__DIR__ . '/../../CentralAdmin.sql');
            if (!$sqlPath || !file_exists($sqlPath)) {
                return ['success' => false, 'error' => 'CentralAdmin.sql 파일을 찾을 수 없습니다. 경로: ' . __DIR__ . '/../../CentralAdmin.sql'];
            }

            $sql = file_get_contents($sqlPath);
            if ($sql === false) {
                return ['success' => false, 'error' => 'CentralAdmin.sql 파일을 읽을 수 없습니다.'];
            }

            // SQL 실행 (multi-statement)
            $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
            $pdo->exec($sql);

            return ['success' => true, 'error' => null];
        } catch (PDOException $e) {
            return ['success' => false, 'error' => 'DB 설치 중 오류: ' . $e->getMessage()];
        }
    }
}
