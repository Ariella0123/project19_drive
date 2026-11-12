<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$token = trim($_GET['token'] ?? '');
$stmt = $pdo->prepare("SELECT * FROM shares WHERE token = ? AND is_active = 1");
$stmt->execute([$token]);
$share = $stmt->fetch();

if (!$share) {
    die("分享链接不存在或已失效。");
}

// 获取文件信息
$fileStmt = $pdo->prepare("SELECT * FROM files WHERE id = ?");
$fileStmt->execute([$share['resource_id']]);
$file = $fileStmt->fetch();

// 密码校验逻辑
$unlocked = empty($share['password']);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    if (password_verify($_POST['password'], $share['password'])) {
        $unlocked = true;
    } else {
        $error = "密码错误！";
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>文件分享 - NovaCloud</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center" style="min-height: 100vh;">
<div class="card border-0 shadow rounded-4 p-4" style="max-width: 450px; width:100%;">
    <h4 class="fw-bold mb-3"><i class="fa-solid fa-share-nodes text-primary me-2"></i>NovaCloud 文件分享</h4>
    <?php if (!$unlocked): ?>
        <form method="POST">
            <p class="text-secondary small">该文件已被受密码保护，请输入密码访问：</p>
            <?php if (isset($error)): ?><div class="alert alert-danger p-2 small"><?= $error ?></div><?php endif; ?>
            <input type="password" name="password" class="form-control mb-3" placeholder="密码" required>
            <button type="submit" class="btn btn-primary w-100">提取文件</button>
        </form>
    <?php else: ?>
        <div class="text-center py-3">
            <h5><?= htmlspecialchars($file['original_name']) ?></h5>
            <p class="text-muted small">文件大小：<?= formatBytes($file['file_size']) ?></p>
            <a href="download.php?id=<?= $file['id'] ?>" class="btn btn-primary w-100">立即下载</a>
        </div>
    <?php endif; ?>
</div>
</body>
</html>