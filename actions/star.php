<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

/** @var int $currentUserId */
/** @var PDO $pdo */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. CSRF 安全校验
    verifyCsrfToken($_POST['csrf_token'] ?? '');

    $itemType = $_POST['item_type'] ?? ''; // 'file' 或 'folder'
    $itemId = intval($_POST['item_id'] ?? 0);
    $action = $_POST['action'] ?? '';     // 'star' 或 'unstar'

    $newStatus = ($action === 'star') ? 1 : 0;

    // 2. 根据类型更新对应的数据库表，并通过 user_id 严格隔离权限
    if ($itemType === 'file' && $itemId > 0) {
        $stmt = $pdo->prepare("UPDATE files SET is_starred = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$newStatus, $itemId, $currentUserId]);
        logActivity($currentUserId, $newStatus ? 'STAR_FILE' : 'UNSTAR_FILE', "更新文件星标状态 ID: $itemId 为 $newStatus");
    } elseif ($itemType === 'folder' && $itemId > 0) {
        $stmt = $pdo->prepare("UPDATE folders SET is_starred = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$newStatus, $itemId, $currentUserId]);
        logActivity($currentUserId, $newStatus ? 'STAR_FOLDER' : 'UNSTAR_FOLDER', "更新文件夹星标状态 ID: $itemId 为 $newStatus");
    }

    // 3. 处理完成后返回来源页面（或默认返回文件管理页）
    $redirectUrl = $_SERVER['HTTP_REFERER'] ?? (SITE_URL . '/files.php');
    header("Location: " . $redirectUrl);
    exit();
} else {
    // 非 POST 请求直接重定向
    header("Location: " . SITE_URL . "/files.php");
    exit();
}