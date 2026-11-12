# NovaCloud Storage System

NovaCloud Storage is a professional, secure, and fully responsive web-based digital file management & storage platform built with **PHP 8.3**, **Native PDO MySQL**, **Bootstrap 5**, and **Vanilla JavaScript**.

---

## Features
- **File Management**: Upload, download, preview, rename, move, star, tag, and restore files.
- **Hierarchical Folders**: Create nested folders with breadcrumb navigation and color coding.
- **Storage Quotas**: Real-time quota enforcement preventing uploads beyond allocated limits.
- **File Versioning**: Maintain revision history and rollback to previous versions.
- **Public & Password-Protected Sharing**: Generate secure random tokens, expiration dates, and password protection for shared files/folders.
- **Recycle Bin (Trash)**: Soft deletion with automated trash retention cleanup.
- **Comprehensive Admin Panel**: Manage users, storage capacities, system activity logs, and global extension filters.
- **Enterprise Security**: Prepared SQL statements, password hashing, CSRF token validation, XSS escaping, and upload directory script execution prevention.

---

## Installation Instructions (XAMPP / Localhost)

1. Clone or extract the `NovaCloud` directory into your local web server root folder:
   - **XAMPP**: `C:\xampp\htdocs\NovaCloud\`
   - **MAMP**: `/Applications/MAMP/htdocs/NovaCloud/`
   - **WampServer**: `C:\wamp64\www\NovaCloud\`
2. Start **Apache** and **MySQL** from your XAMPP Control Panel.
3. Copy `.env.example` to `.env`, then update the database values for your local environment. The `.env` file is ignored by version control and must not be committed.
4. Open your web browser and navigate to:
   ```text
   http://localhost/NovaCloud/install/install.php