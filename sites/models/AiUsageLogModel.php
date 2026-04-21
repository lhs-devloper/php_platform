<?php
class AiUsageLogModel extends Model
{
    protected $table = 'ai_usage_log';

    /**
     * 사용 이력 기록
     */
    public function logUsage(array $data)
    {
        return $this->insert($data);
    }

    /**
     * 저장된 상담 ID 연결
     */
    public function linkConsultation($logId, $consultationId)
    {
        return $this->update($logId, ['consultation_id' => $consultationId]);
    }

    /**
     * 회원별 AI 사용 이력 조회
     */
    public function getByMember($memberId)
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM ai_usage_log WHERE member_id = ? ORDER BY created_at DESC'
        );
        $stmt->execute([$memberId]);
        return $stmt->fetchAll();
    }
}
