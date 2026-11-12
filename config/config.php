<?php
if (session_status() === PHP_SESSION_NONE) {
    // 安全 Session Cookie 参数
    session_set_cookie_params([
        'lifetime' => 86400,
        'path' => '/',
        'secure' => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    session_start();
}

// 动态获取当前协议与主机名
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

// 【核心优化】自动识别项目所在的子目录（兼容 Apache 的 /project19_drive 与 PHP 内置服务器的根目录）
$scriptPath = dirname($_SERVER['SCRIPT_NAME'] ?? '');
$baseDir = rtrim(str_replace('\\', '/', $scriptPath), '/');
// 如果处于子目录下（非根目录），则自动拼接；否则为空
$subFolder = ($baseDir && $baseDir !== '/') ? $baseDir : '';

define('SITE_URL', $protocol . '://' . $host);

define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
define('UPLOAD_PATH', ROOT_PATH . 'uploads' . DIRECTORY_SEPARATOR);

error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * 记录用户活动审计日志
 */
function logActivity($userId, $action, $description) {
    global $pdo;
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
    $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
    $stmt->execute([$userId, $action, $description, $ip]);
}

/**
 * 格式化文件大小字节数
 */
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}

/**
 * 重新计算并更新用户的总存储占用
 */
function recalculateUserStorage($userId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT SUM(file_size) as total FROM files WHERE user_id = ? AND is_deleted = 0");
    $stmt->execute([$userId]);
    $res = $stmt->fetch();
    $totalUsed = $res['total'] ?? 0;

    $update = $pdo->prepare("UPDATE users SET storage_used = ? WHERE id = ?");
    $update->execute([$totalUsed, $userId]);
    return $totalUsed;
}

/**
 * CSRF 令牌生成
 */
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * CSRF 令牌校验
 */
function verifyCsrfToken($token) {
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        die("Invalid CSRF Token.");
    }
}