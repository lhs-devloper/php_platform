<?php
/**
 * ThemeSettingsModel - theme_settings 테이블 CRUD
 */
class ThemeSettingsModel extends Model
{
    protected $table = 'theme_settings';

    /**
     * 그룹별 설정 조회
     */
    public function getByGroup($group)
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM theme_settings WHERE setting_group = ? ORDER BY setting_key"
        );
        $stmt->execute([$group]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 전체 설정 조회 (그룹별 정리)
     */
    public function getAllGrouped()
    {
        $stmt = $this->db->query(
            "SELECT * FROM theme_settings ORDER BY setting_group, setting_key"
        );
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row['setting_group']][$row['setting_key']] = $row;
        }
        return $grouped;
    }

    /**
     * 설정 upsert (INSERT ON DUPLICATE KEY UPDATE)
     */
    public function upsert($group, $key, $value, $updatedBy = null)
    {
        $stmt = $this->db->prepare(
            "INSERT INTO theme_settings (setting_group, setting_key, setting_value, updated_by)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_by = VALUES(updated_by)"
        );
        return $stmt->execute([$group, $key, $value, $updatedBy]);
    }

    /**
     * 여러 설정을 한번에 저장
     */
    public function bulkUpsert($group, array $keyValues, $updatedBy = null)
    {
        foreach ($keyValues as $key => $value) {
            $this->upsert($group, $key, $value, $updatedBy);
        }
    }

    /**
     * 특정 설정 삭제
     */
    public function deleteSetting($group, $key)
    {
        $stmt = $this->db->prepare(
            "DELETE FROM theme_settings WHERE setting_group = ? AND setting_key = ?"
        );
        return $stmt->execute([$group, $key]);
    }

    /**
     * 그룹 전체 삭제 (초기화)
     */
    public function deleteGroup($group)
    {
        $stmt = $this->db->prepare("DELETE FROM theme_settings WHERE setting_group = ?");
        return $stmt->execute([$group]);
    }
}
