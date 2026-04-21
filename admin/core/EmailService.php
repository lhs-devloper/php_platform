<?php
/**
 * SMTP 이메일 발송 서비스
 * PHP socket 기반 SMTP 클라이언트 (외부 라이브러리 불필요)
 * SMTP 설정은 DB(system_config)에서 로드
 */
class EmailService
{
    private $host;
    private $port;
    private $user;
    private $pass;
    private $fromEmail;
    private $fromName;
    private $encryption; // 'tls', 'ssl', ''
    private $lastError = '';

    /**
     * @param array|null $config  외부에서 설정을 직접 주입 (테스트용). null이면 DB에서 로드.
     */
    public function __construct(?array $config = null)
    {
        if ($config !== null) {
            // 직접 주입 (SMTP 설정 테스트 시 사용)
            $this->applyConfig($config);
        } else {
            // DB에서 로드
            $this->loadFromDatabase();
        }
    }

    private function loadFromDatabase(): void
    {
        try {
            $configModel = new SystemConfigModel();
            $cfg = $configModel->getSmtpConfig();
            $this->applyConfig($cfg);
        } catch (Exception $e) {
            // DB 연결 실패 등 예외 시 빈 값 유지
            $this->host = '';
            $this->port = 587;
            $this->user = '';
            $this->pass = '';
            $this->fromEmail = '';
            $this->fromName = defined('APP_NAME') ? APP_NAME : 'CentralAdmin';
            $this->encryption = 'tls';
        }
    }

    private function applyConfig(array $cfg): void
    {
        $this->host       = $cfg['smtp_host'] ?? '';
        $this->port       = (int)($cfg['smtp_port'] ?? 587);
        $this->user       = $cfg['smtp_user'] ?? '';
        $this->pass       = $cfg['smtp_pass'] ?? '';
        $this->fromEmail  = $cfg['smtp_from_email'] ?? '';
        $this->fromName   = $cfg['smtp_from_name'] ?? (defined('APP_NAME') ? APP_NAME : 'CentralAdmin');
        $this->encryption = $cfg['smtp_encryption'] ?? 'tls';
    }

    /**
     * SMTP 설정이 완료되었는지 확인
     */
    public function isConfigured(): bool
    {
        return !empty($this->host) && !empty($this->fromEmail);
    }

    /**
     * 이메일 발송
     * @param string|array $to       수신자 이메일 (문자열 또는 배열)
     * @param string       $subject  제목
     * @param string       $bodyHtml HTML 본문
     * @param string       $bodyText 텍스트 본문 (선택)
     * @param string       $cc       CC (콤마 구분)
     * @param string       $bcc      BCC (콤마 구분)
     * @return bool
     */
    public function send($to, string $subject, string $bodyHtml, string $bodyText = '', string $cc = '', string $bcc = ''): bool
    {
        $this->lastError = '';

        if (!$this->isConfigured()) {
            $this->lastError = 'SMTP 설정이 완료되지 않았습니다. 이메일 관리 > SMTP 설정에서 설정을 완료하세요.';
            return false;
        }

        // 수신자 목록 정규화
        $toList = is_array($to) ? $to : array_filter(array_map('trim', explode(',', $to)));
        if (empty($toList)) {
            $this->lastError = '수신자를 입력해주세요.';
            return false;
        }

        $allRecipients = $toList;
        $ccList  = $cc  ? array_filter(array_map('trim', explode(',', $cc)))  : [];
        $bccList = $bcc ? array_filter(array_map('trim', explode(',', $bcc))) : [];
        $allRecipients = array_merge($allRecipients, $ccList, $bccList);

        try {
            // SMTP 연결
            $socket = $this->connect();
            if (!$socket) return false;

            // EHLO
            $this->sendCommand($socket, "EHLO " . gethostname());

            // STARTTLS (if needed)
            if ($this->encryption === 'tls') {
                $this->sendCommand($socket, "STARTTLS", 220);
                $cryptoResult = @stream_socket_enable_crypto($socket, true, $this->getCryptoMethod());
                if ($cryptoResult !== true) {
                    $this->lastError = 'STARTTLS 암호화 실패: TLS 핸드셰이크에 실패했습니다. (서버: ' . $this->host . ':' . $this->port . ')';
                    fclose($socket);
                    return false;
                }
                $this->sendCommand($socket, "EHLO " . gethostname());
            }

            // AUTH LOGIN
            if (!empty($this->user)) {
                $this->sendCommand($socket, "AUTH LOGIN", 334);
                $this->sendCommand($socket, base64_encode($this->user), 334);
                $this->sendCommand($socket, base64_encode($this->pass), 235);
            }

            // MAIL FROM
            $this->sendCommand($socket, "MAIL FROM:<{$this->fromEmail}>", 250);

            // RCPT TO
            foreach ($allRecipients as $rcpt) {
                $this->sendCommand($socket, "RCPT TO:<{$rcpt}>", 250);
            }

            // DATA
            $this->sendCommand($socket, "DATA", 354);

            // 메시지 빌드
            $boundary = '----=_Part_' . md5(uniqid(mt_rand(), true));
            $message  = $this->buildMessage($toList, $ccList, $subject, $bodyHtml, $bodyText, $boundary);

            // 메시지 전송
            fwrite($socket, $message . "\r\n.\r\n");
            $response = $this->getResponse($socket);
            if (strpos($response, '250') !== 0) {
                $this->lastError = 'DATA 완료 실패: ' . $response;
                fclose($socket);
                return false;
            }

            // QUIT
            $this->sendCommand($socket, "QUIT", 221);
            fclose($socket);
            return true;

        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * 마지막 에러 메시지 반환
     */
    public function getLastError(): string
    {
        return $this->lastError;
    }

    /**
     * SMTP 연결 테스트
     */
    public function testConnection(): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'message' => 'SMTP 설정이 완료되지 않았습니다.'];
        }

