<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

/** @var int $currentUserId */
/** @var PDO $pdo */

// 获取当前用户最近的文件（按创建时间倒序排序，最多展示 20 个）
$stmt = $pdo->prepare("SELECT * FROM files WHERE user_id = ? AND is_deleted = 0 ORDER BY created_at DESC LIMIT 20");
$stmt->execute([$currentUserId]);
$recentFiles = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="fa-solid fa-clock text-primary me-2"></i>最近使用</h4>
        <p class="text-muted small mb-0">这里展示您最近上传或修改的文件记录</p>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
    <?php if (empty($recentFiles)): ?>
        <div class="text-center py-5">
            <i class="fa-solid fa-clock-rotate-left fa-3x text-muted mb-3 opacity-50"></i>
            <h5 class="text-secondary">暂无最近使用记录</h5>
            <p class="text-muted small">您上传的文件将自动显示在这里供您快速访问。</p>
            <a href="<?= SITE_URL ?>/files.php" class="btn btn-primary btn-sm mt-2 rounded-3 px-3">前往文件管理</a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr class="text-secondary small">
                        <th>文件名</th>
                        <th>大小</th>
                        <th>类型</th>
                        <th>上传时间</th>
                        <th class="text-end">操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentFiles as $file): ?>
                        <tr>
                            <td>
                                <i class="fa-solid fa-file-lines text-primary me-2"></i>
                                <span class="fw-semibold text-dark"><?= htmlspecialchars($file['original_name']) ?></span>
                            </td>
                            <td><?= formatBytes($file['file_size']) ?></td>
                            <td>
                                <span class="badge bg-light text-secondary border px-2 py-1">
                                    <?= strtoupper($file['file_extension']) ?>
                                </span>
                            </td>
                            <td class="text-muted small"><?= $file['created_at'] ?></td>
                            <td class="text-end">
                                <a href="<?= SITE_URL ?>/preview.php?id=<?= $file['id'] ?>" class="btn btn-sm btn-light text-secondary" title="预览">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                                <a href="<?= SITE_URL ?>/download.php?id=<?= $file['id'] ?>" class="btn btn-sm btn-light text-primary" title="下载">
                                    <i class="fa-solid fa-download"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>