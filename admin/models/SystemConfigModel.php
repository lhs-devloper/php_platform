<?php
/**
 * 시스템 설정 모델 (key-value 저장소)
 * SMTP, 기타 런타임 설정을 DB에서 관리
 */
class SystemConfigModel extends Model
{
    protected $table = 'system_config';

    /**
     * 설정값 조회 (단일)
     */
    public function get(string $key, string $default = ''): string
    {
        $stmt = $this->db->prepare("SELECT config_value FROM `{$this->table}` WHERE config_key = ? LIMIT 1");
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        return $row ? $row['config_value'] : $default;
    }

    /**
     * 설정값 저장 (upsert)
     */
    public function set(string $key, string $value, string $group = 'general', string $label = ''): void
    {
        $stmt = $this->db->prepare("SELECT id FROM `{$this->table}` WHERE config_key = ? LIMIT 1");
        $stmt->execute([$key]);
        $row = $stmt->fetch();

        if ($row) {
            $this->db->prepare(
                "UPDATE `{$this->table}` SET config_value = ?, updated_at = NOW() WHERE config_key = ?"
            )->execute([$value, $key]);
        } else {
            $this->db->prepare(
                "INSERT INTO `{$this->table}` (config_key, config_value, config_group, label, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())"
            )->execute([$key, $value, $group, $label]);
        }
    }

    /**
     * 그룹별 설정 일괄 조회
     */
    public function getGroup(string $group): array
    {
        $stmt = $this->db->prepare("SELECT config_key, config_value, label FROM `{$this->table}` WHERE config_group = ? ORDER BY id ASC");
        $stmt->execute([$group]);
        $rows = $stmt->fetchAll();

        $result = [];
        foreach ($rows as $row) {
            $result[$row['config_key']] = $row['config_value'];
        }
        return $result;
    }

    /**
     * 그룹별 설정 일괄 저장
     */
    public function setGroup(string $group, array $data, array $labels = []): void
    {
        foreach ($data as $key => $value) {
            $label = $labels[$key] ?? '';
            $this->set($key, $value ?? '', $group, $label);
        }
    }

    /**
     * SMTP 설정 조회 (편의 메서드)
     */
    public function getSmtpConfig(): array
    {
        $config = $this->getGroup('smtp');
        return [
            'smtp_host'       => $config['smtp_host'] ?? '',
            'smtp_port'       => $config['smtp_port'] ?? '587',
            'smtp_user'       => $config['smtp_user'] ?? '',
            'smtp_pass'       => $config['smtp_pass'] ?? '',
            'smtp_from_email' => $config['smtp_from_email'] ?? '',
            'smtp_from_name'  => $config['smtp_from_name'] ?? 'CentralAdmin',
            'smtp_encryption' => $config['smtp_encryption'] ?? 'tls',
        ];
    }

    /**
     * SMTP 설정 저장
     */
    public function saveSmtpConfig(array $data): void
    {
        $labels = [
            'smtp_host'       => 'SMTP 서버',
            'smtp_port'       => 'SMTP 포트',
            'smtp_user'       => 'SMTP 계정',
            'smtp_pass'       => 'SMTP 비밀번호',
            'smtp_from_email' => '발신자 이메일',
            'smtp_from_name'  => '발신자 이름',
            'smtp_encryption' => '암호화 방식',
        ];
        $this->setGroup('smtp', $data, $labels);
    }
}
