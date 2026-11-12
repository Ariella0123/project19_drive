<!-- 全局统一的标签绑定 Modal -->
<div class="modal fade" id="globalTagModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="<?= SITE_URL ?>/actions/assign_tag.php" class="modal-content border-0 shadow rounded-4 text-start">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
            <input type="hidden" name="file_id" id="modalFileId" value="">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold">为文件添加标签</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-4">
                <p class="small text-muted mb-3">正在为 <strong id="modalFileName" class="text-primary"></strong> 选择分类标签：</p>
                <?php if (empty($allTags)): ?>
                    <p class="text-danger small">您还没有创建任何标签，请先去“标签管理”页面创建一个（例如：冰红茶）。</p>
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

<script>
// 点击标签按钮时通过 JS 动态赋值并打开全局弹窗
function openTagModal(fileId, fileName) {
    document.getElementById('modalFileId').value = fileId;
    document.getElementById('modalFileName').textContent = fileName;
    var myModal = new bootstrap.Modal(document.getElementById('globalTagModal'));
    myModal.show();
}
</script>
</div> <!-- Close container-fluid -->
</div> <!-- Close page-content-wrapper -->
</div> <!-- Close wrapper -->

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
<!-- Custom JavaScript Engine -->
<script src="<?= SITE_URL ?>/assets/js/app.js"></script>
</body>
</html>