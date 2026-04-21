<?php
class AiConfigModel extends Model
{
    protected $table = 'ai_config';

    /**
     * 가맹점별 활성 AI 설정 조회
     */
    public function getByFranchise($franchiseId)
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM ai_config WHERE franchise_id = ? AND is_active = 1 LIMIT 1'
        );
        $stmt->execute([$franchiseId]);
        $row = $stmt->fetch();
        return $row !== false ? $row : null;
    }

    /**
     * 복호화된 API 키 반환
     */
    public function getDecryptedApiKey($franchiseId)
    {
        $config = $this->getByFranchise($franchiseId);
        if (!$config || empty($config['api_key'])) {
            // 전역 폴백
            return defined('AI_DEFAULT_API_KEY') && AI_DEFAULT_API_KEY !== '' ? AI_DEFAULT_API_KEY : null;
        }
        $decrypted = AiClient::decryptKey($config['api_key'], AI_ENCRYPTION_KEY);
        return $decrypted !== '' ? $decrypted : null;
    }

    /**
     * AI 설정이 사용 가능한지 확인
     */
    public function isAvailable($franchiseId)
    {
        return $this->getDecryptedApiKey($franchiseId) !== null;
    }
}
