<?php
/**
 * NovaCloud Storage - 用户身份验证与授权拦截器
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/functions.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: " . SITE_URL . "/login.php");
    exit();
}

/** @var int $currentUserId */
/** @var string $currentUserRole */

$currentUserId = $_SESSION['user_id'];
$currentUserRole = $_SESSION['role'];