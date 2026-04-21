<?php
/**
 * AI API 클라이언트 (Claude / OpenAI)
 * curl 기반 HTTP 통신, 외부 라이브러리 없음
 */
class AiClient
{
    private $apiKey;
    private $modelName;
    private $provider;
    private $timeout;

    private const CLAUDE_API_URL = 'https://api.anthropic.com/v1/messages';
    private const CLAUDE_API_VERSION = '2023-06-01';
    private const OPENAI_API_URL = 'https://api.openai.com/v1/chat/completions';

    public function __construct(string $apiKey, string $modelName = 'claude-sonnet-4-20250514', string $provider = 'CLAUDE', int $timeout = 30)
    {
        $this->apiKey = $apiKey;
        $this->modelName = $modelName;
        $this->provider = strtoupper($provider);
        $this->timeout = $timeout;
    }

    /**
     * 상담 소견 생성 요청
     *
     * @param string $systemPrompt 시스템 프롬프트
     * @param string $userMessage  회원 분석 데이터 텍스트
     * @return array ['content' => parsed JSON array, 'usage' => token usage array]
     * @throws RuntimeException API 호출 실패 시
     */
    public function generateConsultation(string $systemPrompt, string $userMessage): array
    {
        if ($this->provider === 'CLAUDE') {
            return $this->callClaude($systemPrompt, $userMessage);
        }
        return $this->callOpenAI($systemPrompt, $userMessage);
    }

    private function callClaude(string $systemPrompt, string $userMessage): array
    {
        $payload = [
            'model'      => $this->modelName,
            'max_tokens' => 2048,
            'system'     => $systemPrompt,
            'messages'   => [
                ['role' => 'user', 'content' => $userMessage],
            ],
        ];

        $headers = [
            'Content-Type: application/json',
            'x-api-key: ' . $this->apiKey,
            'anthropic-version: ' . self::CLAUDE_API_VERSION,
        ];

        $response = $this->curlPost(self::CLAUDE_API_URL, $payload, $headers);

        if (!isset($response['content'][0]['text'])) {
            $errorMsg = isset($response['error']['message']) ? $response['error']['message'] : 'Unknown Claude API error';
            throw new RuntimeException('Claude API 오류: ' . $errorMsg);
        }

        $rawText = $response['content'][0]['text'];
        $parsed = $this->parseJsonResponse($rawText);

        $usage = [
            'prompt_tokens'     => isset($response['usage']['input_tokens']) ? (int)$response['usage']['input_tokens'] : null,
            'completion_tokens' => isset($response['usage']['output_tokens']) ? (int)$response['usage']['output_tokens'] : null,
        ];
        $usage['total_tokens'] = ($usage['prompt_tokens'] ?? 0) + ($usage['completion_tokens'] ?? 0);

        return ['content' => $parsed, 'usage' => $usage];
    }

    private function callOpenAI(string $systemPrompt, string $userMessage): array
    {
        $payload = [
            'model'      => $this->modelName,
            'max_tokens' => 2048,
            'messages'   => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userMessage],
            ],
        ];

        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey,
        ];

        $response = $this->curlPost(self::OPENAI_API_URL, $payload, $headers);

        if (!isset($response['choices'][0]['message']['content'])) {
            $errorMsg = isset($response['error']['message']) ? $response['error']['message'] : 'Unknown OpenAI API error';
            throw new RuntimeException('OpenAI API 오류: ' . $errorMsg);
        }

        $rawText = $response['choices'][0]['message']['content'];
        $parsed = $this->parseJsonResponse($rawText);

        $usage = [
            'prompt_tokens'     => isset($response['usage']['prompt_tokens']) ? (int)$response['usage']['prompt_tokens'] : null,
            'completion_tokens' => isset($response['usage']['completion_tokens']) ? (int)$response['usage']['completion_tokens'] : null,
            'total_tokens'      => isset($response['usage']['total_tokens']) ? (int)$response['usage']['total_tokens'] : null,
        ];

        return ['content' => $parsed, 'usage' => $usage];
    }

    /**
     * curl POST 요청
     */
    private function curlPost(string $url, array $payload, array $headers): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($result === false) {
            throw new RuntimeException('API 연결 실패: ' . $curlError);
        }

        if ($httpCode >= 400) {
            $errorBody = json_decode($result, true);
            $msg = isset($errorBody['error']['message']) ? $errorBody['error']['message'] : "HTTP {$httpCode}";
            throw new RuntimeException('API 오류 (' . $httpCode . '): ' . $msg);
        }

        $decoded = json_decode($result, true);
        if ($decoded === null) {
            throw new RuntimeException('API 응답 JSON 파싱 실패');
        }

        return $decoded;
    }

    /**
     * AI 응답에서 JSON 추출 및 파싱
     * 마크다운 코드펜스(```json ... ```) 제거 후 파싱
     */
    private function parseJsonResponse(string $text): array
    {
        $text = trim($text);

        // 마크다운 코드펜스 제거
        if (preg_match('/```(?:json)?\s*\n?(.*?)\n?\s*```/s', $text, $matches)) {
            $text = trim($matches[1]);
        }

        $decoded = json_decode($text, true);
        if ($decoded === null) {
            throw new RuntimeException('AI 응답 JSON 파싱 실패: ' . mb_substr($text, 0, 200));
        }

        // 필수 필드 검증
        $requiredFields = ['overall_assessment'];
        foreach ($requiredFields as $field) {
            if (!isset($decoded[$field]) || $decoded[$field] === '') {
                throw new RuntimeException('AI 응답에 필수 필드 누락: ' . $field);
            }
        }

        return $decoded;
    }

    /**
     * API 키 암호화
     */
    public static function encryptKey(string $plainKey, string $encryptionKey): string
    {
        $iv = openssl_random_pseudo_bytes(16);
        $encrypted = openssl_encrypt($plainKey, 'AES-256-CBC', $encryptionKey, 0, $iv);
        return base64_encode($iv . '::' . $encrypted);
    }

    /**
     * API 키 복호화
     */
    public static function decryptKey(string $encryptedKey, string $encryptionKey): string
    {
        $data = base64_decode($encryptedKey);
        $parts = explode('::', $data, 2);
        if (count($parts) !== 2) {
            return '';
        }
        return openssl_decrypt($parts[1], 'AES-256-CBC', $encryptionKey, 0, $parts[0]);
    }
}
