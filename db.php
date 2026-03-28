<?php

declare(strict_types=1);

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$dbHost = 'localhost';
$dbName = 'hvernued_range';
$dbUser = 'hvernued_cpses_hvnqmd5ph8';
$dbPass = 'Zirect@1618*1##';

$dsn = "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    die('Database connection failed: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
}

function getPDO(): PDO
{
    global $pdo;
    return $pdo;
}

function e($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(): bool
{
    return isset($_POST['csrf_token'], $_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string)$_POST['csrf_token']);
}

function require_admin(): void
{
    if (empty($_SESSION['admin_id'])) {
        header('Location: login.php');
        exit;
    }
}

function column_exists(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND COLUMN_NAME = :column');
    $stmt->execute([':table' => $table, ':column' => $column]);
    return (int)$stmt->fetchColumn() > 0;
}

function ensure_column(PDO $pdo, string $table, string $column, string $definition): void
{
    if (!column_exists($pdo, $table, $column)) {
        $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
    }
}

function ensure_tables(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS admin (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS students (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_name VARCHAR(150) NOT NULL,
        class VARCHAR(10) NOT NULL,
        gender VARCHAR(10) NOT NULL,
        class_number INT NOT NULL,
        created_at DATETIME NOT NULL,
        UNIQUE KEY uniq_class_gender_number (class, gender, class_number)
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS textbooks (
        id INT AUTO_INCREMENT PRIMARY KEY,
        book_name VARCHAR(150) NOT NULL,
        class VARCHAR(10) NOT NULL,
        price DECIMAL(10,2) NOT NULL
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS notebooks (
        id INT AUTO_INCREMENT PRIMARY KEY,
        notebook_type VARCHAR(100) NOT NULL,
        pages INT NOT NULL,
        price DECIMAL(10,2) NOT NULL
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_name VARCHAR(150) NOT NULL,
        class VARCHAR(10) NOT NULL,
        gender VARCHAR(10) NOT NULL,
        class_number INT NOT NULL,
        item_type VARCHAR(20) NOT NULL,
        item_name VARCHAR(150) NOT NULL,
        pages INT NULL,
        price DECIMAL(10,2) NOT NULL,
        quantity INT NOT NULL,
        total_price DECIMAL(10,2) NOT NULL,
        order_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_class (class),
        INDEX idx_gender (gender),
        INDEX idx_item_name (item_name),
        INDEX idx_order_date (order_date)
    )");

    // Auto-fix older deployments where columns are missing (prevents Unknown column fatal errors).
    ensure_column($pdo, 'students', 'student_name', "VARCHAR(150) NOT NULL DEFAULT '' AFTER id");
    ensure_column($pdo, 'students', 'class', "VARCHAR(10) NOT NULL DEFAULT '' AFTER student_name");
    ensure_column($pdo, 'students', 'gender', "VARCHAR(10) NOT NULL DEFAULT '' AFTER class");
    ensure_column($pdo, 'students', 'class_number', "INT NOT NULL DEFAULT 0 AFTER gender");
    ensure_column($pdo, 'students', 'created_at', "DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER class_number");

    // Compatibility with older code that used class_id
    ensure_column($pdo, 'students', 'class_id', "INT NULL AFTER class");
    ensure_column($pdo, 'orders', 'class_id', "INT NULL AFTER class");
    ensure_column($pdo, 'textbooks', 'class_id', "INT NULL AFTER class");

    ensure_column($pdo, 'orders', 'student_name', "VARCHAR(150) NOT NULL DEFAULT '' AFTER id");
    ensure_column($pdo, 'orders', 'class', "VARCHAR(10) NOT NULL DEFAULT '' AFTER student_name");
    ensure_column($pdo, 'orders', 'gender', "VARCHAR(10) NOT NULL DEFAULT '' AFTER class");
    ensure_column($pdo, 'orders', 'class_number', "INT NOT NULL DEFAULT 0 AFTER gender");
    ensure_column($pdo, 'orders', 'item_type', "VARCHAR(20) NOT NULL DEFAULT 'textbook' AFTER class_number");
    ensure_column($pdo, 'orders', 'item_name', "VARCHAR(150) NOT NULL DEFAULT '' AFTER item_type");
    ensure_column($pdo, 'orders', 'pages', "INT NULL AFTER item_name");
    ensure_column($pdo, 'orders', 'price', "DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER pages");
    ensure_column($pdo, 'orders', 'quantity', "INT NOT NULL DEFAULT 0 AFTER price");
    ensure_column($pdo, 'orders', 'total_price', "DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER quantity");
    ensure_column($pdo, 'orders', 'order_date', "DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER total_price");

    $hash = '$2y$12$k4jxSd9qC9Ct2pAIohB/pegFn1CyiEDMdRMvu/zwfjx0Sti7n/Mge';
    $stmt = $pdo->prepare('INSERT IGNORE INTO admin (username, password) VALUES (:u, :p)');
    $stmt->execute([':u' => 'admin', ':p' => $hash]);

    $countBooks = (int)$pdo->query('SELECT COUNT(*) FROM textbooks')->fetchColumn();
    if ($countBooks === 0) {
        $pdo->exec("INSERT INTO textbooks (book_name, class, price) VALUES
            ('Fiqh','1',90),('Arabic','1',80),('Nahvu','1',70),
            ('Fiqh','2',100),('Arabic','2',90),('Quran','2',120)");
    }

    $countNotebooks = (int)$pdo->query('SELECT COUNT(*) FROM notebooks')->fetchColumn();
    if ($countNotebooks === 0) {
        $pdo->exec("INSERT INTO notebooks (notebook_type, pages, price) VALUES
            ('Notebook',100,30),('Notebook',200,50),('Notebook',300,70)");
    }
}

function render_site_footer(): void
{
    echo '<footer class="site-footer">'
        . '<p>Madrasa Book Portal | Contact: +91 6235 989 198</p>'
        . '<p class="meta">© ' . date('Y') . ' All Rights Reserved | Design by '
        . '<a href="https://mhm.mmhnu.online/" target="_blank" rel="noopener noreferrer">Muhsin Faizy</a></p>'
        . '</footer>';
}

ensure_tables($pdo);
