<?php
/**
 * Root Entry Point - Vega MediaPro
 * Melayani landing page index.html dan meneruskan webhook doit.id jika dikirim ke root URL
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hasDoitSignature = isset($_SERVER['HTTP_PAYBRIDGE_SIGNATURE']);
    if (!$hasDoitSignature && function_exists('getallheaders')) {
        foreach (getallheaders() as $k => $v) {
            if (strcasecmp($k, 'PayBridge-Signature') === 0) {
                $hasDoitSignature = true;
                break;
            }
        }
    }

    if ($hasDoitSignature) {
        require __DIR__ . '/api/doit-notify.php';
        exit;
    }
}

// Layani landing page HTML untuk visitor
if (file_exists(__DIR__ . '/index.html')) {
    include __DIR__ . '/index.html';
    exit;
}
