<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

/** @var int $currentUserId */
/** @var PDO $pdo */

$folderId = filter_input(INPUT_GET, 'folder_id', FILTER_VALIDATE_INT);

// 创建文件夹逻辑
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_folder') {
    verifyCsrfToken($_POST['csrf_token'] ?? '');
    $folderName = trim($_POST['folder_name'] ?? '');
    if (!empty($folderName)) {
        $stmt = $pdo->prepare("INSERT INTO folders (user_id, parent_id, name) VALUES (?, ?, ?)");
        $stmt->execute([$currentUserId, $folderId, $folderName]);
        logActivity($currentUserId, 'CREATE_FOLDER', "新建文件夹: $folderName");
        header("Location: " . SITE_URL . "/files.php" . ($folderId ? "?folder_id=$folderId" : ""));
        exit();
    }
}

// 获取子文件夹列表
$stmtF = $pdo->prepare("SELECT * FROM folders WHERE user_id = ? AND parent_id " . ($folderId ? "= ?" : "IS NULL") . " AND is_deleted = 0 ORDER BY name ASC");
$stmtF->execute($folderId ? [$currentUserId, $folderId] : [$currentUserId]);
$folders = $stmtF->fetchAll();

// 获取文件列表
$stmtFiles = $pdo->prepare("SELECT * FROM files WHERE user_id = ? AND folder_id " . ($folderId ? "= ?" : "IS NULL") . " AND is_deleted = 0 ORDER BY updated_at DESC");
$stmtFiles->execute($folderId ? [$currentUserId, $folderId] : [$currentUserId]);
$files = $stmtFiles->fetchAll();

// 获取当前用户的所有标签（用于文件绑定弹窗）
$tagStmt = $pdo->prepare("SELECT * FROM tags WHERE user_id = ? ORDER BY name ASC");
$tagStmt->execute([$currentUserId]);
$allTags = $tagStmt->fetchAll();

