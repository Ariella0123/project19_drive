<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

/** @var int $currentUserId */
/** @var PDO $pdo */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken($_POST['csrf_token'] ?? '');
    $fileId = intval($_POST['file_id'] ?? 0);
    $token = bin2hex(random_bytes(16));
    $pass = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : null;

    $stmt = $pdo->prepare("INSERT INTO shares (user_id, resource_type, resource_id, token, password) VALUES (?, 'file', ?, ?, ?)");
    $stmt->execute([$currentUserId, $fileId, $token, $pass]);
    
    $_SESSION['share_link'] = SITE_URL . "/public_share.php?token=" . $token;
}
?>

<div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
    <h4 class="fw-bold mb-3"><i class="fa-solid fa-share-nodes text-primary me-2"></i>我的分享</h4>

    <?php if (isset($_SESSION['share_link'])): ?>
        <?php $shareLink = $_SESSION['share_link']; ?>
        <div class="alert alert-success d-flex align-items-center justify-content-between flex-wrap gap-2 rounded-3 p-3">
            <div>
                <span class="fw-semibold">分享链接创建成功：</span>
                <code id="shareLinkText" class="text-dark bg-white px-2 py-1 rounded border ms-1"><?= $shareLink ?></code>
            </div>
            <button type="button" class="btn btn-sm btn-outline-success bg-white rounded-3 px-3" onclick="copyShareLink()">
                <i class="fa-solid fa-copy me-1"></i> 复制链接
            </button>
        </div>
        <?php unset($_SESSION['share_link']); ?>
    <?php endif; ?>

    <p class="text-muted small mb-0">您可以通过分享链接将文件安全地发送给其他人。</p>
</div>

<script>
function copyShareLink() {
    const linkText = document.getElementById('shareLinkText').innerText;
    navigator.clipboard.writeText(linkText).then(() => {
        alert('链接已成功复制到剪贴板！');
    }).catch(err => {
        console.error('复制失败', err);
    });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>