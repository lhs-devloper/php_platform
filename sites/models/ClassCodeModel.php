<?php
class ClassCodeModel extends Model
{
    protected $table = 'class_code';

    /**
     * 활성 수강반 목록 (드롭다운용)
     */
    public function getActiveList()
    {
        return $this->db->query(
            "SELECT id, code, name FROM class_code WHERE is_active = 1 ORDER BY code"
        )->fetchAll();
    }

    /**
     * 전체 목록 + 소속 회원 수 집계
     */
    public function getAllWithMemberCount()
    {
        $sql = "SELECT c.*, COALESCE(m.member_count, 0) AS member_count
                FROM class_code c
                LEFT JOIN (
                    SELECT class_code_id, COUNT(*) AS member_count
                    FROM member
                    WHERE status != 'WITHDRAWN'
                    GROUP BY class_code_id
                ) m ON m.class_code_id = c.id
                ORDER BY c.code";
        return $this->db->query($sql)->fetchAll();
    }

    /**
     * code 중복 확인 (수정 시 자기 자신 제외)
     */
    public function codeExists($code, $excludeId = null)
    {
        if ($excludeId) {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM class_code WHERE code = ? AND id != ?");
            $stmt->execute([$code, $excludeId]);
        } else {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM class_code WHERE code = ?");
            $stmt->execute([$code]);
        }
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * 활성/비활성 토글
     */
    public function toggleActive($id)
    {
        $stmt = $this->db->prepare("UPDATE class_code SET is_active = NOT is_active WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * 수강반 소속 회원 수 조회
     */
    public function getMemberCount($classCodeId)
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM member WHERE class_code_id = ? AND status != 'WITHDRAWN'"
        );
        $stmt->execute([$classCodeId]);
        return (int)$stmt->fetchColumn();
    }
}
