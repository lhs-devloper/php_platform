<?php
/**
 * 스키마 업데이트 서비스
 *
 * 운영 중인 테넌트 DB에 마이그레이션 SQL을 일괄/개별 적용한다.
 * migrations/ 폴더의 버전별 SQL 파일을 순차 실행하고,
 * 각 테넌트 DB의 schema_migrations 테이블로 적용 여부를 추적한다.
 *
 * 흐름:
 *   1. migrations/ 폴더에서 사용 가능한 마이그레이션 목록 스캔
 *   2. 대상 테넌트 DB 접속 → schema_migrations 조회 → 미적용 버전 탐지
 *   3. 미적용 SQL 파일을 순차 실행
 *   4. provision_log에 결과 기록
 */
class SchemaUpdateService
{
    private $centralDb;
    private $migrationsPath;

    public function __construct()
    {
        $this->centralDb = Database::getInstance();
        $this->migrationsPath = realpath(__DIR__ . '/../../migrations');
    }

    // ─── 마이그레이션 목록 스캔 ───

    /**
     * migrations/ 폴더에서 사용 가능한 마이그레이션 목록 반환
     * @return array [['version' => '0001', 'name' => 'AI 상담 어시스턴트', 'file' => 'full_path'], ...]
     */
    public function getAvailableMigrations()
    {
        $migrations = [];
        if (!$this->migrationsPath || !is_dir($this->migrationsPath)) {
            return $migrations;
        }

        $files = glob($this->migrationsPath . '/*.sql');
        sort($files);

        foreach ($files as $file) {
            $filename = basename($file, '.sql');
            // 파일명 형식: 0001_ai_consultation_assistant
            if (preg_match('/^(\d{4})_(.+)$/', $filename, $m)) {
                $migrations[] = [
                    'version' => $m[1],
                    'name'    => str_replace('_', ' ', $m[2]),
                    'file'    => $file,
                    'filename'=> basename($file),
                ];
            }
        }

        return $migrations;
    }

    // ─── 활성 테넌트 목록 ───

