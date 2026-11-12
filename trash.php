<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

// 处理还原与彻底删除
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken($_POST['csrf_token'] ?? '');
    $fileId = intval($_POST['file_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($action === 'restore') {
        $stmt = $pdo->prepare("UPDATE files SET is_deleted = 0, deleted_at = NULL WHERE id = ? AND user_id = ?");
        $stmt->execute([$fileId, $currentUserId]);
        logActivity($currentUserId, 'RESTORE', "还原文件 ID: $fileId");
    } elseif ($action === 'permanent_delete') {
        $stmt = $pdo->prepare("SELECT * FROM files WHERE id = ? AND user_id = ?");
        $stmt->execute([$fileId, $currentUserId]);
        $file = $stmt->fetch();

        if ($file) {
            @unlink($file['file_path']); // 物理删除
            $pdo->prepare("DELETE FROM files WHERE id = ?")->execute([$fileId]);
            recalculateUserStorage($currentUserId);
            logActivity($currentUserId, 'PERMANENT_DELETE', "彻底删除文件: {$file['original_name']}");
        }
    }
}

// 获取回收站文件
$stmt = $pdo->prepare("SELECT * FROM files WHERE user_id = ? AND is_deleted = 1");
$stmt->execute([$currentUserId]);
$deletedFiles = $stmt->fetchAll();
?>

<h4 class="fw-bold mb-4">回收站</h4>
<div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr class="text-secondary small">
                    <th>文件名</th>
                    <th>大小</th>
                    <th>删除时间</th>
                    <th class="text-end">操作</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($deletedFiles as $df): ?>
                    <tr>
                        <td><?= htmlspecialchars($df['original_name']) ?></td>
                        <td><?= formatBytes($df['file_size']) ?></td>
                        <td><?= $df['deleted_at'] ?></td>
                        <td class="text-end">
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                <input type="hidden" name="file_id" value="<?= $df['id'] ?>">
                                <button type="submit" name="action" value="restore" class="btn btn-sm btn-outline-success">还原</button>
                                <button type="submit" name="action" value="permanent_delete" class="btn btn-sm btn-outline-danger" onclick="return confirm('确定彻底删除？不可恢复！')">彻底删除</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>