<?php
declare(strict_types=1);

function mail_last_error(): string
{
    return (string)($GLOBALS['empowerme_mail_last_error'] ?? '');
}

function mail_set_error(string $message): void
{
    $GLOBALS['empowerme_mail_last_error'] = $message;
}

function mail_log_event(string $status, string $to, string $subject, string $detail = ''): void
{
    $dir = defined('ROOT_PATH') ? ROOT_PATH . '/storage' : dirname(__DIR__) . '/storage';
    if (!is_dir($dir)) @mkdir($dir, 0770, true);
    $line = sprintf(
        "[%s] %s to=%s subject=%s%s\n",
        date('c'),
        strtoupper($status),
        str_replace(["\r","\n"], '', $to),
        str_replace(["\r","\n"], '', $subject),
        $detail !== '' ? ' detail=' . str_replace(["\r","\n"], ' ', $detail) : ''
    );
    @file_put_contents($dir . '/mail.log', $line, FILE_APPEND | LOCK_EX);
}

function smtp_read_response($socket): array
{
    $data = '';
    $code = 0;
    while (($line = fgets($socket, 8192)) !== false) {
        $data .= $line;
        if (strlen($line) >= 3 && ctype_digit(substr($line, 0, 3))) {
            $code = (int)substr($line, 0, 3);
        }
        if (strlen($line) < 4 || $line[3] !== '-') break;
    }

    $meta = stream_get_meta_data($socket);
    if (!empty($meta['timed_out'])) {
        return [0, trim($data) ?: 'SMTP connection timed out.'];
    }
    return [$code, trim($data)];
}

function smtp_command($socket, string $command, array $expected, string &$error, bool $sensitive = false): bool
{
    $written = @fwrite($socket, $command . "\r\n");
    if ($written === false) {
        $error = 'Could not write to the SMTP server.';
        return false;
    }
    [$code, $response] = smtp_read_response($socket);
    if (!in_array($code, $expected, true)) {
        $shown = $sensitive ? '[credentials hidden]' : $command;
        $error = 'SMTP command failed (' . $shown . '): ' . ($response ?: 'no response');
        return false;
    }
    return true;
}

function smtp_deliver_once(string $to, string $subject, string $html, string &$error): bool
{
    $host = trim(setting('smtp_host'));
    $port = (int)setting('smtp_port', '587');
    $enc = strtolower(trim(setting('smtp_encryption', 'tls')));
    $user = trim(setting('smtp_username'));
    $pass = decrypt_secret(setting('smtp_password'));
    $from = trim(setting('smtp_from_email') ?: setting('public_email'));
    $fromName = trim(setting('smtp_from_name', setting('program_name')));

    if ($host === '') {
        $error = 'SMTP host is not configured.';
        return false;
    }
    if ($port < 1 || $port > 65535) {
        $error = 'SMTP port is invalid.';
        return false;
    }
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        $error = 'Recipient email address is invalid.';
        return false;
    }
    if (!filter_var($from, FILTER_VALIDATE_EMAIL)) {
        $error = 'Sender email address is invalid.';
        return false;
    }
    if (!in_array($enc, ['tls','ssl','none'], true)) {
        $enc = 'tls';
    }

    $transport = $enc === 'ssl' ? 'ssl://' : '';
    $target = $transport . $host . ':' . $port;
    $context = stream_context_create([
        'ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
            'allow_self_signed' => false,
        ],
    ]);

    $socket = @stream_socket_client(
        $target,
        $errno,
        $errstr,
        20,
        STREAM_CLIENT_CONNECT,
        $context
    );
    if (!$socket) {
        $error = 'Could not connect to SMTP server: ' . ($errstr ?: 'connection failed') . ($errno ? ' (' . $errno . ')' : '');
        return false;
    }
    stream_set_timeout($socket, 20);

    [$bannerCode, $banner] = smtp_read_response($socket);
    if ($bannerCode !== 220) {
        $error = 'SMTP server rejected the connection: ' . ($banner ?: 'no response');
        fclose($socket);
        return false;
    }

    $domain = (string)($_SERVER['SERVER_NAME'] ?? '');
    if ($domain === '') {
        $domain = (string)(parse_url(app_url(), PHP_URL_HOST) ?: 'localhost');
    }
    $domain = preg_replace('/[^A-Za-z0-9.-]/', '', $domain) ?: 'localhost';

    if (!smtp_command($socket, 'EHLO ' . $domain, [250], $error)) {
        fclose($socket);
        return false;
    }

    if ($enc === 'tls') {
        if (!smtp_command($socket, 'STARTTLS', [220], $error)) {
            fclose($socket);
            return false;
        }
        if (!@stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            $error = 'Could not establish the encrypted SMTP connection.';
            fclose($socket);
            return false;
        }
        if (!smtp_command($socket, 'EHLO ' . $domain, [250], $error)) {
            fclose($socket);
            return false;
        }
    }

    if ($user !== '') {
        if (!smtp_command($socket, 'AUTH LOGIN', [334], $error)) {
            fclose($socket);
            return false;
        }
        if (!smtp_command($socket, base64_encode($user), [334], $error, true)) {
            fclose($socket);
            return false;
        }
        if (!smtp_command($socket, base64_encode($pass), [235], $error, true)) {
            fclose($socket);
            return false;
        }
    }

    if (!smtp_command($socket, 'MAIL FROM:<' . $from . '>', [250], $error)) {
        fclose($socket);
        return false;
    }
    if (!smtp_command($socket, 'RCPT TO:<' . $to . '>', [250,251], $error)) {
        fclose($socket);
        return false;
    }
    if (!smtp_command($socket, 'DATA', [354], $error)) {
        fclose($socket);
        return false;
    }

    $safeFromName = str_replace(['"', "\r", "\n"], '', $fromName);
    $safeSubject = str_replace(["\r","\n"], '', $subject);
    $messageIdDomain = filter_var($domain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) ? $domain : 'empowermeprogram.org';

    $headers = [
        'From: ' . sprintf('"%s" <%s>', $safeFromName, $from),
        'Reply-To: <' . $from . '>',
        'To: <' . $to . '>',
        'Subject: =?UTF-8?B?' . base64_encode($safeSubject) . '?=',
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8',
        'Content-Transfer-Encoding: 8bit',
        'Date: ' . date(DATE_RFC2822),
        'Message-ID: <' . bin2hex(random_bytes(12)) . '@' . $messageIdDomain . '>',
        'X-Mailer: EmpowerME',
    ];

    $normalizedHtml = str_replace(["\r\n","\r"], "\n", $html);
    $normalizedHtml = preg_replace('/(?m)^\./', '..', $normalizedHtml) ?? $normalizedHtml;
    $normalizedHtml = str_replace("\n", "\r\n", $normalizedHtml);
    $payload = implode("\r\n", $headers) . "\r\n\r\n" . $normalizedHtml . "\r\n.\r\n";

    if (@fwrite($socket, $payload) === false) {
        $error = 'Could not send the email data to the SMTP server.';
        fclose($socket);
        return false;
    }

    [$dataCode, $dataResponse] = smtp_read_response($socket);
    if ($dataCode !== 250) {
        $error = 'SMTP server did not accept the email: ' . ($dataResponse ?: 'no response');
        $quitError = '';
        @smtp_command($socket, 'QUIT', [221,250], $quitError);
        fclose($socket);
        return false;
    }

    $quitError = '';
    @smtp_command($socket, 'QUIT', [221,250], $quitError);
    fclose($socket);
    return true;
}

