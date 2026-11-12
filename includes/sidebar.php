<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$userId = $_SESSION['user_id'];

// 获取用户已用空间与总配额
$uStmt = $pdo->prepare("SELECT name, email, role, storage_quota, storage_used FROM users WHERE id = ?");
$uStmt->execute([$userId]);
$currentUserInfo = $uStmt->fetch();

$usedBytes = $currentUserInfo['storage_used'] ?? 0;
$quotaBytes = $currentUserInfo['storage_quota'] ?? 10737418240;
$percentUsed = $quotaBytes > 0 ? min(100, round(($usedBytes / $quotaBytes) * 100, 1)) : 0;
?>
<div class="bg-white border-end" id="sidebar-wrapper" style="min-width: 250px; max-width: 250px;">
    <div class="sidebar-heading border-bottom bg-light p-3 fw-bold text-primary d-flex align-items-center">
        <i class="fa-solid font-cloud fa-cloud-arrow-up me-2 fs-4"></i>
        <span><?= htmlspecialchars($appName ?? 'NovaCloud') ?></span>
    </div>
    <div class="list-group list-group-flush my-2">
        <a href="<?= SITE_URL ?>/dashboard.php" class="list-group-item list-group-item-action p-3 border-0 <?= $currentPage == 'dashboard.php' ? 'active rounded-3 mx-2 fw-semibold' : '' ?>">
            <i class="fa-solid fa-gauge me-3"></i>控制台
        </a>
        <a href="<?= SITE_URL ?>/files.php" class="list-group-item list-group-item-action p-3 border-0 <?= $currentPage == 'files.php' ? 'active rounded-3 mx-2 fw-semibold' : '' ?>">
            <i class="fa-solid fa-folder-open me-3"></i>全部文件
        </a>
        <a href="<?= SITE_URL ?>/recent.php" class="list-group-item list-group-item-action p-3 border-0 <?= $currentPage == 'recent.php' ? 'active rounded-3 mx-2 fw-semibold' : '' ?>">
            <i class="fa-solid fa-clock me-3"></i>最近使用
        </a>
        <a href="<?= SITE_URL ?>/starred.php" class="list-group-item list-group-item-action p-3 border-0 <?= $currentPage == 'starred.php' ? 'active rounded-3 mx-2 fw-semibold' : '' ?>">
            <i class="fa-solid fa-star me-3"></i>星标文件
        </a>
        <a href="<?= SITE_URL ?>/tags.php" class="list-group-item list-group-item-action p-3 border-0 <?= $currentPage == 'tags.php' ? 'active rounded-3 mx-2 fw-semibold' : '' ?>">
            <i class="fa-solid fa-tags me-3"></i>标签管理
        </a>
        <a href="<?= SITE_URL ?>/share.php" class="list-group-item list-group-item-action p-3 border-0 <?= $currentPage == 'share.php' ? 'active rounded-3 mx-2 fw-semibold' : '' ?>">
            <i class="fa-solid fa-share-nodes me-3"></i>我的分享
        </a>
        <a href="<?= SITE_URL ?>/trash.php" class="list-group-item list-group-item-action p-3 border-0 <?= $currentPage == 'trash.php' ? 'active rounded-3 mx-2 fw-semibold' : '' ?>">
            <i class="fa-solid fa-trash me-3"></i>回收站
        </a>

        <?php if ($currentUserInfo['role'] === 'admin'): ?>
            <hr class="my-3">
            <div class="px-3 text-muted small fw-bold text-uppercase">管理后台</div>
            <a href="<?= SITE_URL ?>/admin/index.php" class="list-group-item list-group-item-action p-3 border-0">
                <i class="fa-solid fa-user-shield me-3"></i>系统管理
            </a>
        <?php endif; ?>
    </div>

    <!-- 容量展示 -->
    <div class="mt-auto p-3 border-top bg-light mx-2 rounded-3 mb-3">
        <div class="d-flex justify-content-between small text-muted mb-1">
            <span>已用空间</span>
            <span><?= $percentUsed ?>%</span>
        </div>
        <div class="progress mb-2" style="height: 6px;">
            <div class="progress-bar <?= $percentUsed > 90 ? 'bg-danger' : 'bg-primary' ?>" role="progressbar" style="width: <?= $percentUsed ?>%"></div>
        </div>
        <div class="small text-muted text-center">
            <?= formatBytes($usedBytes) ?> / <?= formatBytes($quotaBytes) ?>
        </div>
    </div>
</div>

<div id="page-content-wrapper" class="w-100 bg-light">
    <!-- Top Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom px-4 py-2">
        <div class="d-flex align-items-center w-100">
            <form action="<?= SITE_URL ?>/search.php" method="GET" class="d-flex flex-grow-1 me-4" style="max-width: 500px;">
                <div class="input-group">
                    <span class="input-group-text bg-light border-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                    <input class="form-control bg-light border-0" type="search" name="q" placeholder="搜索文件或文件夹..." required>
                </div>
            </form>
            <div class="ms-auto d-flex align-items-center">
                <div class="dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center text-dark fw-semibold" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fa-solid fa-circle-user fs-3 text-secondary me-2"></i>
                        <span><?= htmlspecialchars($currentUserInfo['name']) ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2">
                        <li><a class="dropdown-menu-item dropdown-item" href="<?= SITE_URL ?>/profile.php"><i class="fa-solid fa-user me-2"></i>个人中心</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-menu-item dropdown-item text-danger" href="<?= SITE_URL ?>/logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i>退出登录</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>
    <div class="container-fluid p-4">