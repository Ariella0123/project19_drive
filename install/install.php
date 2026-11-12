<?php
/**
 * NovaCloud Storage - Automated Setup Wizard
 */
session_start();
define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);

$step =$_GET['step'] ?? 1;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' &&$step == 2) {
    $dbHost = trim($_POST['db_host']);
    $dbName = trim($_POST['db_name']);
    $dbUser = trim($_POST['db_user']);
    $dbPass =$_POST['db_pass'];

    $adminName = trim($_POST['admin_name']);
    $adminEmail = trim($_POST['admin_email']);
    $adminPass =$_POST['admin_pass'];

    try {
        $pdo = new PDO("mysql:host=$dbHost;charset=utf8mb4", $dbUser, $dbPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);$pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
        $pdo->exec("USE `$dbName`;");

        // Execute table schemas
        $pdo->exec("CREATE TABLE IF NOT EXISTS `settings` (id INT AUTO_INCREMENT PRIMARY KEY, setting_key VARCHAR(100) NOT NULL UNIQUE, setting_value TEXT NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        $pdo->exec("CREATE TABLE IF NOT EXISTS `users` (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) NOT NULL, email VARCHAR(150) NOT NULL UNIQUE, password VARCHAR(255) NOT NULL, role ENUM('admin', 'user') DEFAULT 'user', status ENUM('active', 'inactive') DEFAULT 'active', storage_quota BIGINT DEFAULT 10737418240, storage_used BIGINT DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        $pdo->exec("CREATE TABLE IF NOT EXISTS `folders` (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, parent_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, color VARCHAR(50) DEFAULT '#0d6efd', is_starred TINYINT(1) DEFAULT 0, is_deleted TINYINT(1) DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE, FOREIGN KEY (parent_id) REFERENCES folders(id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        $pdo->exec("CREATE TABLE IF NOT EXISTS `files` (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, folder_id INT DEFAULT NULL, original_name VARCHAR(255) NOT NULL, stored_name VARCHAR(255) NOT NULL, file_path VARCHAR(500) NOT NULL, mime_type VARCHAR(100) NOT NULL, file_extension VARCHAR(20) NOT NULL, file_size BIGINT NOT NULL, is_starred TINYINT(1) DEFAULT 0, is_deleted TINYINT(1) DEFAULT 0, deleted_at TIMESTAMP NULL DEFAULT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE, FOREIGN KEY (folder_id) REFERENCES folders(id) ON DELETE SET NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        $pdo->exec("CREATE TABLE IF NOT EXISTS `file_versions` (id INT AUTO_INCREMENT PRIMARY KEY, file_id INT NOT NULL, version_number INT NOT NULL, stored_name VARCHAR(255) NOT NULL, file_path VARCHAR(500) NOT NULL, file_size BIGINT NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (file_id) REFERENCES files(id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        $pdo->exec("CREATE TABLE IF NOT EXISTS `tags` (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, name VARCHAR(50) NOT NULL, color VARCHAR(20) DEFAULT '#6c757d', FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        $pdo->exec("CREATE TABLE IF NOT EXISTS `file_tags` (file_id INT NOT NULL, tag_id INT NOT NULL, PRIMARY KEY (file_id, tag_id), FOREIGN KEY (file_id) REFERENCES files(id) ON DELETE CASCADE, FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        $pdo->exec("CREATE TABLE IF NOT EXISTS `shares` (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, resource_type ENUM('file', 'folder') NOT NULL, resource_id INT NOT NULL, token VARCHAR(64) NOT NULL UNIQUE, password VARCHAR(255) DEFAULT NULL, expires_at DATETIME DEFAULT NULL, views INT DEFAULT 0, is_active TINYINT(1) DEFAULT 1, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        $pdo->exec("CREATE TABLE IF NOT EXISTS `activity_logs` (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT DEFAULT NULL, action VARCHAR(100) NOT NULL, description TEXT NOT NULL, ip_address VARCHAR(45) NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        // Insert default settings
        $pdo->exec("INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`) VALUES ('app_name', 'NovaCloud Storage'), ('max_upload_size', '52428800'), ('default_storage_quota', '10737418240'), ('allowed_extensions', 'jpg,jpeg,png,gif,pdf,txt,docx,xlsx,zip,mp4,mp3'), ('trash_retention_days', '30');");

        // Create Admin User
        $hashedPass = password_hash($adminPass, PASSWORD_DEFAULT);
        $stmt =$pdo->prepare("INSERT INTO users (name, email, password, role, status) VALUES (?, ?, ?, 'admin', 'active') ON DUPLICATE KEY UPDATE password = ?");
        $stmt->execute([$adminName,$adminEmail, $hashedPass,$hashedPass]);

        // Write database environment configuration
        $envValue = static function ($value) {
            return '"' . addcslashes($value, "\\\"") . '"';
        };
        $envContent = implode(PHP_EOL, [
            'DB_HOST=' . $envValue($dbHost),
            'DB_NAME=' . $envValue($dbName),
            'DB_USER=' . $envValue($dbUser),
            'DB_PASS=' . $envValue($dbPass),
            'DB_CHARSET=utf8mb4',
            'DB_PORT=3306',
        ]) . PHP_EOL;
        file_put_contents(ROOT_PATH . '.env', $envContent, LOCK_EX);

        // Create upload directory and .htaccess
        if (!is_dir(ROOT_PATH . 'uploads')) {
            mkdir(ROOT_PATH . 'uploads', 0755, true);
        }
        file_put_contents(ROOT_PATH . 'uploads/.htaccess', "Options -Indexes\n<FilesMatch \"\.(php|phtml|php3|php4|php5|phar|exe|bat|sh)$\">\n    Order Allow,Deny\n    Deny from all\n</FilesMatch>");

        // Lock installer
        file_put_contents(ROOT_PATH . 'install/install.lock', 'Installed on ' . date('Y-m-d H:i:s'));

        header("Location: ../login.php?installed=success");
        exit();

    } catch (Exception $e) {$error = "Installation Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>NovaCloud Installation Wizard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center py-5" style="min-height: 100vh;">
    <div class="container" style="max-width: 600px;">
        <div class="card shadow border-0 rounded-4 p-4">
            <h2 class="fw-bold mb-4 text-primary">NovaCloud Setup</h2>
            <?php if(file_exists(__DIR__ . '/install.lock')): ?>
                <div class="alert alert-warning">NovaCloud is already installed. Delete <code>install.lock</code> to reinstall.</div>
                <a href="../login.php" class="btn btn-primary w-100">Go to Login</a>
            <?php else: ?>
                <?php if($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
                <form method="POST">
                    <h5 class="text-secondary mb-3">1. Database Configuration</h5>
                    <div class="mb-3"><label class="form-label">Database Host</label><input type="text" name="db_host" class="form-control" value="localhost" required></div>
                    <div class="mb-3"><label class="form-label">Database Name</label><input type="text" name="db_name" class="form-control" value="cloud_drive_db" required></div>
                    <div class="mb-3"><label class="form-label">Database User</label><input type="text" name="db_user" class="form-control" value="root" required></div>
                    <div class="mb-3"><label class="form-label">Database Password</label><input type="password" name="db_pass" class="form-control"></div>
                    
                    <h5 class="text-secondary mb-3 mt-4">2. Administrator Account</h5>
                    <div class="mb-3"><label class="form-label">Admin Full Name</label><input type="text" name="admin_name" class="form-control" value="System Administrator" required></div>
                    <div class="mb-3"><label class="form-label">Admin Email</label><input type="email" name="admin_email" class="form-control" value="admin@novacloud.local" required></div>
                    <div class="mb-3"><label class="form-label">Admin Password</label><input type="password" name="admin_pass" class="form-control" required></div>
                    
                    <button type="submit" class="btn btn-primary w-100 py-2 mt-3">Complete Installation</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>