function mail_html(string $to, string $subject, string $html): bool
{
    mail_set_error('');
    $lastError = '';

    for ($attempt = 1; $attempt <= 2; $attempt++) {
        $error = '';
        if (smtp_deliver_once($to, $subject, $html, $error)) {
            mail_log_event('sent', $to, $subject, 'attempt=' . $attempt);
            return true;
        }
        $lastError = $error ?: 'Unknown SMTP error.';
        if ($attempt < 2) usleep(250000);
    }

    mail_set_error($lastError);
    mail_log_event('failed', $to, $subject, $lastError);
    return false;
}

function email_shell(string $title, string $bodyHtml): string
{
    $name = h(setting('program_name', 'EmpowerME Grant Program'));
    return '<!doctype html><html><body style="margin:0;background:#f3f6f8;font-family:Arial,sans-serif;color:#18313f"><table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f3f6f8;padding:28px 12px"><tr><td align="center"><table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px;background:#fff;border-radius:16px;overflow:hidden"><tr><td style="background:#064f78;padding:26px;color:#fff;font-size:22px;font-weight:700">'.$name.'</td></tr><tr><td style="padding:30px"><h1 style="font-size:24px;margin:0 0 18px">'.h($title).'</h1>'.$bodyHtml.'<p style="margin-top:28px;color:#657783;font-size:13px">This is an automated message from '.$name.'.</p></td></tr></table></td></tr></table></body></html>';
}

function send_application_confirmation(array $app): bool
{
    if (empty($app['email']) || empty($app['tracking_code']) || empty($app['full_name'])) {
        mail_set_error('Application email data is incomplete.');
        return false;
    }

    $trackUrl = app_url('track.php?code=' . urlencode((string)$app['tracking_code']));
    $body = '<p>Hello '.h((string)$app['full_name']).',</p><p>We received your grant application. Keep the tracking code below private; it is used to access your application updates.</p><div style="font-size:26px;font-weight:700;letter-spacing:2px;background:#eef5f8;padding:16px;border-radius:10px;margin:22px 0">'.h((string)$app['tracking_code']).'</div><p><a href="'.h($trackUrl).'" style="display:inline-block;background:#064f78;color:#fff;text-decoration:none;padding:12px 18px;border-radius:8px">Track your application</a></p><p>Submitting an application does not guarantee funding. All applications are subject to eligibility and review.</p>';
    return mail_html((string)$app['email'], 'Your EmpowerME grant application was received', email_shell('Application received', $body));
}

function send_update_email(array $app, array $update): bool
{
    if (empty($app['email']) || empty($app['tracking_code']) || empty($app['full_name'])) {
        mail_set_error('Application email data is incomplete.');
        return false;
    }

    $trackUrl = app_url('track.php?code=' . urlencode((string)$app['tracking_code']));
    $body = '<p>Hello '.h((string)$app['full_name']).',</p><p>There is a new update on your grant application.</p><h2 style="font-size:18px">'.h((string)($update['title'] ?? 'Application update')).'</h2><div style="line-height:1.65">'.nl2br(h((string)($update['message'] ?? ''))).'</div>';
    if (!empty($update['link_url']) && safe_return_url((string)$update['link_url'])) {
        $label = !empty($update['link_label']) ? (string)$update['link_label'] : 'Open link';
        $body .= '<p><a href="'.h((string)$update['link_url']).'" style="display:inline-block;background:#0b79ad;color:#fff;text-decoration:none;padding:11px 16px;border-radius:8px">'.h($label).'</a></p>';
    }
    $body .= '<p><a href="'.h($trackUrl).'" style="display:inline-block;background:#064f78;color:#fff;text-decoration:none;padding:12px 18px;border-radius:8px">View application progress</a></p>';
    return mail_html((string)$app['email'], 'Update to your EmpowerME grant application', email_shell('Application update', $body));
}
