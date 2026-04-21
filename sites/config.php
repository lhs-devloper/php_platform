<?php

/**
 * 가맹점 사이트 설정
 * .env 파일에서 환경변수를 로드한 뒤,
 * 서브도메인 → central_admin 조회 → 테넌트 DB 연결
 */

// ─── .env 로드 ───
(function () {
    $envFile = __DIR__ . '/.env';
    if (!file_exists($envFile)) return;

    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        // 주석 건너뛰기
        if ($line === '' || $line[0] === '#') continue;
        // KEY=VALUE 파싱
        if (strpos($line, '=') === false) continue;
        list($key, $value) = explode('=', $line, 2);
        $key   = trim($key);
        $value = trim($value);
        // 따옴표 제거
        if (strlen($value) >= 2 &&
            (($value[0] === '"' && $value[strlen($value)-1] === '"') ||
             ($value[0] === "'" && $value[strlen($value)-1] === "'"))) {
            $value = substr($value, 1, -1);
        }
        // $_ENV 및 putenv 설정
        $_ENV[$key] = $value;
        putenv("{$key}={$value}");
    }
})();

/**
 * 환경변수 헬퍼: .env → $_ENV → 기본값 순서로 조회
 */
function env($key, $default = '')
{
    if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
        return $_ENV[$key];
    }
    $val = getenv($key);
    if ($val !== false && $val !== '') {
        return $val;
    }
    return $default;
}

// ─── 중앙 DB 접속정보 ───
define('CENTRAL_DB_HOST', env('CENTRAL_DB_HOST', 'localhost'));
define('CENTRAL_DB_PORT', (int)env('CENTRAL_DB_PORT', 3306));
define('CENTRAL_DB_NAME', env('CENTRAL_DB_NAME', 'central_admin'));
define('CENTRAL_DB_USER', env('CENTRAL_DB_USER', 'root'));
define('CENTRAL_DB_PASS', env('CENTRAL_DB_PASS', ''));

// ─── 앱 설정 ───
define('ADMIN_ACCESS_SECRET', env('ADMIN_ACCESS_SECRET', ''));
define('APP_VERSION', env('APP_VERSION', '1.0.0'));
define('ITEMS_PER_PAGE', (int)env('ITEMS_PER_PAGE', 20));
define('HASH_ALGO', env('HASH_ALGO', 'sha256'));
define('BASE_PATH', __DIR__);

// ─── 이미지 설정 ───
define('IMG_POSTURE_BASE_URL', env('IMG_POSTURE_BASE_URL', 'http://poco-main.ai-sw.kr/img/pose'));
define('IMG_FOOT_BASE_URL', env('IMG_FOOT_BASE_URL', 'http://footai.ai-sw.kr/jokmoon/img/'));

// ─── AI 설정 ───
define('AI_ENCRYPTION_KEY', env('AI_ENCRYPTION_KEY', ''));
define('AI_DEFAULT_API_KEY', env('AI_DEFAULT_API_KEY', ''));

// ─── 환경 설정 ───
define('APP_ENV', env('APP_ENV', 'local'));
define('APP_DEBUG', filter_var(env('APP_DEBUG', 'true'), FILTER_VALIDATE_BOOLEAN));
define('DOMAIN_SUFFIX', env('DOMAIN_SUFFIX', '.localhost'));

// ─── 서브도메인 추출 ───
$httpHost = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
$parts = explode('.', $httpHost);
$subdomain = '';

if (count($parts) >= 2 && $parts[count($parts) - 1] === 'localhost') {
    $subdomain = strtolower($parts[0]);
}

if ($subdomain === '' || in_array($subdomain, ['admin', 'www', 'api', 'mail', 'ftp'], true)) {
    http_response_code(400);
    die('잘못된 접근입니다.');
}

// ─── central_admin에서 테넌트 DB 정보 조회 ───
try {
    $centralDsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
        CENTRAL_DB_HOST,
        CENTRAL_DB_PORT,
        CENTRAL_DB_NAME
    );
    $centralPdo = new PDO($centralDsn, CENTRAL_DB_USER, CENTRAL_DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $domain = $subdomain . DOMAIN_SUFFIX;
    $stmt = $centralPdo->prepare(
        "SELECT td.*, t.company_name, t.status AS tenant_status, t.service_type
         FROM tenant_database td
         JOIN tenant t ON t.id = td.tenant_id
         WHERE td.domain = ? AND td.status = 'ACTIVE' LIMIT 1"
    );
    $stmt->execute([$domain]);
    $tenantDb = $stmt->fetch();
    $centralPdo = null; // 중앙 DB 연결 해제

    if (!$tenantDb) {
        http_response_code(404);
        die("<h1>404</h1><p>등록되지 않은 서비스입니다: {$domain}</p>");
    }
    if ($tenantDb['tenant_status'] !== 'ACTIVE') {
        http_response_code(503);
        die('<h1>서비스 일시 중지</h1><p>관리자에게 문의해주세요.</p>');
    }
} catch (PDOException $e) {
    http_response_code(500);
    if (APP_DEBUG) {
        die('시스템 오류: ' . $e->getMessage());
    }
    die('시스템 오류가 발생했습니다.');
}

// ─── 테넌트 DB 상수 정의 ───
define('TENANT_DB_HOST', $tenantDb['db_host']);
define('TENANT_DB_PORT', (int)$tenantDb['db_port']);
define('TENANT_DB_NAME', $tenantDb['db_name']);
define('TENANT_DB_USER', CENTRAL_DB_USER); // 테스트: root, 운영: 전용계정
define('TENANT_DB_PASS', CENTRAL_DB_PASS);
define('APP_NAME', $tenantDb['company_name']);
define('TENANT_SERVICE_TYPE', $tenantDb['service_type'] ?: 'BOTH'); // POSTURE | FOOT | BOTH

// ─── 세션 설정 (테넌트별 분리) ───
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.gc_maxlifetime', 3600);
session_name('TENANT_' . strtoupper($subdomain));
session_start();

// ─── 공통 설정 ───
date_default_timezone_set(env('APP_TIMEZONE', 'Asia/Seoul'));
ini_set('display_errors', APP_DEBUG ? 1 : 0);
error_reporting(APP_DEBUG ? E_ALL : E_ALL & ~E_NOTICE & ~E_DEPRECATED);

// ─── 가맹점 정보 전역 저장 ───
$GLOBALS['subdomain'] = $subdomain;
$GLOBALS['tenant_db_info'] = $tenantDb;
