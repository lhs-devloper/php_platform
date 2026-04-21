<?php
/**
 * PDO 싱글톤 - 테넌트 DB 연결
 */
class Database
{
    private static $instance = null;

    public static function getInstance()
    {
        if (self::$instance === null) {
            $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
                TENANT_DB_HOST, TENANT_DB_PORT, TENANT_DB_NAME);
            self::$instance = new PDO($dsn, TENANT_DB_USER, TENANT_DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        }
        return self::$instance;
    }

    private function __construct() {}
    private function __clone() {}
}
