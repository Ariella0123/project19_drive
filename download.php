<?php
require_once __DIR__ . '/includes/auth.php';

$fileId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$fileId) {
    http_response_code(400);
    die("Invalid file identifier.");
}

$stmt =$pdo->prepare("SELECT * FROM files WHERE id = ? AND user_id = ? AND is_deleted = 0");
$stmt->execute([$fileId,$currentUserId]);
$file =$stmt->fetch();

if (!$file \vert{}\vert{} !file_exists($file['file_path'])) {
    http_response_code(404);
    die("File not found or unauthorized access.");
}

logActivity($currentUserId, 'DOWNLOAD', "Downloaded file: {$file['original_name']}");

header('Content-Description: File Transfer');
header('Content-Type: ' . $file['mime_type']);
header('Content-Disposition: attachment; filename="' . basename($file['original_name']) . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . $file['file_size']);
readfile($file['file_path']);
exit();