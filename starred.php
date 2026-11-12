<?php
require_once __DIR__ . '/includes/auth.php'; // 包含身份验证和配置文件

/** @var int $currentUserId */
/** @var PDO $pdo */

// 1. 优先处理 POST 请求（必须在任何 HTML 输出之前进行）
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken($_POST['csrf_token'] ?? '');
    
    $itemType = $_POST['item_type'] ?? ''; // 'file' 或 'folder'
    $itemId = intval($_POST['item_id'] ?? 0);
    $action = $_POST['action'] ?? '';     // 'star' 或 'unstar'

    $newStatus = ($action === 'star') ? 1 : 0;

    if ($itemType === 'file' && $itemId > 0) {
        $stmt = $pdo->prepare("UPDATE files SET is_starred = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$newStatus, $itemId, $currentUserId]);
        logActivity($currentUserId, $newStatus ? 'STAR_FILE' : 'UNSTAR_FILE', "更新文件星标状态 ID: $itemId");
    } elseif ($itemType === 'folder' && $itemId > 0) {
        $stmt = $pdo->prepare("UPDATE folders SET is_starred = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$newStatus, $itemId, $currentUserId]);
        logActivity($currentUserId, $newStatus ? 'STAR_FOLDER' : 'UNSTAR_FOLDER', "更新文件夹星标状态 ID: $itemId");
    }

    header("Location: " . SITE_URL . "/starred.php");
    exit();
}

// 2. 接着才引入页面头部和侧边栏（开始输出 HTML）
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

// 查询当前用户所有加星标且未被删除的文件夹
$stmtFolders = $pdo->prepare("SELECT * FROM folders WHERE user_id = ? AND is_starred = 1 AND is_deleted = 0 ORDER BY name ASC");
$stmtFolders->execute([$currentUserId]);
$starredFolders = $stmtFolders->fetchAll();

// 查询当前用户所有加星标且未被删除的文件
$stmtFiles = $pdo->prepare("SELECT * FROM files WHERE user_id = ? AND is_starred = 1 AND is_deleted = 0 ORDER BY updated_at DESC");
$stmtFiles->execute([$currentUserId]);
$starredFiles = $stmtFiles->fetchAll();

$isEmpty = empty($starredFolders) && empty($starredFiles);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="fa-solid fa-star text-warning me-2"></i>星标文件与文件夹</h4>
        <p class="text-muted small mb-0">这里集中展示您标记为星标的重要文件及文件夹，方便快速查找</p>
    </div>
</div>

<?php if ($isEmpty): ?>
    <div class="card border-0 shadow-sm rounded-4 p-5 bg-white text-center">
        <div class="py-4">
            <i class="fa-regular fa-star fa-4x text-warning mb-3 opacity-50"></i>
            <h5 class="text-secondary fw-semibold">暂无星标内容</h5>
            <p class="text-muted small">您可以在“全部文件”列表中为重要文件或文件夹添加星标标记。</p>
            <a href="<?= SITE_URL ?>/files.php" class="btn btn-primary btn-sm rounded-3 px-3">前往文件列表</a>
        </div>
    </div>
<?php else: ?>

    <!-- 星标文件夹区块 -->
    <?php if (!empty($starredFolders)): ?>
        <h6 class="fw-bold text-secondary mb-3"><i class="fa-solid fa-folder me-2"></i>星标文件夹</h6>
        <div class="row g-3 mb-4">
            <?php foreach ($starredFolders as $folder): ?>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm rounded-3 p-3 bg-white d-flex flex-row align-items-center justify-content-between">
                        <a href="<?= SITE_URL ?>/files.php?folder_id=<?= $folder['id'] ?>" class="text-decoration-none text-dark fw-semibold text-truncate me-2">
                            <i class="fa-solid fa-folder text-warning me-2 fs-5"></i>
                            <?= htmlspecialchars($folder['name']) ?>
                        </a>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                            <input type="hidden" name="item_type" value="folder">
                            <input type="hidden" name="item_id" value="<?= $folder['id'] ?>">
                            <input type="hidden" name="action" value="unstar">
                            <button type="submit" class="btn btn-link text-warning p-0 border-0" title="取消星标">
                                <i class="fa-solid fa-star fs-5"></i>
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- 星标文件区块 -->
    <?php if (!empty($starredFiles)): ?>
        <h6 class="fw-bold text-secondary mb-3"><i class="fa-solid fa-file me-2"></i>星标文件</h6>
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr class="text-secondary small">
                            <th>文件名</th>
                            <th>大小</th>
                            <th>类型</th>
                            <th>修改时间</th>
                            <th class="text-end">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($starredFiles as $file): ?>
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
                                <td class="text-muted small"><?= $file['updated_at'] ?></td>
                                <td class="text-end">
                                    <form method="POST" class="d-inline me-2">
                                        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                        <input type="hidden" name="item_type" value="file">
                                        <input type="hidden" name="item_id" value="<?= $file['id'] ?>">
                                        <input type="hidden" name="action" value="unstar">
                                        <button type="submit" class="btn btn-sm btn-link text-warning p-0 border-0 align-middle" title="取消星标">
                                            <i class="fa-solid fa-star fs-6"></i>
                                        </button>
                                    </form>
                                    <a href="<?= SITE_URL ?>/preview.php?id=<?= $file['id'] ?>" class="btn btn-sm btn-light text-secondary me-1" title="预览">
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
        </div>
    <?php endif; ?>

<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>