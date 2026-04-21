<?php
/**
 * 가맹점 DB 프로비저닝 서비스
 *
 * 가맹점 등록 시 자동으로:
 * 1. 슬러그 기반 전용 DB 생성 (ex: smartidea)
 * 2. TotalApp.sql 스키마 + 초기데이터 실행
 * 3. franchise 테이블에 가맹점 정보 반영
 * 4. tenant_database 레코드 생성 (smartidea.localhost 도메인 매핑)
 * 5. provision_log 이력 기록
 */
class ProvisionService
{
    private $centralDb;

    public function __construct()
    {
        $this->centralDb = Database::getInstance();
    }

    /**
     * 가맹점 DB 프로비저닝 실행
     *
     * @param int    $tenantId  가맹점 PK
     * @param array  $tenant    가맹점 정보 배열
     * @param string $slug      서브도메인/DB명으로 사용할 슬러그 (ex: smartidea)
     * @param int    $adminId   실행한 관리자 PK
     * @return array
     */
    public function provision($tenantId, array $tenant, $slug, $adminId)
    {
        $dbName = $slug;
        $domain = $slug . PROVISION_DOMAIN_SUFFIX;
        $dbUser = $slug;
        $dbPass = $this->generatePassword();

        // 슬러그 중복 체크
        $stmt = $this->centralDb->prepare('SELECT id FROM tenant_database WHERE db_name = ? OR domain = ?');
        $stmt->execute([$dbName, $domain]);
        if ($stmt->fetch()) {
            return [
                'success' => false,
                'message' => "'{$slug}' 는 이미 사용 중인 슬러그입니다.",
                'db_name' => $dbName,
                'domain'  => $domain,
            ];
        }

        // 프로비저닝 로그 시작
        $logId = $this->createProvisionLog($tenantId, $adminId, 'CREATE', $dbName);

        try {
            // 1. 프로비저닝 서버 연결
            $provisionDsn = sprintf(
                'mysql:host=%s;port=%d;charset=%s',
                PROVISION_DB_HOST, PROVISION_DB_PORT, DB_CHARSET
            );
            $provisionPdo = new PDO($provisionDsn, PROVISION_DB_USER, PROVISION_DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);

            // 2. DB 생성
            $provisionPdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

            // 3. 전용 사용자 생성 + 권한 부여
            try {
                $provisionPdo->exec("CREATE USER IF NOT EXISTS '{$dbUser}'@'localhost' IDENTIFIED BY '{$dbPass}'");
                $provisionPdo->exec("GRANT ALL PRIVILEGES ON `{$dbName}`.* TO '{$dbUser}'@'localhost'");
                $provisionPdo->exec("FLUSH PRIVILEGES");
            } catch (PDOException $e) {
                // 테스트 환경에서는 root로 직접 접속하므로 실패해도 진행
            }

            // 4. 가맹점 DB에 연결
            $tenantDsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                PROVISION_DB_HOST, PROVISION_DB_PORT, $dbName, DB_CHARSET
            );
            $tenantPdo = new PDO($tenantDsn, PROVISION_DB_USER, PROVISION_DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);

            // 5. TotalApp.sql 실행
            $sqlPath = TOTALAPP_SQL_PATH;
            if (!$sqlPath || !file_exists($sqlPath)) {
                throw new Exception("TotalApp.sql 파일을 찾을 수 없습니다: {$sqlPath}");
            }
            $sql = file_get_contents($sqlPath);
            $tenantPdo->exec($sql);

            // 6. franchise 테이블에 가맹점 실제 정보 반영
            $stmt = $tenantPdo->prepare(
                "UPDATE franchise SET name = ?, ceo_name = ?, phone = ?, address = ?, email = ? WHERE id = 1"
            );
            $stmt->execute([
                $tenant['company_name'],
                $tenant['ceo_name'] ?: '',
                $tenant['phone'] ?: '',
                $tenant['address'] ?: '',
                $tenant['email'] ?: '',
            ]);

            // 7. tenant_database 레코드 생성
            $this->createTenantDatabase($tenantId, $dbName, $dbUser, $dbPass, $domain);

            // 8. 프로비저닝 로그 완료
            $this->updateProvisionLog($logId, 'COMPLETED');

            return [
                'success' => true,
                'message' => "DB '{$dbName}' 생성 완료 → {$domain}",
                'db_name' => $dbName,
                'domain'  => $domain,
            ];

        } catch (Exception $e) {
            $this->updateProvisionLog($logId, 'FAILED', $e->getMessage());

            return [
                'success' => false,
                'message' => '프로비저닝 실패: ' . $e->getMessage(),
                'db_name' => $dbName,
                'domain'  => $domain,
            ];
        }
    }

    /**
     * 가맹점 DB 완전 삭제 (DROP DATABASE + 사용자 삭제 + 레코드 삭제)
     */
    public function destroy($tenantId, array $database, $adminId)
    {
        $dbName = $database['db_name'];
        $dbUser = $database['db_user'];

        $logId = $this->createProvisionLog($tenantId, $adminId, 'DESTROY', $dbName);

        try {
            $provisionDsn = sprintf(
                'mysql:host=%s;port=%d;charset=%s',
                PROVISION_DB_HOST, PROVISION_DB_PORT, DB_CHARSET
            );
            $provisionPdo = new PDO($provisionDsn, PROVISION_DB_USER, PROVISION_DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);

            // 1. DB 삭제
            $provisionPdo->exec("DROP DATABASE IF EXISTS `{$dbName}`");

            // 2. 전용 사용자 삭제
            if ($dbUser) {
                try {
                    $provisionPdo->exec("DROP USER IF EXISTS '{$dbUser}'@'localhost'");
                    $provisionPdo->exec("FLUSH PRIVILEGES");
                } catch (PDOException $e) {
                    // 사용자 삭제 실패해도 진행
                }
            }

            // 3. tenant_database 레코드 삭제
            $stmt = $this->centralDb->prepare('DELETE FROM tenant_database WHERE tenant_id = ?');
            $stmt->execute([$tenantId]);

            // 4. provision_log 완료
            $this->updateProvisionLog($logId, 'COMPLETED');

            return ['success' => true, 'message' => "DB '{$dbName}' 완전 삭제 완료"];

        } catch (Exception $e) {
            $this->updateProvisionLog($logId, 'FAILED', $e->getMessage());
            return ['success' => false, 'message' => 'DB 삭제 실패: ' . $e->getMessage()];
        }
    }

    /**
     * 슬러그 유효성 검사 (영문 소문자, 숫자, 하이픈만 허용)
     */
    public static function validateSlug($slug)
    {
        if (empty($slug)) {
            return '슬러그를 입력해주세요.';
        }
        if (!preg_match('/^[a-z0-9]([a-z0-9\-]*[a-z0-9])?$/', $slug)) {
            return '슬러그는 영문 소문자, 숫자, 하이픈(-)만 사용 가능합니다. (예: smartidea, my-shop)';
        }
        if (strlen($slug) < 3 || strlen($slug) > 50) {
            return '슬러그는 3~50자 이내로 입력해주세요.';
        }
        // 예약어 차단
        $reserved = ['admin', 'www', 'api', 'mail', 'ftp', 'test', 'dev', 'staging', 'central'];
        if (in_array($slug, $reserved, true)) {
            return "'{$slug}'는 예약어로 사용할 수 없습니다.";
        }
        return null; // 유효
    }

    private function generatePassword($length = 16)
    {
        return bin2hex(random_bytes($length / 2));
    }

    private function createTenantDatabase($tenantId, $dbName, $dbUser, $dbPass, $domain)
    {
        $stmt = $this->centralDb->prepare('SELECT id FROM tenant_database WHERE tenant_id = ?');
        $stmt->execute([$tenantId]);
        $existing = $stmt->fetch();

        if ($existing) {
            $stmt = $this->centralDb->prepare(
                "UPDATE tenant_database SET
                    db_host = ?, db_port = ?, db_name = ?, db_user = ?, db_password_enc = ?,
                    domain = ?, db_version = ?, status = 'ACTIVE', provisioned_at = NOW()
                 WHERE tenant_id = ?"
            );
            $stmt->execute([
                PROVISION_DB_HOST, PROVISION_DB_PORT, $dbName, $dbUser, base64_encode($dbPass),
                $domain, APP_VERSION, $tenantId
            ]);
        } else {
            $stmt = $this->centralDb->prepare(
                "INSERT INTO tenant_database
                    (tenant_id, db_host, db_port, db_name, db_user, db_password_enc, domain, db_version, status, provisioned_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'ACTIVE', NOW())"
            );
            $stmt->execute([
                $tenantId, PROVISION_DB_HOST, PROVISION_DB_PORT, $dbName, $dbUser,
                base64_encode($dbPass), $domain, APP_VERSION
            ]);
        }
    }

    private function createProvisionLog($tenantId, $adminId, $action, $detail)
    {
        $stmt = $this->centralDb->prepare(
            "INSERT INTO provision_log (tenant_id, admin_id, action, status, to_version, detail, started_at, created_at)
             VALUES (?, ?, ?, 'IN_PROGRESS', ?, ?, NOW(), NOW())"
        );
        $stmt->execute([$tenantId, $adminId, $action, APP_VERSION, "DB: {$detail}"]);
        return (int)$this->centralDb->lastInsertId();
    }

    private function updateProvisionLog($logId, $status, $errorMessage = null)
    {
        $stmt = $this->centralDb->prepare(
            "UPDATE provision_log SET status = ?, error_message = ?, completed_at = NOW() WHERE id = ?"
        );
        $stmt->execute([$status, $errorMessage, $logId]);
    }
}