// 面包屑目录名称
$currentFolderName = "根目录";
if ($folderId) {
    $fnStmt = $pdo->prepare("SELECT name FROM folders WHERE id = ? AND user_id = ?");
    $fnStmt->execute([$folderId, $currentUserId]);
    $fnRes = $fnStmt->fetch();
    if ($fnRes) $currentFolderName = $fnRes['name'];
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="fa-solid fa-folder-open text-primary me-2"></i>文件管理器</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item"><a href="<?= SITE_URL ?>/files.php" class="text-decoration-none">根目录</a></li>
                <?php if ($folderId): ?>
                    <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($currentFolderName) ?></li>
                <?php endif; ?>
            </ol>
        </nav>
    </div>
    <div class="d-flex gap-2">
        <?php if ($folderId): ?>
            <a href="<?= SITE_URL ?>/files.php" class="btn btn-outline-secondary rounded-3 btn-sm d-flex align-items-center">
                <i class="fa-solid fa-arrow-left me-1"></i> 返回上级
            </a>
        <?php endif; ?>
        <button class="btn btn-outline-primary rounded-3 btn-sm" data-bs-toggle="modal" data-bs-target="#newFolderModal">
            <i class="fa-solid fa-folder-plus me-1"></i> 新建文件夹
        </button>
        <button class="btn btn-primary rounded-3 btn-sm" data-bs-toggle="modal" data-bs-target="#uploadModal">
            <i class="fa-solid fa-upload me-1"></i> 上传文件
        </button>
    </div>
</div>

<!-- 文件夹网格展示 -->
<?php if (!empty($folders)): ?>
    <h6 class="fw-bold text-secondary mb-3 small"><i class="fa-solid fa-folder me-2"></i>文件夹</h6>
    <div class="row g-3 mb-4">
        <?php foreach ($folders as $f): ?>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm rounded-3 p-3 bg-white d-flex flex-row align-items-center justify-content-between">
                    <a href="<?= SITE_URL ?>/files.php?folder_id=<?= $f['id'] ?>" class="text-decoration-none text-dark fw-semibold text-truncate me-2">
                        <i class="fa-solid fa-folder text-warning me-2 fs-5"></i> <?= htmlspecialchars($f['name']) ?>
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- 文件列表表格 -->
<h6 class="fw-bold text-secondary mb-3 small"><i class="fa-solid fa-file-lines me-2"></i>文件</h6>
<div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
    <?php if (empty($files) && empty($folders)): ?>
        <div class="text-center py-5">
            <i class="fa-solid fa-cloud-arrow-up fa-3x text-muted mb-3 opacity-50"></i>
            <h5 class="text-secondary">当前目录为空</h5>
            <p class="text-muted small">点击右上角“上传文件”或“新建文件夹”开始使用。</p>
        </div>
    <?php elseif (empty($files)): ?>
        <p class="text-muted text-center py-3 mb-0">该文件夹下暂无文件。</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr class="text-secondary small">
                        <th>文件名</th>
                        <th>大小</th>
                        <th>更新时间</th>
                        <th class="text-end">操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($files as $file): ?>
                        <tr>
                            <td>
                                <i class="fa-solid fa-file-lines text-primary me-2"></i>
                                <span class="fw-semibold text-dark"><?= htmlspecialchars($file['original_name']) ?></span>
                            </td>
                            <td><?= formatBytes($file['file_size']) ?></td>
                            <td class="text-muted small"><?= $file['updated_at'] ?></td>
                            <td class="text-end">
                                <!-- 1. 标星切换 -->
                                <form method="POST" action="<?= SITE_URL ?>/actions/star.php" class="d-inline">
                                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                    <input type="hidden" name="item_type" value="file">
                                    <input type="hidden" name="item_id" value="<?= $file['id'] ?>">
                                    <input type="hidden" name="action" value="<?= $file['is_starred'] ? 'unstar' : 'star' ?>">
                                    <button type="submit" class="btn btn-sm btn-light text-<?= $file['is_starred'] ? 'warning' : 'secondary' ?>" title="<?= $file['is_starred'] ? '取消星标' : '添加星标' ?>">
                                        <i class="fa-solid fa-star"></i>
                                    </button>
                                </form>

                                <!-- 2. 绑定标签按钮（支持加入“冰红茶”等标签） -->
                                <button type="button" class="btn btn-sm btn-light text-info" title="添加标签" 
                                        onclick="openTagModal(<?= $file['id'] ?>, '<?= htmlspecialchars($file['original_name'], ENT_QUOTES) ?>')">
                                    <i class="fa-solid fa-tag"></i>
                                </button>

                                <!-- 3. 分享按钮 -->
                                <form method="POST" action="<?= SITE_URL ?>/share.php" class="d-inline">
                                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                    <input type="hidden" name="file_id" value="<?= $file['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-light text-primary" title="创建外链分享">
                                        <i class="fa-solid fa-share-nodes"></i>
                                    </button>
                                </form>

                                <!-- 4. 预览与下载 -->
                                <a href="<?= SITE_URL ?>/preview.php?id=<?= $file['id'] ?>" class="btn btn-sm btn-light text-secondary" title="预览">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                                <a href="<?= SITE_URL ?>/download.php?id=<?= $file['id'] ?>" class="btn btn-sm btn-light text-success" title="下载">
                                    <i class="fa-solid fa-download"></i>
                                </a>

                                <!-- 5. 移入回收站 -->
                                <form method="POST" action="<?= SITE_URL ?>/actions/trash.php" class="d-inline" onsubmit="return confirm('确定要将此文件移入回收站吗？');">
                                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                    <input type="hidden" name="item_type" value="file">
                                    <input type="hidden" name="item_id" value="<?= $file['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-light text-danger" title="移入回收站">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>

                        <!-- 每个文件专属的标签绑定 Modal -->
                        <div class="modal fade" id="tagModal<?= $file['id'] ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <form method="POST" action="<?= SITE_URL ?>/actions/assign_tag.php" class="modal-content border-0 shadow rounded-4 text-start">
                                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                    <input type="hidden" name="file_id" value="<?= $file['id'] ?>">
                                    <div class="modal-header border-bottom">
                                        <h5 class="modal-title fw-bold">为文件添加标签</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body py-4">
                                        <p class="small text-muted mb-3">为 <strong><?= htmlspecialchars($file['original_name']) ?></strong> 选择一个分类标签：</p>
                                        <?php if (empty($allTags)): ?>
                                            <p class="text-danger small">您还没有创建任何标签，请先去“标签管理”页面创建一个（如“冰红茶”）。</p>
                                        <?php else: ?>
                                            <div class="mb-3">
                                                <label class="form-label small text-secondary">选择标签</label>
                                                <select name="tag_id" class="form-select rounded-3" required>
                                                    <option value="">-- 请选择标签 --</option>
                                                    <?php foreach ($allTags as $tag): ?>
                                                        <option value="<?= $tag['id'] ?>"><?= htmlspecialchars($tag['name']) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="modal-footer border-top">
                                        <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">取消</button>
                                        <?php if (!empty($allTags)): ?>
                                            <button type="submit" class="btn btn-primary rounded-3">确认绑定</button>
                                        <?php endif; ?>
                                    </div>
                                </form>
                            </div>
                        </div>

                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- 新建文件夹 Modal -->
<div class="modal fade" id="newFolderModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content border-0 shadow rounded-4">
            <input type="hidden" name="action" value="create_folder">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold">新建文件夹</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-4">
                <input type="text" name="folder_name" class="form-control rounded-3" placeholder="请输入文件夹名称" required>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">取消</button>
                <button type="submit" class="btn btn-primary rounded-3">确认创建</button>
            </div>
        </form>
    </div>
</div>

<!-- 上传文件 Modal -->
<div class="modal fade" id="uploadModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="<?= SITE_URL ?>/upload.php" method="POST" enctype="multipart/form-data" class="modal-content border-0 shadow rounded-4">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
            <?php if ($folderId): ?>
                <input type="hidden" name="folder_id" value="<?= $folderId ?>">
            <?php endif; ?>
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold">上传文件</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-4">
                <input type="file" name="files[]" class="form-control rounded-3" multiple required>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">取消</button>
                <button type="submit" class="btn btn-primary rounded-3">开始上传</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>