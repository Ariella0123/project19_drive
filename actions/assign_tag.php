<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

/** @var int $currentUserId */
/** @var PDO $pdo */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken($_POST['csrf_token'] ?? '');

    $fileId = intval($_POST['file_id'] ?? 0);
    $tagId = intval($_POST['tag_id'] ?? 0);

    // 验证文件和标签是否属于当前用户
    $fCheck = $pdo->prepare("SELECT id FROM files WHERE id = ? AND user_id = ?");
    $fCheck->execute([$fileId, $currentUserId]);
    
    $tCheck = $pdo->prepare("SELECT id FROM tags WHERE id = ? AND user_id = ?");
    $tCheck->execute([$tagId, $currentUserId]);

    if ($fCheck->fetch() && $tCheck->fetch()) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO file_tags (file_id, tag_id) VALUES (?, ?)");
        $stmt->execute([$fileId, $tagId]);

        logActivity($currentUserId, 'ASSIGN_TAG', "将文件 ID: $fileId 绑定到标签 ID: $tagId");
        $_SESSION['success'] = "标签绑定成功！";
    }

    header("Location: " . ($_SERVER['HTTP_REFERER'] ?? SITE_URL . '/files.php'));
    exit();
}