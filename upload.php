<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';

/** @var int $currentUserId */
/** @var PDO $pdo */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken($_POST['csrf_token'] ?? '');

    $folderId = !empty($_POST['folder_id']) ? intval($_POST['folder_id']) : null;
    
    // 验证目标文件夹归属
    if ($folderId) {
        $fCheck = $pdo->prepare("SELECT id FROM folders WHERE id = ? AND user_id = ? AND is_deleted = 0");
        $fCheck->execute([$folderId, $currentUserId]);
        if (!$fCheck->fetch()) {
            $_SESSION['error'] = "Invalid destination folder.";
            header("Location: " . SITE_URL . "/files.php");
            exit();
        }
    }

    if (!isset($_FILES['files'])) {
        $_SESSION['error'] = "No files uploaded.";
        header("Location: " . SITE_URL . "/files.php");
        exit();
    }

    // 获取系统允许的文件扩展名
    $settingStmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'allowed_extensions'");
    $settingStmt->execute();
    $allowedExts = explode(',', strtolower(str_replace(' ', '', $settingStmt->fetch()['setting_value'] ?? '')));

    // 获取用户配额与已用空间
    $uStmt = $pdo->prepare("SELECT storage_quota, storage_used FROM users WHERE id = ?");
    $uStmt->execute([$currentUserId]);
    $userData = $uStmt->fetch();

    $files = $_FILES['files'];
    $fileCount = count($files['name']);
    $uploadedCount = 0;

    for ($i = 0; $i < $fileCount; $i++) {
        if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;

        $originalName = $files['name'][$i];
        $fileSize = $files['size'][$i];
        $tmpName = $files['tmp_name'][$i];
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        // 验证扩展名（已修正逻辑运算符）
        if (!in_array($ext, $allowedExts) || in_array($ext, ['php', 'phtml', 'exe', 'sh', 'bat'])) {
            continue;
        }

        // 验证存储配额
        if (($userData['storage_used'] + $fileSize) > $userData['storage_quota']) {
            $_SESSION['error'] = "Storage quota exceeded!";
            break;
        }

        // 生成安全唯一的存储文件名
        $storedName = bin2hex(random_bytes(16)) . '.' . $ext;
        $subDir = UPLOAD_PATH . $currentUserId . '/';
        if (!is_dir($subDir)) {
            mkdir($subDir, 0755, true);
        }
        $destination = $subDir . $storedName;

        if (move_uploaded_file($tmpName, $destination)) {
            $mimeType = mime_content_type($destination);

            $stmt = $pdo->prepare("INSERT INTO files (user_id, folder_id, original_name, stored_name, file_path, mime_type, file_extension, file_size) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$currentUserId, $folderId, $originalName, $storedName, $destination, $mimeType, $ext, $fileSize]);
            
            $fileId = $pdo->lastInsertId();

            // 写入初始版本控制记录
            $vStmt = $pdo->prepare("INSERT INTO file_versions (file_id, version_number, stored_name, file_path, file_size) VALUES (?, 1, ?, ?, ?)");
            $vStmt->execute([$fileId, $storedName, $destination, $fileSize]);

            $userData['storage_used'] += $fileSize;
            $uploadedCount++;
            logActivity($currentUserId, 'UPLOAD', "Uploaded file: $originalName");
        }
    }

    recalculateUserStorage($currentUserId);
    $_SESSION['success'] = "$uploadedCount file(s) uploaded successfully.";
    header("Location: " . SITE_URL . "/files.php" . ($folderId ? "?folder_id=$folderId" : ""));
    exit();
}