    /**
     * 활성 테넌트 DB 목록 조회
     * @return array
     */
    public function getActiveTenants()
    {
        $stmt = $this->centralDb->prepare(
            "SELECT td.*, t.company_name, t.status AS tenant_status
             FROM tenant_database td
             JOIN tenant t ON t.id = td.tenant_id
             WHERE td.status = 'ACTIVE' AND t.status = 'ACTIVE'
             ORDER BY t.company_name"
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // ─── 테넌트별 적용 상태 조회 ───

    /**
     * 특정 테넌트 DB에 적용된 마이그레이션 버전 목록 조회
     * @param array $tenantDb tenant_database 레코드
     * @return array 적용된 버전 배열 ['0001', ...]
     */
    public function getAppliedVersions(array $tenantDb)
    {
        try {
            $pdo = $this->connectTenant($tenantDb);

            // schema_migrations 테이블 존재 여부 확인
            $stmt = $pdo->query("SHOW TABLES LIKE 'schema_migrations'");
            if (!$stmt->fetch()) {
                return []; // 테이블 없음 = 마이그레이션 미적용
            }

            $stmt = $pdo->query("SELECT version FROM schema_migrations ORDER BY version");
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            return []; // 접속 실패 시 빈 배열
        }
    }

    /**
     * 전체 테넌트의 마이그레이션 적용 현황 조회
     * @return array [tenant_id => ['tenant' => ..., 'applied' => [...], 'pending' => [...]]]
     */
    public function getStatusAll()
    {
        $tenants = $this->getActiveTenants();
        $available = $this->getAvailableMigrations();
        $allVersions = array_column($available, 'version');
        $status = [];

        foreach ($tenants as $t) {
            $applied = $this->getAppliedVersions($t);
            $pending = array_values(array_diff($allVersions, $applied));

            $status[] = [
                'tenant_id'    => $t['tenant_id'],
                'company_name' => $t['company_name'],
                'db_name'      => $t['db_name'],
                'domain'       => $t['domain'],
                'applied'      => $applied,
                'pending'      => $pending,
                'up_to_date'   => empty($pending),
            ];
        }

        return $status;
    }

    // ─── 마이그레이션 실행 ───

    /**
     * 단일 테넌트에 미적용 마이그레이션 실행
     * @param int   $tenantId  테넌트 PK
     * @param int   $adminId   실행 관리자 PK
     * @param array $versions  적용할 버전 목록 (빈 배열이면 전체 미적용분)
     * @return array ['success' => bool, 'results' => [...], 'message' => string]
     */
    public function applyToTenant($tenantId, $adminId, array $versions = [])
    {
        // 테넌트 DB 정보 조회
        $stmt = $this->centralDb->prepare(
            "SELECT td.*, t.company_name
             FROM tenant_database td
             JOIN tenant t ON t.id = td.tenant_id
             WHERE td.tenant_id = ? AND td.status = 'ACTIVE' LIMIT 1"
        );
        $stmt->execute([$tenantId]);
        $tenantDb = $stmt->fetch();

        if (!$tenantDb) {
            return ['success' => false, 'message' => '활성 테넌트 DB를 찾을 수 없습니다.', 'results' => []];
        }

        // 사용 가능한 마이그레이션
        $available = $this->getAvailableMigrations();
        $availableMap = [];
        foreach ($available as $m) {
            $availableMap[$m['version']] = $m;
        }

        // 이미 적용된 버전
        $applied = $this->getAppliedVersions($tenantDb);

        // 적용 대상 결정
        if (empty($versions)) {
            $versions = array_values(array_diff(array_keys($availableMap), $applied));
        } else {
            // 요청된 버전 중 이미 적용된 것 제외
            $versions = array_values(array_diff($versions, $applied));
        }

        if (empty($versions)) {
            return ['success' => true, 'message' => '적용할 마이그레이션이 없습니다. (최신 상태)', 'results' => []];
        }

        sort($versions); // 버전순 정렬

        // 프로비저닝 로그 시작
        $logId = $this->createLog($tenantId, $adminId, implode(', ', $versions));

        $results = [];
        $allSuccess = true;

        try {
            $pdo = $this->connectTenant($tenantDb);

            foreach ($versions as $ver) {
                if (!isset($availableMap[$ver])) {
                    $results[] = ['version' => $ver, 'success' => false, 'error' => '마이그레이션 파일을 찾을 수 없습니다.'];
                    $allSuccess = false;
                    continue;
                }

                $migration = $availableMap[$ver];
                $sql = file_get_contents($migration['file']);
                if ($sql === false) {
                    $results[] = ['version' => $ver, 'success' => false, 'error' => '파일 읽기 실패: ' . $migration['filename']];
                    $allSuccess = false;
                    break; // 순차 실행이므로 중단
                }

                try {
                    $pdo->exec($sql);
                    $results[] = ['version' => $ver, 'name' => $migration['name'], 'success' => true];
                } catch (PDOException $e) {
                    $results[] = ['version' => $ver, 'name' => $migration['name'], 'success' => false, 'error' => $e->getMessage()];
                    $allSuccess = false;
                    break; // 실패 시 후속 마이그레이션 중단
                }
            }

            // tenant_database의 last_migration_at 갱신
            if ($allSuccess) {
                $stmt = $this->centralDb->prepare(
                    "UPDATE tenant_database SET last_migration_at = NOW() WHERE tenant_id = ?"
                );
                $stmt->execute([$tenantId]);
            }

            // 로그 업데이트
            $detail = json_encode($results, JSON_UNESCAPED_UNICODE);
            $this->updateLog($logId, $allSuccess ? 'COMPLETED' : 'FAILED', $detail, $allSuccess ? null : '일부 마이그레이션 실패');

            $applied_count = count(array_filter($results, function ($r) { return $r['success']; }));
            $msg = sprintf(
                '%s: %d개 마이그레이션 중 %d개 적용 완료',
                $tenantDb['company_name'],
                count($versions),
                $applied_count
            );

            return ['success' => $allSuccess, 'message' => $msg, 'results' => $results];

        } catch (PDOException $e) {
            $this->updateLog($logId, 'FAILED', null, $e->getMessage());
            return ['success' => false, 'message' => 'DB 접속 실패: ' . $e->getMessage(), 'results' => []];
        }
    }

    /**
     * 전체 활성 테넌트에 미적용 마이그레이션 일괄 실행
     * @param int $adminId 실행 관리자 PK
     * @return array ['total' => int, 'success' => int, 'failed' => int, 'skipped' => int, 'details' => [...]]
     */
    public function applyToAll($adminId)
    {
        $tenants = $this->getActiveTenants();
        $summary = ['total' => count($tenants), 'success' => 0, 'failed' => 0, 'skipped' => 0, 'details' => []];

        foreach ($tenants as $t) {
            $result = $this->applyToTenant($t['tenant_id'], $adminId);

            if (empty($result['results'])) {
                $summary['skipped']++;
            } elseif ($result['success']) {
                $summary['success']++;
            } else {
                $summary['failed']++;
            }

            $summary['details'][] = [
                'tenant_id'    => $t['tenant_id'],
                'company_name' => $t['company_name'],
                'db_name'      => $t['db_name'],
                'result'       => $result,
            ];
        }

        return $summary;
    }

    // ─── 내부 헬퍼 ───

    /**
     * 테넌트 DB 접속
     */
    private function connectTenant(array $tenantDb)
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $tenantDb['db_host'],
            (int)$tenantDb['db_port'],
            $tenantDb['db_name']
        );
        return new PDO($dsn, PROVISION_DB_USER, PROVISION_DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }

    /**
     * provision_log 생성 (MIGRATE 액션)
     */
    private function createLog($tenantId, $adminId, $toVersion)
    {
        $stmt = $this->centralDb->prepare(
            "INSERT INTO provision_log (tenant_id, admin_id, action, status, to_version, started_at, created_at)
             VALUES (?, ?, 'MIGRATE', 'IN_PROGRESS', ?, NOW(), NOW())"
        );
        $stmt->execute([$tenantId, $adminId, $toVersion]);
        return (int)$this->centralDb->lastInsertId();
    }

    /**
     * provision_log 업데이트
     */
    private function updateLog($logId, $status, $detail = null, $errorMessage = null)
    {
        $stmt = $this->centralDb->prepare(
            "UPDATE provision_log SET status = ?, detail = ?, error_message = ?, completed_at = NOW() WHERE id = ?"
        );
        $stmt->execute([$status, $detail, $errorMessage, $logId]);
    }
}