        try {
            $socket = $this->connect();
            if (!$socket) {
                return ['success' => false, 'message' => $this->lastError];
            }

            $this->sendCommand($socket, "EHLO " . gethostname());

            if ($this->encryption === 'tls') {
                $this->sendCommand($socket, "STARTTLS", 220);
                $cryptoResult = @stream_socket_enable_crypto($socket, true, $this->getCryptoMethod());
                if ($cryptoResult !== true) {
                    fclose($socket);
                    return ['success' => false, 'message' => 'STARTTLS 암호화 실패: TLS 핸드셰이크에 실패했습니다.'];
                }
                $this->sendCommand($socket, "EHLO " . gethostname());
            }

            if (!empty($this->user)) {
                $this->sendCommand($socket, "AUTH LOGIN", 334);
                $this->sendCommand($socket, base64_encode($this->user), 334);
                $this->sendCommand($socket, base64_encode($this->pass), 235);
            }

            $this->sendCommand($socket, "QUIT", 221);
            fclose($socket);

            return ['success' => true, 'message' => 'SMTP 연결 및 인증 성공'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // --------------------------------------------------
    // Private helpers
    // --------------------------------------------------

    private function connect()
    {
        $protocol = ($this->encryption === 'ssl') ? 'ssl://' : '';
        $errno = 0;
        $errstr = '';

        // SSL 컨텍스트: TLS 1.2+ 허용, 인증서 검증 완화 (로컬/테스트 환경 호환)
        $sslContext = stream_context_create([
            'ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true,
                'crypto_method'     => $this->getCryptoMethod(),
            ],
        ]);

        $socket = @stream_socket_client(
            $protocol . $this->host . ':' . $this->port,
            $errno, $errstr, 30,
            STREAM_CLIENT_CONNECT,
            $sslContext
        );

        if (!$socket) {
            $this->lastError = "SMTP 서버 연결 실패: {$errstr} ({$errno})";
            return false;
        }

        stream_set_timeout($socket, 30);
        $response = $this->getResponse($socket);
        if (strpos($response, '220') !== 0) {
            $this->lastError = "SMTP 서버 응답 오류: {$response}";
            fclose($socket);
            return false;
        }

        return $socket;
    }

    /**
     * TLS 1.2 / 1.3을 우선 사용하는 crypto method 반환
     */
    private function getCryptoMethod(): int
    {
        // TLS 1.2 (PHP 5.6+)
        $method = STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;

        // TLS 1.3 (PHP 7.4+)
        if (defined('STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT')) {
            $method |= STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT;
        }

        return $method;
    }

    private function sendCommand($socket, string $command, int $expectedCode = 250): string
    {
        fwrite($socket, $command . "\r\n");
        $response = $this->getResponse($socket);
        if ($expectedCode && strpos($response, (string)$expectedCode) !== 0) {
            throw new Exception("SMTP 오류 [{$command}]: {$response}");
        }
        return $response;
    }

    private function getResponse($socket): string
    {
        $response = '';
        while (true) {
            $line = fgets($socket, 4096);
            if ($line === false) break;
            $response .= $line;
            // 응답 끝: "250 OK" (4번째 문자가 공백이면 마지막 줄)
            if (isset($line[3]) && $line[3] === ' ') break;
        }
        return trim($response);
    }

    private function buildMessage(array $toList, array $ccList, string $subject, string $bodyHtml, string $bodyText, string $boundary): string
    {
        $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        $encodedFrom    = '=?UTF-8?B?' . base64_encode($this->fromName) . '?= <' . $this->fromEmail . '>';

        $headers  = "From: {$encodedFrom}\r\n";
        $headers .= "To: " . implode(', ', $toList) . "\r\n";
        if (!empty($ccList)) {
            $headers .= "Cc: " . implode(', ', $ccList) . "\r\n";
        }
        $headers .= "Subject: {$encodedSubject}\r\n";
        $headers .= "Date: " . date('r') . "\r\n";
        $headers .= "Message-ID: <" . md5(uniqid(mt_rand(), true)) . "@" . gethostname() . ">\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";
        $headers .= "\r\n";

        // Text part
        $textPart = $bodyText ?: strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $bodyHtml));
        $body  = "--{$boundary}\r\n";
        $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $body .= chunk_split(base64_encode($textPart)) . "\r\n";

        // HTML part
        $body .= "--{$boundary}\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $body .= chunk_split(base64_encode($this->wrapHtml($bodyHtml))) . "\r\n";

        $body .= "--{$boundary}--\r\n";

        return $headers . $body;
    }

    private function wrapHtml(string $content): string
    {
        return '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body style="font-family:\'Pretendard\',-apple-system,sans-serif;font-size:14px;line-height:1.6;color:#333;">' . $content . '</body></html>';
    }
}
