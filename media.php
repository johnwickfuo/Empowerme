<?php
require __DIR__ . '/inc/bootstrap.php';
$type = (string)($_GET['type'] ?? '');
$id = (int)($_GET['id'] ?? 0);
if ($type === 'testimonial') {
    $stmt = $db->prepare('SELECT image_stored_name stored_name, image_mime_type mime_type FROM testimonials WHERE id=? LIMIT 1');
} elseif ($type === 'sponsor') {
    $stmt = $db->prepare('SELECT logo_stored_name stored_name, logo_mime_type mime_type FROM sponsors WHERE id=? LIMIT 1');
} else {
    http_response_code(404); exit('Image not found.');
}
$stmt->execute([$id]);
$media = $stmt->fetch();
if (!$media || !$media['stored_name']) { http_response_code(404); exit('Image not found.'); }
$path = MEDIA_PATH . '/' . basename((string)$media['stored_name']);
if (!is_file($path)) { http_response_code(404); exit('Image not found.'); }
$mime = (string)($media['mime_type'] ?: 'application/octet-stream');
if (!str_starts_with($mime, 'image/')) { http_response_code(404); exit('Image not found.'); }
header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($path));
header('Cache-Control: public, max-age=86400');
header('X-Content-Type-Options: nosniff');
readfile($path);
exit;
