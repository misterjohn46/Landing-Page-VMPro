<?php
/**
 * Admin Authentication & Session Middleware
 * Vega MediaPro
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../api/db.php';

function check_auth() {
    if (empty($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        header('Location: /admin/login.php');
        exit;
    }
}

function get_logged_admin() {
    global $pdo;
    if (empty($_SESSION['admin_id'])) return null;
    try {
        $stmt = $pdo->prepare("SELECT id, username, name, updated_at FROM admin_users WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $_SESSION['admin_id']]);
        return $stmt->fetch();
    } catch (Exception $e) {
        return null;
    }
}

function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf() {
    $token = $_POST['csrf_token'] ?? '';
    if (empty($token) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        die('Validasi keamanan (CSRF Token) gagal. Silakan muat ulang halaman.');
    }
}
