<?php
require_once __DIR__ . '/includes/auth.php';

$fileId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$stmt = $pdo->prepare("SELECT * FROM files WHERE id = ? AND user_id = ? AND is_deleted = 0");
$stmt->execute([$fileId, $currentUserId]);
$file = $stmt->fetch();

if (!$file || !file_exists($file['file_path'])) {
    die("文件不存在或无权访问。");
}

$mime = $file['mime_type'];
$ext = $file['file_extension'];

// 图像与 PDF 直接内联展示
if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'mp4', 'mp3', 'txt'])) {
    header('Content-Type: ' . $mime);
    header('Content-Disposition: inline; filename="' . basename($file['original_name']) . '"');
    readfile($file['file_path']);
    exit();
} else {
    echo "该文件类型不支持直接预览，请<a href='download.php?id={$file['id']}'>下载</a>后查看。";
}