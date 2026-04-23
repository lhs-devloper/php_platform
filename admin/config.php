<?php

/**
 * CentralAdmin - 설정 파일
 * .env 파일에서 환경변수를 로드하여 상수로 정의
 */

// .env 로더
// PHP 8.0 미만 버전을 위한 str_starts_with 함수 정의
if (!function_exists('str_starts_with')) {
    function str_starts_with($haystack, $needle)
    {
        return (string)$needle !== '' && strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}

// (참고) 아마 str_ends_with나 str_contains도 에러가 날 확률이 높으니 같이 넣어두는 게 좋습니다.
if (!function_exists('str_ends_with')) {
    function str_ends_with($haystack, $needle)
    {
        return $needle !== '' && substr($haystack, -strlen($needle)) === (string)$needle;
    }
}

if (!function_exists('str_contains')) {
    function str_contains($haystack, $needle)
    {
        return $needle !== '' && mb_strpos($haystack, $needle) !== false;
    }
}

require_once __DIR__ . '/core/Env.php';
Env::load(__DIR__ . '/.env');

// DB 접속 정보
define('DB_HOST',    Env::get('DB_HOST', 'localhost'));
define('DB_PORT',    Env::getInt('DB_PORT', 3306));
define('DB_NAME',    Env::get('DB_NAME', 'central_admin'));
define('DB_USER',    Env::get('DB_USER', 'root'));
define('DB_PASS',    Env::get('DB_PASS', ''));
define('DB_CHARSET', Env::get('DB_CHARSET', 'utf8mb4'));

// 앱 설정
define('APP_NAME',      Env::get('APP_NAME', 'CentralAdmin'));
define('APP_VERSION',   Env::get('APP_VERSION', '1.0.0'));
define('APP_ENV',       Env::get('APP_ENV', 'production'));
define('ITEMS_PER_PAGE', Env::getInt('ITEMS_PER_PAGE', 20));
define('HASH_ALGO',     Env::get('HASH_ALGO', 'sha256'));

// 관리자 사이트 접속 토큰 (가맹점 자동 로그인용)
define('ADMIN_ACCESS_SECRET', Env::get('ADMIN_ACCESS_SECRET', 'change_this_secret'));

// 대칭키 암호화 (tenant.app_pw 등)
define('ENCRYPTION_KEY', Env::get('ENCRYPTION_KEY', 'change_this_encryption_key'));

// 프로비저닝 설정
define('PROVISION_DB_HOST',    Env::get('PROVISION_DB_HOST', DB_HOST));
define('PROVISION_DB_PORT',    Env::getInt('PROVISION_DB_PORT', DB_PORT));
define('PROVISION_DB_USER',    Env::get('PROVISION_DB_USER', DB_USER));
define('PROVISION_DB_PASS',    Env::get('PROVISION_DB_PASS', DB_PASS));
define('PROVISION_DOMAIN_SUFFIX', Env::get('PROVISION_DOMAIN_SUFFIX', '.localhost'));
define('TOTALAPP_SQL_PATH',    realpath(__DIR__ . '/' . Env::get('TOTALAPP_SQL_PATH', '../TotalApp.sql')));
define('TENANT_SITE_PATH',     realpath(__DIR__ . '/' . Env::get('TENANT_SITE_PATH', '../sites')));
define('MYSQL_BIN_PATH',       Env::get('MYSQL_BIN_PATH', '/usr/bin/mysql'));

// 경로
define('BASE_PATH', __DIR__);
define('BASE_URL', rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'));

// 세션 설정
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.gc_maxlifetime', 3600);

session_start();

// 타임존
date_default_timezone_set('Asia/Seoul');

// 파일 업로드 설정 (마이그레이션 SQL 파일 최대 200MB)
ini_set('upload_max_filesize', '200M');
ini_set('post_max_size', '210M');
ini_set('max_execution_time', 600);
ini_set('memory_limit', '512M');

// 에러 표시 (환경에 따라 분기)
if (APP_ENV === 'development') {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
}
