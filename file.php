<?php
require __DIR__.'/inc/bootstrap.php';
$id=(int)($_GET['id']??0); $code=strtoupper(trim((string)($_GET['code']??'')));
$stmt=$db->prepare('SELECT f.* FROM application_files f JOIN applications a ON a.id=f.application_id WHERE f.id=? AND a.tracking_code=? LIMIT 1'); $stmt->execute([$id,$code]); $file=$stmt->fetch();
if(!$file){http_response_code(404);exit('File not found.');}
$path=UPLOAD_PATH.'/'.$file['stored_name']; if(!is_file($path)){http_response_code(404);exit('File not found.');}
header('Content-Type: '.($file['mime_type']?:'application/octet-stream')); header('Content-Length: '.filesize($path)); header('Content-Disposition: attachment; filename="'.str_replace(['"',"\r","\n"],'',basename($file['original_name'])).'"'); header('X-Content-Type-Options: nosniff'); readfile($path); exit;
