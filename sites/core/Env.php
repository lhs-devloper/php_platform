<?php
/**
 * .env 파일 로더
 * 외부 라이브러리 없이 .env 파일을 파싱하여 환경변수로 로드
 */
class Env
{
    private static $loaded = false;
    private static $vars = [];

    /**
     * .env 파일 로드
     */
    public static function load(string $path): void
    {
        if (self::$loaded) return;

        if (!file_exists($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);

            // 주석/빈줄 무시
            if ($line === '' || $line[0] === '#') {
                continue;
            }

            // KEY=VALUE 파싱
            $pos = strpos($line, '=');
            if ($pos === false) continue;

            $key   = trim(substr($line, 0, $pos));
            $value = trim(substr($line, $pos + 1));

            // 따옴표 제거
            if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
                $value = substr($value, 1, -1);
            }

            self::$vars[$key] = $value;

            // putenv + $_ENV 동기화
            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
        }

        self::$loaded = true;
    }

    /**
     * 환경변수 값 가져오기
     * @param string $key 키
     * @param mixed $default 기본값
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        if (isset(self::$vars[$key])) {
            return self::$vars[$key];
        }

        $val = getenv($key);
        if ($val !== false) {
            return $val;
        }

        return $default;
    }

    /**
     * 정수형 환경변수
     */
    public static function getInt(string $key, int $default = 0): int
    {
        return (int)self::get($key, $default);
    }

    /**
     * 불린형 환경변수
     */
    public static function getBool(string $key, bool $default = false): bool
    {
        $val = self::get($key);
        if ($val === null) return $default;
        return in_array(strtolower($val), ['true', '1', 'yes', 'on'], true);
    }
}
