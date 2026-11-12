<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

/** @var int $currentUserId */
/** @var PDO $pdo */

// 统计数据
$stmtFiles = $pdo->prepare("SELECT COUNT(*) as file_count, SUM(file_size) as total_size FROM files WHERE user_id = ? AND is_deleted = 0");
$stmtFiles->execute([$currentUserId]);
$fileStats = $stmtFiles->fetch();

$stmtFolders = $pdo->prepare("SELECT COUNT(*) as folder_count FROM folders WHERE user_id = ? AND is_deleted = 0");
$stmtFolders->execute([$currentUserId]);
$folderCount = $stmtFolders->fetchColumn();

// 最近文件
$stmtRecent = $pdo->prepare("SELECT * FROM files WHERE user_id = ? AND is_deleted = 0 ORDER BY created_at DESC LIMIT 5");
$stmtRecent->execute([$currentUserId]);
$recentFiles = $stmtRecent->fetchAll();
?>

<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
            <div class="d-flex align-items-center">
                <div class="rounded-3 bg-primary bg-opacity-10 p-3 text-primary me-3">
                    <i class="fa-solid fa-file-lines fs-3"></i>
                </div>
                <div>
                    <span class="text-muted small">文件总数</span>
                    <h4 class="fw-bold mb-0"><?= number_format($fileStats['file_count'] ?? 0) ?></h4>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
            <div class="d-flex align-items-center">
                <div class="rounded-3 bg-success bg-opacity-10 p-3 text-success me-3">
                    <i class="fa-solid fa-folder fs-3"></i>
                </div>
                <div>
                    <span class="text-muted small">文件夹总数</span>
                    <h4 class="fw-bold mb-0"><?= number_format($folderCount) ?></h4>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
            <div class="d-flex align-items-center">
                <div class="rounded-3 bg-info bg-opacity-10 p-3 text-info me-3">
                    <i class="fa-solid fa-hard-drive fs-3"></i>
                </div>
                <div>
                    <span class="text-muted small">已用存储</span>
                    <h4 class="fw-bold mb-0"><?= formatBytes($fileStats['total_size'] ?? 0) ?></h4>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
    <h5 class="fw-bold mb-3">最近上传</h5>
    <?php if (empty($recentFiles)): ?>
        <p class="text-muted my-3 text-center">暂无上传文件</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr class="text-secondary small">
                        <th>文件名</th>
                        <th>大小</th>
                        <th>创建时间</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentFiles as $rf): ?>
                    <tr>
                        <td>
                            <i class="fa-solid fa-file me-2 text-secondary"></i>
                            <?= htmlspecialchars($rf['original_name']) ?>
                        </td>
                        <td><?= formatBytes($rf['file_size']) ?></td>
                        <td><?= $rf['created_at'] ?></td>
                        <td>
                            <a href="<?= SITE_URL ?>/download.php?id=<?= $rf['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-download"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>