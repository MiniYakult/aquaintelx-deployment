<?php
// ============================================================
// smtp_mailer.php — Simple SMTP mail sender for AquaIntelX
// Works on Railway/local when SMTP env vars are configured.
// No Composer/PHPMailer required.
// ============================================================
declare(strict_types=1);

function aquaintelx_env(string $key, ?string $fallback = null): ?string {
    $value = getenv($key);
    if ($value === false || $value === '') {
        return $fallback;
    }
    return $value;
}

function aquaintelx_mail_config(): array {
    return [
        'host'       => aquaintelx_env('SMTP_HOST', 'smtp.gmail.com'),
        'port'       => (int)aquaintelx_env('SMTP_PORT', '587'),
        'username'   => aquaintelx_env('SMTP_USERNAME', aquaintelx_env('MAIL_USERNAME', '')),
        'password'   => aquaintelx_env('SMTP_PASSWORD', aquaintelx_env('MAIL_PASSWORD', '')),
        'from_email' => aquaintelx_env('SMTP_FROM_EMAIL', aquaintelx_env('MAIL_FROM_EMAIL', aquaintelx_env('SMTP_USERNAME', ''))),
        'from_name'  => aquaintelx_env('SMTP_FROM_NAME', aquaintelx_env('MAIL_FROM_NAME', 'AquaIntelX')),
        'secure'     => strtolower((string)aquaintelx_env('SMTP_SECURE', 'tls')), // tls or ssl
    ];
}

function aquaintelx_sanitize_header(string $value): string {
    return trim(str_replace(["\r", "\n"], '', $value));
}

function aquaintelx_smtp_read($socket): string {
    $data = '';
    while (($line = fgets($socket, 515)) !== false) {
        $data .= $line;
        // SMTP multiline responses have a dash after the code, e.g. 250-...
        if (preg_match('/^\d{3}\s/', $line)) {
            break;
        }
    }
    return $data;
}

function aquaintelx_smtp_command($socket, string $command, array $expectedCodes): string {
    fwrite($socket, $command . "\r\n");
    $response = aquaintelx_smtp_read($socket);
    $code = (int)substr($response, 0, 3);

    if (!in_array($code, $expectedCodes, true)) {
        throw new RuntimeException("SMTP command failed. Command: {$command}; Response: {$response}");
    }

    return $response;
}

function aquaintelx_dot_stuff(string $message): string {
    $message = str_replace(["\r\n", "\r"], "\n", $message);
    $lines = explode("\n", $message);

    foreach ($lines as &$line) {
        if (isset($line[0]) && $line[0] === '.') {
            $line = '.' . $line;
        }
    }

    return implode("\r\n", $lines);
}

function aquaintelx_send_mail(string $toEmail, string $toName, string $subject, string $htmlBody, string $textBody = ''): bool {
    $config = aquaintelx_mail_config();

    if (empty($config['host']) || empty($config['username']) || empty($config['password']) || empty($config['from_email'])) {
        throw new RuntimeException('SMTP is not configured. Add SMTP_HOST, SMTP_PORT, SMTP_USERNAME, SMTP_PASSWORD, and SMTP_FROM_EMAIL in Railway Variables.');
    }

    $host = $config['host'];
    $port = $config['port'];
    $secure = $config['secure'];
    $remote = $secure === 'ssl' ? "ssl://{$host}:{$port}" : "tcp://{$host}:{$port}";

    $errno = 0;
    $errstr = '';
    $socket = stream_socket_client($remote, $errno, $errstr, 25, STREAM_CLIENT_CONNECT);

    if (!$socket) {
        throw new RuntimeException("Could not connect to SMTP server: {$errstr} ({$errno})");
    }

    stream_set_timeout($socket, 25);

    try {
        $greeting = aquaintelx_smtp_read($socket);
        if ((int)substr($greeting, 0, 3) !== 220) {
            throw new RuntimeException("Invalid SMTP greeting: {$greeting}");
        }

        $serverName = $_SERVER['SERVER_NAME'] ?? 'localhost';
        aquaintelx_smtp_command($socket, "EHLO {$serverName}", [250]);

        if ($secure === 'tls') {
            aquaintelx_smtp_command($socket, 'STARTTLS', [220]);

            $cryptoOk = stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            if ($cryptoOk !== true) {
                throw new RuntimeException('Could not enable TLS encryption for SMTP connection.');
            }

            aquaintelx_smtp_command($socket, "EHLO {$serverName}", [250]);
        }

        aquaintelx_smtp_command($socket, 'AUTH LOGIN', [334]);
        aquaintelx_smtp_command($socket, base64_encode((string)$config['username']), [334]);
        aquaintelx_smtp_command($socket, base64_encode((string)$config['password']), [235]);

        $fromEmail = aquaintelx_sanitize_header((string)$config['from_email']);
        $fromName = aquaintelx_sanitize_header((string)$config['from_name']);
        $toEmailClean = aquaintelx_sanitize_header($toEmail);
        $toNameClean = aquaintelx_sanitize_header($toName ?: $toEmailClean);
        $subjectClean = aquaintelx_sanitize_header($subject);

        aquaintelx_smtp_command($socket, "MAIL FROM:<{$fromEmail}>", [250]);
        aquaintelx_smtp_command($socket, "RCPT TO:<{$toEmailClean}>", [250, 251]);
        aquaintelx_smtp_command($socket, 'DATA', [354]);

        $boundary = 'b_' . bin2hex(random_bytes(12));
        $date = date('r');
        $messageIdHost = preg_replace('/[^A-Za-z0-9.-]/', '', $_SERVER['SERVER_NAME'] ?? 'aquaintelx.local');
        $messageId = bin2hex(random_bytes(12)) . '@' . ($messageIdHost ?: 'aquaintelx.local');

        if ($textBody === '') {
            $textBody = trim(strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlBody)));
        }

        $headers = [];
        $headers[] = "Date: {$date}";
        $headers[] = "From: {$fromName} <{$fromEmail}>";
        $headers[] = "To: {$toNameClean} <{$toEmailClean}>";
        $headers[] = "Subject: {$subjectClean}";
        $headers[] = "Message-ID: <{$messageId}>";
        $headers[] = "MIME-Version: 1.0";
        $headers[] = "Content-Type: multipart/alternative; boundary=\"{$boundary}\"";

        $body = implode("\r\n", $headers) . "\r\n\r\n";
        $body .= "--{$boundary}\r\n";
        $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
        $body .= $textBody . "\r\n\r\n";
        $body .= "--{$boundary}\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
        $body .= $htmlBody . "\r\n\r\n";
        $body .= "--{$boundary}--\r\n";

        fwrite($socket, aquaintelx_dot_stuff($body) . "\r\n.\r\n");
        $dataResponse = aquaintelx_smtp_read($socket);
        $dataCode = (int)substr($dataResponse, 0, 3);

        if ($dataCode !== 250) {
            throw new RuntimeException("SMTP DATA failed: {$dataResponse}");
        }

        aquaintelx_smtp_command($socket, 'QUIT', [221, 250]);
        fclose($socket);
        return true;
    } catch (Throwable $e) {
        if (is_resource($socket)) {
            @fwrite($socket, "QUIT\r\n");
            @fclose($socket);
        }
        throw $e;
    }
}
