<?php
/**
 * AES-256-CBC 대칭키 암호화 헬퍼
 * - tenant.app_pw 등 원문 복원이 필요한 값 저장용
 * - 키는 ENCRYPTION_KEY 상수에서 sha256으로 유도
 * - 저장 포맷: base64(iv . ciphertext)
 */
class Crypto
{
    private static function key(): string
    {
        if (!defined('ENCRYPTION_KEY') || ENCRYPTION_KEY === '') {
            throw new RuntimeException('ENCRYPTION_KEY가 설정되지 않았습니다.');
        }
        return hash('sha256', ENCRYPTION_KEY, true);
    }

    public static function encrypt(string $plaintext): string
    {
        if ($plaintext === '') return '';
        $iv = random_bytes(16);
        $ct = openssl_encrypt($plaintext, 'AES-256-CBC', self::key(), OPENSSL_RAW_DATA, $iv);
        if ($ct === false) {
            throw new RuntimeException('암호화 실패');
        }
        return base64_encode($iv . $ct);
    }

    public static function decrypt(string $payload): ?string
    {
        if ($payload === '') return '';
        $raw = base64_decode($payload, true);
        if ($raw === false || strlen($raw) <= 16) return null;
        $iv = substr($raw, 0, 16);
        $ct = substr($raw, 16);
        $pt = openssl_decrypt($ct, 'AES-256-CBC', self::key(), OPENSSL_RAW_DATA, $iv);
        return $pt === false ? null : $pt;
    }
}
