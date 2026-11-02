<?php
/**
 * Database Connection & Migration Helper
 * Vega MediaPro - DANA Enterprise
 */

// Konfigurasi Database (Bisa disesuaikan dengan database di CloudPanel)
$dbHost = getenv('DB_HOST') ?: '127.0.0.1';
$dbName = getenv('DB_NAME') ?: 'vegamediapro';
$dbUser = getenv('DB_USER') ?: 'vegamediapro';
$dbPass = getenv('DB_PASS') ?: '';

$pdo = null;

try {
    $dsn = "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4";
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    // Buat tabel transaksi DANA secara otomatis jika belum ada
    $tableSql = "
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
    ";
    $pdo->exec($tableSql);

} catch (PDOException $e) {
    // Jika database belum dibuat di CloudPanel, jangan hentikan proses notifikasi
    error_log("Database connection failed: " . $e->getMessage());
}
