<?php
/**
 * Database Connection & Migration Helper
 * Vega MediaPro
 */

$localConfig = file_exists(__DIR__ . '/db.local.php') ? require __DIR__ . '/db.local.php' : [];

$dbHost = getenv('DB_HOST') ?: ($localConfig['host'] ?? '127.0.0.1');
$dbName = getenv('DB_NAME') ?: ($localConfig['name'] ?? 'vegamediapro');
$dbUser = getenv('DB_USER') ?: ($localConfig['user'] ?? 'root');
$dbPass = getenv('DB_PASS') ?: ($localConfig['pass'] ?? '');

$pdo = null;

try {
    $dsn = "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4";
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    // 1. Tabel Admin Users
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS admin_users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        name VARCHAR(100) DEFAULT 'Administrator',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Inisialisasi admin pertama jika tabel masih kosong
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM admin_users");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $initialHash = password_hash('admin123', PASSWORD_DEFAULT);
        $insertStmt = $pdo->prepare("INSERT INTO admin_users (username, password_hash, name) VALUES ('admin', :hash, 'Admin Vega MediaPro')");
        $insertStmt->execute([':hash' => $initialHash]);
    }

    // 2. Tabel Pengaturan Sistem & Payment Gateway
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS app_settings (
        setting_key VARCHAR(64) PRIMARY KEY,
        setting_value LONGTEXT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // 3. Tabel Transaksi DANA
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS dana_transactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id VARCHAR(64) NOT NULL UNIQUE,
        merchant_trans_id VARCHAR(64) NULL,
        acquirement_id VARCHAR(64) NULL,
        customer_name VARCHAR(100) NULL,
        customer_phone VARCHAR(20) NULL,
        customer_email VARCHAR(100) NULL,
        package_name VARCHAR(100) NULL,
        amount DECIMAL(15,2) NOT NULL,
        status VARCHAR(30) DEFAULT 'PENDING',
        raw_response LONGTEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // 4. Tabel Transaksi doit.id
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS doit_transactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        payment_id VARCHAR(64) NOT NULL UNIQUE,
        reference VARCHAR(128) NOT NULL,
        amount INT NOT NULL,
        status VARCHAR(30) NOT NULL DEFAULT 'PENDING',
        customer_name VARCHAR(100) NULL,
        customer_phone VARCHAR(30) NULL,
        customer_email VARCHAR(100) NULL,
        package_name VARCHAR(100) NULL,
        raw_data LONGTEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX (reference)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
}

/**
 * Helper untuk mengambil setting
 */
function get_setting($key, $default = null) {
    global $pdo;
    if (!$pdo) return $default;
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM app_settings WHERE setting_key = :k LIMIT 1");
        $stmt->execute([':k' => $key]);
        $val = $stmt->fetchColumn();
        return ($val !== false && $val !== null) ? $val : $default;
    } catch (Exception $e) {
        return $default;
    }
}

/**
 * Helper untuk menyimpan setting
 */
function set_setting($key, $value) {
    global $pdo;
    if (!$pdo) return false;
    try {
        $stmt = $pdo->prepare("
            INSERT INTO app_settings (setting_key, setting_value, updated_at) 
            VALUES (:k, :v, NOW()) 
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()
        ");
        return $stmt->execute([':k' => $key, ':v' => $value]);
    } catch (Exception $e) {
        return false;
    }
}
