<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/admin_auth.php';

// 系统总数据统计
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalFiles = $pdo->query("SELECT COUNT(*) FROM files")->fetchColumn();
$totalStorage = $pdo->query("SELECT SUM(file_size) FROM files")->fetchColumn();
?>

<h4 class="fw-bold mb-4">系统管理控制台</h4>
<div class="row g-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm p-3 bg-white rounded-4">
            <span class="text-muted small">注册用户数</span>
            <h3 class="fw-bold mb-0"><?= $totalUsers ?></h3>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm p-3 bg-white rounded-4">
            <span class="text-muted small">系统全量文件数</span>
            <h3 class="fw-bold mb-0"><?= $totalFiles ?></h3>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm p-3 bg-white rounded-4">
            <span class="text-muted small">占用存储空间</span>
            <h3 class="fw-bold mb-0"><?= formatBytes($totalStorage ?? 0) ?></h3>
        </div>
    </div>
</div>

<div class="mt-4">
    <a href="users.php" class="btn btn-outline-primary">用户管理</a>
    <a href="logs.php" class="btn btn-outline-secondary">审计日志</a>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>