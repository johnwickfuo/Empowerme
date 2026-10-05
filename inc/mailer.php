<?php
declare(strict_types=1);

function mail_html(string $to, string $subject, string $html): bool
{
    $host = trim(setting('smtp_host'));
    if ($host === '') return false;
    $port = (int)setting('smtp_port', '587');
    $enc = strtolower(setting('smtp_encryption', 'tls'));
    $user = setting('smtp_username');
    $pass = decrypt_secret(setting('smtp_password'));
    $from = setting('smtp_from_email') ?: setting('public_email');
    $fromName = setting('smtp_from_name', setting('program_name'));

    $target = ($enc === 'ssl' ? 'ssl://' : '') . $host . ':' . $port;
    $socket = @stream_socket_client($target, $errno, $errstr, 15, STREAM_CLIENT_CONNECT);
    if (!$socket) return false;
    stream_set_timeout($socket, 15);

    $read = function() use ($socket): string {
        $data = '';
        while (($line = fgets($socket, 515)) !== false) {
            $data .= $line;
            if (strlen($line) < 4 || $line[3] === ' ') break;
        }
        return $data;
    };
    $send = function(string $cmd, array $ok = [250]) use ($socket, $read): bool {
        fwrite($socket, $cmd . "\r\n");
        $resp = $read();
        $code = (int)substr($resp, 0, 3);
        return in_array($code, $ok, true);
    };

    $banner = $read();
    if ((int)substr($banner, 0, 3) !== 220) { fclose($socket); return false; }
    $domain = $_SERVER['SERVER_NAME'] ?? 'localhost';
    if (!$send('EHLO ' . $domain, [250])) { fclose($socket); return false; }

    if ($enc === 'tls') {
        if (!$send('STARTTLS', [220])) { fclose($socket); return false; }
        if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) { fclose($socket); return false; }
        if (!$send('EHLO ' . $domain, [250])) { fclose($socket); return false; }
    }

    if ($user !== '') {
        if (!$send('AUTH LOGIN', [334]) || !$send(base64_encode($user), [334]) || !$send(base64_encode($pass), [235])) {
            fclose($socket); return false;
        }
    }

    if (!$send('MAIL FROM:<' . $from . '>', [250])) { fclose($socket); return false; }
    if (!$send('RCPT TO:<' . $to . '>', [250, 251])) { fclose($socket); return false; }
    if (!$send('DATA', [354])) { fclose($socket); return false; }

    $headers = [
        'From: ' . sprintf('"%s" <%s>', str_replace(['"', "\r", "\n"], '', $fromName), $from),
        'To: <' . $to . '>',
        'Subject: =?UTF-8?B?' . base64_encode($subject) . '?=',
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8',
        'Content-Transfer-Encoding: 8bit',
        'Date: ' . date(DATE_RFC2822),
        'Message-ID: <' . bin2hex(random_bytes(12)) . '@' . $domain . '>',
    ];
    $body = implode("\r\n", $headers) . "\r\n\r\n" . str_replace("\n.", "\n..", $html) . "\r\n.";
    fwrite($socket, $body . "\r\n");
    $resp = $read();
    $ok = (int)substr($resp, 0, 3) === 250;
    $send('QUIT', [221, 250]);
    fclose($socket);
    return $ok;
}

function email_shell(string $title, string $bodyHtml): string
{
    $name = h(setting('program_name', 'EmpowerME Grant Program'));
    return '<!doctype html><html><body style="margin:0;background:#f3f6f8;font-family:Arial,sans-serif;color:#18313f"><table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f3f6f8;padding:28px 12px"><tr><td align="center"><table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px;background:#fff;border-radius:16px;overflow:hidden"><tr><td style="background:#064f78;padding:26px;color:#fff;font-size:22px;font-weight:700">'.$name.'</td></tr><tr><td style="padding:30px"><h1 style="font-size:24px;margin:0 0 18px">'.h($title).'</h1>'.$bodyHtml.'<p style="margin-top:28px;color:#657783;font-size:13px">This is an automated message from '.$name.'.</p></td></tr></table></td></tr></table></body></html>';
}

function send_application_confirmation(array $app): bool
{
    $trackUrl = app_url('track.php?code=' . urlencode($app['tracking_code']));
    $body = '<p>Hello '.h($app['full_name']).',</p><p>We received your grant application. Keep the tracking code below private; it is used to access your application updates.</p><div style="font-size:26px;font-weight:700;letter-spacing:2px;background:#eef5f8;padding:16px;border-radius:10px;margin:22px 0">'.h($app['tracking_code']).'</div><p><a href="'.h($trackUrl).'" style="display:inline-block;background:#064f78;color:#fff;text-decoration:none;padding:12px 18px;border-radius:8px">Track your application</a></p><p>Submitting an application does not guarantee funding. All applications are subject to eligibility and review.</p>';
    return mail_html($app['email'], 'Your EmpowerME grant application was received', email_shell('Application received', $body));
}

function send_update_email(array $app, array $update): bool
{
    $trackUrl = app_url('track.php?code=' . urlencode($app['tracking_code']));
    $body = '<p>Hello '.h($app['full_name']).',</p><p>There is a new update on your grant application.</p><h2 style="font-size:18px">'.h($update['title']).'</h2><div style="line-height:1.65">'.nl2br(h((string)$update['message'])).'</div>';
    if (!empty($update['link_url']) && safe_return_url($update['link_url'])) {
        $label = $update['link_label'] ?: 'Open link';
        $body .= '<p><a href="'.h($update['link_url']).'" style="display:inline-block;background:#0b79ad;color:#fff;text-decoration:none;padding:11px 16px;border-radius:8px">'.h($label).'</a></p>';
    }
    $body .= '<p><a href="'.h($trackUrl).'" style="display:inline-block;background:#064f78;color:#fff;text-decoration:none;padding:12px 18px;border-radius:8px">View application progress</a></p>';
    return mail_html($app['email'], 'Update to your EmpowerME grant application', email_shell('Application update', $body));
}
