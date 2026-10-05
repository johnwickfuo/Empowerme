<?php
declare(strict_types=1);

function h(?string $value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }

function app_url(string $path = ''): string
{
    global $config;
    $base = rtrim((string)($config['app_url'] ?? ''), '/');
    return $base . '/' . ltrim($path, '/');
}

function redirect(string $path): never
{
    header('Location: ' . (preg_match('~^https?://~i', $path) ? $path : app_url($path)));
    exit;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . h(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $sent = (string)($_POST['csrf'] ?? '');
    if (!$sent || !hash_equals(csrf_token(), $sent)) {
        http_response_code(419);
        exit('Session expired. Please go back, refresh the page, and try again.');
    }
}

function setting(string $key, string $default = ''): string
{
    global $db;
    static $cache = [];
    if (array_key_exists($key, $cache)) return $cache[$key];
    $stmt = $db->prepare('SELECT setting_value FROM settings WHERE setting_key = ? LIMIT 1');
    $stmt->execute([$key]);
    $val = $stmt->fetchColumn();
    return $cache[$key] = ($val === false ? $default : (string)$val);
}

function set_setting(string $key, string $value): void
{
    global $db;
    $stmt = $db->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
    $stmt->execute([$key, $value]);
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function get_flashes(): array
{
    $items = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $items;
}

function locations(): array
{
    return ['usa' => 'USA', 'uk' => 'UK', 'canada' => 'Canada', 'australia' => 'Australia', 'others' => 'Others'];
}

function location_label(string $key): string
{
    $all = locations();
    return $all[$key] ?? ucfirst($key);
}

function generate_tracking_code(PDO $db, int $length = 16): string
{
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    do {
        $code = '';
        for ($i = 0; $i < $length; $i++) $code .= $alphabet[random_int(0, strlen($alphabet)-1)];
        $stmt = $db->prepare('SELECT 1 FROM applications WHERE tracking_code = ?');
        $stmt->execute([$code]);
    } while ($stmt->fetchColumn());
    return $code;
}

function slug_key(string $label): string
{
    $key = strtolower(trim($label));
    $key = preg_replace('/[^a-z0-9]+/', '_', $key) ?? '';
    $key = trim($key, '_');
    return $key ?: 'field_' . substr(bin2hex(random_bytes(4)), 0, 8);
}

function active_form(PDO $db, string $location): ?array
{
    $stmt = $db->prepare('SELECT * FROM forms WHERE location = ? AND is_active = 1 ORDER BY updated_at DESC, id DESC LIMIT 1');
    $stmt->execute([$location]);
    $form = $stmt->fetch();
    if (!$form) return null;
    $fields = $db->prepare('SELECT * FROM form_fields WHERE form_id = ? ORDER BY sort_order, id');
    $fields->execute([$form['id']]);
    $form['fields'] = $fields->fetchAll();
    return $form;
}

function form_snapshot(array $form): array
{
    $fields = [];
    foreach ($form['fields'] ?? [] as $f) {
        $fields[] = [
            'field_key' => $f['field_key'], 'label' => $f['label'], 'field_type' => $f['field_type'],
            'placeholder' => $f['placeholder'], 'help_text' => $f['help_text'],
            'options' => $f['options_json'] ? json_decode($f['options_json'], true) : [],
            'condition' => $f['condition_json'] ? json_decode($f['condition_json'], true) : null,
            'is_required' => (bool)$f['is_required'], 'sort_order' => (int)$f['sort_order'], 'id' => (int)$f['id']
        ];
    }
    return [
        'id' => (int)$form['id'], 'title' => $form['title'], 'description' => $form['description'],
        'location' => $form['location'], 'version' => (int)$form['version'], 'fields' => $fields,
        'default_fields' => [
            ['field_key'=>'full_name','label'=>'Full Name','field_type'=>'text','is_required'=>true],
            ['field_key'=>'email','label'=>'Email Address','field_type'=>'email','is_required'=>true],
        ],
    ];
}

function condition_met(?array $condition, array $input): bool
{
    if (!$condition || empty($condition['field'])) return true;
    $value = $input[$condition['field']] ?? null;
    $operator = $condition['operator'] ?? 'equals';
    $expected = (string)($condition['value'] ?? '');
    if (is_array($value)) $value = implode(',', $value);
    $value = (string)$value;
    return match ($operator) {
        'not_equals' => $value !== $expected,
        'not_empty' => trim($value) !== '',
        'empty' => trim($value) === '',
        default => $value === $expected,
    };
}

function store_uploaded_file(PDO $db, int $applicationId, ?int $updateId, ?string $fieldKey, array $file): ?int
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) throw new RuntimeException('A file upload failed. Please try again.');
    if ((int)$file['size'] > MAX_UPLOAD_BYTES) throw new RuntimeException('Each uploaded file must be 20 MB or smaller.');
    if (!is_uploaded_file($file['tmp_name'])) throw new RuntimeException('Invalid uploaded file.');
    if (!is_dir(UPLOAD_PATH) && !mkdir(UPLOAD_PATH, 0770, true)) throw new RuntimeException('Upload storage is not writable.');

    $stored = bin2hex(random_bytes(24)) . '.dat';
    $target = UPLOAD_PATH . '/' . $stored;
    if (!move_uploaded_file($file['tmp_name'], $target)) throw new RuntimeException('Could not store uploaded file.');
    @chmod($target, 0640);

    $mime = function_exists('mime_content_type') ? (mime_content_type($target) ?: 'application/octet-stream') : 'application/octet-stream';
    $stmt = $db->prepare('INSERT INTO application_files (application_id, update_id, field_key, original_name, stored_name, mime_type, file_size) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$applicationId, $updateId, $fieldKey, basename((string)$file['name']), $stored, $mime, (int)$file['size']]);
    return (int)$db->lastInsertId();
}

function normalized_uploads(array $files): array
{
    $out = [];
    if (!isset($files['name'])) return $out;
    if (!is_array($files['name'])) return [$files];
    foreach ($files['name'] as $i => $name) {
        $out[] = [
            'name' => $name,
            'type' => $files['type'][$i] ?? '',
            'tmp_name' => $files['tmp_name'][$i] ?? '',
            'error' => $files['error'][$i] ?? UPLOAD_ERR_NO_FILE,
            'size' => $files['size'][$i] ?? 0,
        ];
    }
    return $out;
}

function encrypt_secret(string $plaintext): string
{
    global $config;
    if ($plaintext === '') return '';
    $key = hash('sha256', (string)($config['app_key'] ?? ''), true);
    $iv = random_bytes(12);
    $tag = '';
    $cipher = openssl_encrypt($plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
    if ($cipher === false) return '';
    return 'enc:' . base64_encode($iv . $tag . $cipher);
}

function decrypt_secret(string $encoded): string
{
    global $config;
    if (!str_starts_with($encoded, 'enc:')) return $encoded;
    $raw = base64_decode(substr($encoded, 4), true);
    if ($raw === false || strlen($raw) < 28) return '';
    $iv = substr($raw, 0, 12);
    $tag = substr($raw, 12, 16);
    $cipher = substr($raw, 28);
    $key = hash('sha256', (string)($config['app_key'] ?? ''), true);
    $plain = openssl_decrypt($cipher, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
    return $plain === false ? '' : $plain;
}

function safe_return_url(string $url): bool
{
    return (bool)filter_var($url, FILTER_VALIDATE_URL) && preg_match('~^https://~i', $url);
}

function store_public_image(array $file): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        throw new RuntimeException('Choose an image to upload.');
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('The image upload failed. Please try again.');
    }
    if ((int)($file['size'] ?? 0) > MAX_UPLOAD_BYTES) {
        throw new RuntimeException('The image must be 20 MB or smaller.');
    }
    if (!is_uploaded_file((string)$file['tmp_name'])) {
        throw new RuntimeException('Invalid image upload.');
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = (string)$finfo->file((string)$file['tmp_name']);
    $allowed = ['image/jpeg','image/png','image/webp','image/gif'];
    if (!in_array($mime, $allowed, true)) {
        throw new RuntimeException('Use a JPG, PNG, WEBP, or GIF image.');
    }
    if (!is_dir(MEDIA_PATH) && !mkdir(MEDIA_PATH, 0770, true) && !is_dir(MEDIA_PATH)) {
        throw new RuntimeException('Media storage is not writable.');
    }
    $stored = bin2hex(random_bytes(24)) . '.dat';
    $target = MEDIA_PATH . '/' . $stored;
    if (!move_uploaded_file((string)$file['tmp_name'], $target)) {
        throw new RuntimeException('Could not store the uploaded image.');
    }
    @chmod($target, 0640);
    return [
        'original_name' => basename((string)($file['name'] ?? 'image')),
        'stored_name' => $stored,
        'mime_type' => $mime,
    ];
}

function delete_media_file(?string $storedName): void
{
    if (!$storedName) return;
    $safe = basename($storedName);
    $path = MEDIA_PATH . '/' . $safe;
    if (is_file($path)) @unlink($path);
}
