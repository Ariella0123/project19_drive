<?php
require_once __DIR__ . '/auth.php';

if ($currentUserRole !== 'admin') {
    http_response_code(403);
    die("Access Denied: Administrator privileges required.");
}