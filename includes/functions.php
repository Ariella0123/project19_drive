<?php
require_once __DIR__ . '/../config/database.php';

/**
 * 记录系统审计日志
 */
function log_activity($pdo, $user_id, $action, $description) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
    $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user_id, $action, $description, $ip]);
}

/**
 * 检查并更新用户存储占用
 */
function update_user_storage($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT SUM(file_size) as total FROM files WHERE user_id = ? AND is_deleted = 0");
    $stmt->execute([$user_id]);
    $res = $stmt->fetch();
    $used = $res['total'] ?? 0;

    $upd = $pdo->prepare("UPDATE users SET storage_used = ? WHERE id = ?");
    $upd->execute([$used, $user_id]);
    return $used;
}

/**
 * 友好的文件大小格式化
 */
function format_bytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}