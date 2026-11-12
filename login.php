<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

if (isset($_SESSION['user_id'])) {
    header("Location: " . SITE_URL . "/dashboard.php");
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($email) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            if ($user['status'] === 'active') {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['user_name'] = $user['name'];

                logActivity($user['id'], 'LOGIN', '用户成功登录系统');
                header("Location: " . SITE_URL . "/dashboard.php");
                exit();
            } else {
                $error = "账号已被禁用，请联系管理员。";
            }
        } else {
            $error = "邮箱或密码错误。";
        }
    } else {
        $error = "请填写所有必填字段。";
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>登录 - NovaCloud Storage</title>
    <!-- 1. 引入 Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- 2. 引入 FontAwesome 图标 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- 3. 引入自定义 SaaS 样式 -->
    <link href="<?= SITE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center" style="min-height: 100vh;">

<div class="card shadow border-0 rounded-4 p-4" style="width: 100%; max-width: 400px;">
    <div class="text-center mb-4">
        <h3 class="fw-bold text-primary"><i class="fa-solid fa-cloud-arrow-up me-2"></i>NovaCloud</h3>
        <p class="text-muted small">安全高效的云端存储平台</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger py-2 small rounded-3"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="mb-3">
            <label class="form-label small text-secondary">电子邮箱</label>
            <input type="email" name="email" class="form-control" placeholder="name@example.com" required>
        </div>
        <div class="mb-3">
            <label class="form-label small text-secondary">密码</label>
            <input type="password" name="password" class="form-control" placeholder="••••••••" required>
        </div>
        
        <div class="d-flex justify-content-between align-items-center mb-3">
            <span class="text-muted small">还没有账号？</span>
            <a href="register.php" class="small text-decoration-none fw-semibold">点击注册</a>
        </div>

        <button type="submit" class="btn btn-primary w-100 py-2 rounded-3 fw-semibold">安全登录</button>
    </form>
</div>

<!-- 引入 Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>