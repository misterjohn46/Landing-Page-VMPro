<?php
/**
 * Duitku Callback Handler (server-side)
 * =====================================
 * Duitku memanggil endpoint ini dari server mereka untuk memberitahukan status transaksi.
 * Ini adalah SATU-SATUNYA sumber kebenaran status pembayaran — jangan pernah menandai
 * pesanan lunas berdasarkan aksi di browser.
 *
 * Signature callback Duitku: MD5(merchantCode + amount + merchantOrderId + apiKey)
 */

declare(strict_types=1);

$merchantCode = getenv('DUITKU_MERCHANT_CODE') ?: '';
$apiKey       = getenv('DUITKU_API_KEY') ?: '';

$localFile = __DIR__ . '/credentials.local.php';
if (($merchantCode === '' || $apiKey === '') && is_readable($localFile)) {
    $local = require $localFile;
    if (is_array($local)) {
        $merchantCode = $merchantCode !== '' ? $merchantCode : (string)($local['merchantCode'] ?? '');
        $apiKey       = $apiKey !== '' ? $apiKey : (string)($local['apiKey'] ?? '');
    }
}

if ($merchantCode === '' || $apiKey === '') {
    error_log('Duitku callback: kredensial belum dikonfigurasi.');
    http_response_code(500);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    exit;
}

$receivedMerchantCode = (string)($_POST['merchantCode'] ?? '');
$amount               = (string)($_POST['amount'] ?? '');
$merchantOrderId      = (string)($_POST['merchantOrderId'] ?? '');
$resultCode           = (string)($_POST['resultCode'] ?? '');
$receivedSignature    = (string)($_POST['signature'] ?? '');

if ($receivedMerchantCode === '' || $amount === '' || $merchantOrderId === '' || $receivedSignature === '') {
    error_log('Duitku callback: parameter tidak lengkap.');
    http_response_code(400);
    exit;
}

$expectedSignature = md5($receivedMerchantCode . $amount . $merchantOrderId . $apiKey);

// hash_equals mencegah timing attack saat membandingkan signature.
if (!hash_equals($expectedSignature, $receivedSignature) || $receivedMerchantCode !== $merchantCode) {
    error_log('Duitku callback: signature tidak valid untuk order ' . $merchantOrderId);
    http_response_code(400);
    exit;
}

if ($resultCode === '00') {
    // TODO: tandai $merchantOrderId sebagai LUNAS di database.
    //       Cocokkan juga $amount dengan harga paket yang tersimpan saat inquiry
    //       sebelum menganggap pembayaran valid, lalu kirim notifikasi ke admin.
    error_log('Duitku callback: pembayaran BERHASIL untuk order ' . $merchantOrderId . ' (Rp ' . $amount . ')');
} else {
    // TODO: tandai $merchantOrderId sebagai GAGAL / DIBATALKAN di database.
    error_log('Duitku callback: pembayaran GAGAL untuk order ' . $merchantOrderId . ' (resultCode ' . $resultCode . ')');
}

http_response_code(200);
echo 'OK';
