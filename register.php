<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

// 如果用户已登录，直接重定向到控制台
if (isset($_SESSION['user_id'])) {
    header("Location: " . SITE_URL . "/dashboard.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($name) || empty($email) || empty($password) || empty($confirmPassword)) {
        $error = "请填写所有必填字段。";
    } elseif ($password !== $confirmPassword) {
        $error = "两次输入的密码不一致。";
    } elseif (strlen($password) < 6) {
        $error = "密码长度不能少于 6 位。";
    } else {
        // 检查邮箱是否已被注册
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = "该电子邮箱已被注册。";
        } else {
            // 获取系统默认存储配额 (settings 表中获取，默认 10GB = 10737418240 字节)
            $quotaStmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'default_storage_quota'");
            $quotaStmt->execute();
            $defaultQuota = $quotaStmt->fetchColumn() ?: 10737418240;

            // 密码加密
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // 插入新用户
            $insertStmt = $pdo->prepare("INSERT INTO users (name, email, password, role, status, storage_quota, storage_used) VALUES (?, ?, ?, 'user', 'active', ?, 0)");
            
            if ($insertStmt->execute([$name, $email, $hashedPassword, $defaultQuota])) {
                $newUserId = $pdo->lastInsertId();
                logActivity($newUserId, 'REGISTER', '新用户注册成功');
                
                $success = "注册成功！请前往登录页面进行登录。";
            } else {
                $error = "注册失败，请稍后重试。";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>注册 - NovaCloud Storage</title>
    <!-- 引入 Bootstrap 5 和 FontAwesome 样式 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="<?= SITE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center" style="min-height: 100vh;">

<div class="card shadow border-0 rounded-4 p-4" style="width: 100%; max-width: 420px;">
    <div class="text-center mb-4">
        <h3 class="fw-bold text-primary"><i class="fa-solid fa-cloud-arrow-up me-2"></i>NovaCloud</h3>
        <p class="text-muted small">创建您的云端存储账号</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger py-2 small rounded-3"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success py-2 small rounded-3"><?= htmlspecialchars($success) ?></div>
        <div class="mt-2">
            <a href="login.php" class="btn btn-primary w-100 py-2 rounded-3">立即登录</a>
        </div>
    <?php else: ?>
        <form method="POST">
            <div class="mb-3">
                <label class="form-label small text-secondary">用户姓名</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-user text-muted"></i></span>
                    <input type="text" name="name" class="form-control bg-light border-start-0" placeholder="请输入您的姓名" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label small text-secondary">电子邮箱</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-envelope text-muted"></i></span>
                    <input type="email" name="email" class="form-control bg-light border-start-0" placeholder="name@example.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label small text-secondary">密码 (不少于 6 位)</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-lock text-muted"></i></span>
                    <input type="password" name="password" class="form-control bg-light border-start-0" placeholder="••••••••" required>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label small text-secondary">确认密码</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-lock text-muted"></i></span>
                    <input type="password" name="confirm_password" class="form-control bg-light border-start-0" placeholder="••••••••" required>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 py-2 rounded-3 fw-semibold">立即注册</button>
        </form>

        <div class="text-center mt-3">
            <span class="text-muted small">已有账号？</span>
            <a href="login.php" class="small text-decoration-none fw-semibold">直接登录</a>
        </div>
    <?php endif; ?>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>