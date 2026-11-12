<?php
require_once __DIR__ . '/includes/auth.php'; // 包含身份验证和配置文件引入

/** @var int $currentUserId */
/** @var PDO $pdo */

// 1. 优先处理 POST 请求（必须在任何 HTML 输出之前进行重定向）
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken($_POST['csrf_token'] ?? '');
    $action = $_POST['action'] ?? '';

    if ($action === 'create_tag') {
        $name = trim($_POST['name'] ?? '');
        $color = trim($_POST['color'] ?? '#6c757d');

        if (!empty($name)) {
            $stmt = $pdo->prepare("INSERT INTO tags (user_id, name, color) VALUES (?, ?, ?)");
            $stmt->execute([$currentUserId, $name, $color]);
            logActivity($currentUserId, 'CREATE_TAG', "创建标签: $name");
        }
    } elseif ($action === 'delete_tag') {
        $tagId = intval($_POST['tag_id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM tags WHERE id = ? AND user_id = ?");
        $stmt->execute([$tagId, $currentUserId]);
        logActivity($currentUserId, 'DELETE_TAG', "删除标签 ID: $tagId");
    }

    header("Location: " . SITE_URL . "/tags.php");
    exit();
}

// 2. 接着才引入页面头部和侧边栏（开始输出 HTML）
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

// 获取当前用户的所有标签，并统计每个标签关联的文件数量
$stmtTags = $pdo->prepare("
    SELECT t.*, COUNT(ft.file_id) as file_count 
    FROM tags t 
    LEFT JOIN file_tags ft ON t.id = ft.tag_id 
    WHERE t.user_id = ? 
    GROUP BY t.id 
    ORDER BY t.name ASC
");
$stmtTags->execute([$currentUserId]);
$tags = $stmtTags->fetchAll();

// 如果通过 GET 传入了 tag_id，则查询该标签下的文件
$selectedTagId = filter_input(INPUT_GET, 'tag_id', FILTER_VALIDATE_INT);
$selectedTagName = '';
$taggedFiles = [];

if ($selectedTagId) {
    $tCheck = $pdo->prepare("SELECT name FROM tags WHERE id = ? AND user_id = ?");
    $tCheck->execute([$selectedTagId, $currentUserId]);
    $tagData = $tCheck->fetch();

    if ($tagData) {
        $selectedTagName = $tagData['name'];
        $fStmt = $pdo->prepare("
            SELECT f.* FROM files f 
            JOIN file_tags ft ON f.id = ft.file_id 
            WHERE ft.tag_id = ? AND f.user_id = ? AND f.is_deleted = 0
        ");
        $fStmt->execute([$selectedTagId, $currentUserId]);
        $taggedFiles = $fStmt->fetchAll();
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="fa-solid fa-tags text-primary me-2"></i>标签系统管理</h4>
        <p class="text-muted small mb-0">使用轻量级标签对文件进行分类与快速索引</p>
    </div>
    <button class="btn btn-primary rounded-3" data-bs-toggle="modal" data-bs-target="#createTagModal">
        <i class="fa-solid fa-plus me-1"></i> 新建标签
    </button>
</div>

<!-- 标签列表展示区 -->
<div class="row g-3 mb-4">
    <?php if (empty($tags)): ?>
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4 p-4 text-center bg-white">
                <p class="text-muted mb-2">暂无任何标签</p>
                <button class="btn btn-outline-primary btn-sm mx-auto rounded-3" data-bs-toggle="modal" data-bs-target="#createTagModal">创建第一个标签</button>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($tags as $tag): ?>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm rounded-3 p-3 bg-white d-flex flex-row align-items-center justify-content-between">
                    <a href="<?= SITE_URL ?>/tags.php?tag_id=<?= $tag['id'] ?>" class="text-decoration-none d-flex align-items-center text-dark text-truncate me-2">
                        <span class="badge rounded-pill me-2" style="background-color: <?= htmlspecialchars($tag['color']) ?>; width: 12px; height: 12px; padding: 0;">&nbsp;</span>
                        <span class="fw-semibold text-truncate"><?= htmlspecialchars($tag['name']) ?></span>
                        <span class="text-muted small ms-2">(<?= $tag['file_count'] ?>)</span>
                    </a>
                    <form method="POST" class="d-inline" onsubmit="return confirm('确定要删除此标签吗？');">
                        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                        <input type="hidden" name="tag_id" value="<?= $tag['id'] ?>">
                        <button type="submit" name="action" value="delete_tag" class="btn btn-link text-danger p-0 border-0" title="删除标签">
                            <i class="fa-solid fa-trash-can"></i>
                        </button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- 如果选择了特定标签，显示该标签下的文件 -->
<?php if ($selectedTagId): ?>
    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0">标签：<span class="text-primary"><?= htmlspecialchars($selectedTagName) ?></span> 的文件</h5>
            <a href="<?= SITE_URL ?>/tags.php" class="btn btn-sm btn-outline-secondary rounded-3">查看全部标签</a>
        </div>

        <?php if (empty($taggedFiles)): ?>
            <p class="text-muted text-center py-3">该标签下暂无关联文件。</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr class="text-secondary small">
                            <th>文件名</th>
                            <th>大小</th>
                            <th>上传时间</th>
                            <th class="text-end">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($taggedFiles as $file): ?>
                            <tr>
                                <td>
                                    <i class="fa-solid fa-file-lines text-primary me-2"></i>
                                    <?= htmlspecialchars($file['original_name']) ?>
                                </td>
                                <td><?= formatBytes($file['file_size']) ?></td>
                                <td class="text-muted small"><?= $file['created_at'] ?></td>
                                <td class="text-end">
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
        <?php endif; ?>
    </div>
<?php endif; ?>

<!-- 新建标签 Modal -->
<div class="modal fade" id="createTagModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content border-0 shadow rounded-4">
            <input type="hidden" name="action" value="create_tag">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold">创建新标签</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-4">
                <div class="mb-3">
                    <label class="form-label small text-secondary">标签名称</label>
                    <input type="text" name="name" class="form-control rounded-3" placeholder="例如：工作、重要、财务" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small text-secondary">标签颜色标记</label>
                    <input type="color" name="color" class="form-control form-control-color w-100 rounded-3" value="#4f46e5">
                </div>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">取消</button>
                <button type="submit" class="btn btn-primary rounded-3">确认创建</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>