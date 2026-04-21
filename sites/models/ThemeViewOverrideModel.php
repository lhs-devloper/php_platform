<?php
/**
 * ThemeViewOverrideModel - theme_view_overrides 테이블 CRUD
 */
class ThemeViewOverrideModel extends Model
{
    protected $table = 'theme_view_overrides';

    /**
     * 전체 오버라이드 목록 조회
     */
    public function getAll()
    {
        $stmt = $this->db->query(
            "SELECT id, view_path, override_type, is_active, updated_by, created_at, updated_at
             FROM theme_view_overrides ORDER BY view_path"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 뷰 경로로 오버라이드 조회
     */
    public function getByViewPath($viewPath, $overrideType = 'partial')
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM theme_view_overrides WHERE view_path = ? AND override_type = ? LIMIT 1"
        );
        $stmt->execute([$viewPath, $overrideType]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row !== false ? $row : null;
    }

    /**
     * 오버라이드 저장 (upsert)
     */
    public function upsert($viewPath, $overrideType, $htmlContent, $cssContent, $updatedBy = null)
    {
        $stmt = $this->db->prepare(
            "INSERT INTO theme_view_overrides (view_path, override_type, html_content, css_content, updated_by)
             VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                html_content = VALUES(html_content),
                css_content = VALUES(css_content),
                updated_by = VALUES(updated_by)"
        );
        return $stmt->execute([$viewPath, $overrideType, $htmlContent, $cssContent, $updatedBy]);
    }

    /**
     * 활성/비활성 토글
     */
    public function toggleActive($id)
    {
        $stmt = $this->db->prepare(
            "UPDATE theme_view_overrides SET is_active = NOT is_active WHERE id = ?"
        );
        return $stmt->execute([$id]);
    }

    /**
     * 오버라이드 삭제 (기본 뷰로 복원)
     */
    public function deleteByViewPath($viewPath, $overrideType = 'partial')
    {
        $stmt = $this->db->prepare(
            "DELETE FROM theme_view_overrides WHERE view_path = ? AND override_type = ?"
        );
        return $stmt->execute([$viewPath, $overrideType]);
    }
}
