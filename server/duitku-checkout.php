<?php
/**
 * Duitku Checkout Handler (server-side)
 * =====================================
 * PENTING:
 * - File ini HANYA berfungsi di hosting yang mengeksekusi PHP (cPanel, VPS, Laragon, XAMPP).
 *   GitHub Pages TIDAK mengeksekusi PHP dan akan menyajikan file ini sebagai teks mentah,
 *   karena itu folder server/ dikecualikan dari publikasi lewat _config.yml.
 * - Kredensial TIDAK BOLEH ditulis di dalam file ini. Lihat server/credentials.example.php.
 * - Harga ditentukan di server (lihat $PACKAGES). Jangan pernah percaya nominal dari browser.
 */

declare(strict_types=1);

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

/** Kirim respons JSON lalu hentikan eksekusi. */
function respond(int $httpCode, array $payload): void
{
    http_response_code($httpCode);
    echo json_encode($payload);
    exit;
}

/**
 * Ambil kredensial dari environment variable, atau dari file lokal
 * server/credentials.local.php (di-gitignore) untuk hosting yang sulit set env var.
 */
function load_credentials(): array
{
    $merchantCode = getenv('DUITKU_MERCHANT_CODE') ?: '';
    $apiKey       = getenv('DUITKU_API_KEY') ?: '';
    $isProduction = filter_var(getenv('DUITKU_PRODUCTION') ?: 'false', FILTER_VALIDATE_BOOLEAN);
    $baseUrl      = getenv('APP_BASE_URL') ?: '';

    $localFile = __DIR__ . '/credentials.local.php';
    if (($merchantCode === '' || $apiKey === '') && is_readable($localFile)) {
        $local = require $localFile;
        if (is_array($local)) {
            $merchantCode = $merchantCode !== '' ? $merchantCode : (string)($local['merchantCode'] ?? '');
            $apiKey       = $apiKey !== '' ? $apiKey : (string)($local['apiKey'] ?? '');
            $isProduction = $isProduction ?: (bool)($local['isProduction'] ?? false);
            $baseUrl      = $baseUrl !== '' ? $baseUrl : (string)($local['baseUrl'] ?? '');
        }
    }

    return [
        'merchantCode' => $merchantCode,
        'apiKey'       => $apiKey,
        'isProduction' => $isProduction,
        'baseUrl'      => rtrim($baseUrl, '/'),
    ];
}

/**
 * Daftar paket resmi. Harga dikunci di server agar tidak bisa dimanipulasi dari browser.
 * Nominal dalam Rupiah.
 */
$PACKAGES = [
    'pos-standar'    => ['name' => 'Paket POS Standar',            'amount' => 2500000],
    'sekolah-pintar' => ['name' => 'Paket SIM Sekolah Pintar',     'amount' => 5500000],
    'kustom-dp'      => ['name' => 'DP Pemesanan Aplikasi Kustom', 'amount' => 1000000],
];

/** Metode pembayaran Duitku yang diizinkan. */
$ALLOWED_PAYMENT_METHODS = ['SP', 'VC', 'BC', 'M2', 'BR', 'I1', 'BT'];

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    respond(405, ['status' => 'error', 'message' => 'Metode request tidak diizinkan.']);
}

$credentials = load_credentials();
if ($credentials['merchantCode'] === '' || $credentials['apiKey'] === '') {
    error_log('Duitku: DUITKU_MERCHANT_CODE / DUITKU_API_KEY belum dikonfigurasi.');
    respond(500, ['status' => 'error', 'message' => 'Pembayaran online belum dikonfigurasi. Silakan hubungi admin.']);
}
if ($credentials['baseUrl'] === '') {
    error_log('Duitku: APP_BASE_URL belum dikonfigurasi.');
    respond(500, ['status' => 'error', 'message' => 'Pembayaran online belum dikonfigurasi. Silakan hubungi admin.']);
}

$raw   = file_get_contents('php://input') ?: '';
$input = json_decode($raw, true);
if (!is_array($input)) {
    respond(400, ['status' => 'error', 'message' => 'Format request tidak valid.']);
}

// --- Validasi paket: harga diambil dari server, bukan dari request ---
$packageId = (string)($input['packageId'] ?? '');
if (!isset($PACKAGES[$packageId])) {
    respond(422, ['status' => 'error', 'message' => 'Paket yang dipilih tidak dikenali.']);
}
$paymentAmount  = $PACKAGES[$packageId]['amount'];
$productDetails = $PACKAGES[$packageId]['name'];

// --- Validasi data pelanggan ---
$name = trim((string)($input['name'] ?? ''));
if (mb_strlen($name) < 3 || mb_strlen($name) > 100) {
    respond(422, ['status' => 'error', 'message' => 'Nama pemesan wajib diisi (3-100 karakter).']);
}

$email = trim((string)($input['email'] ?? ''));
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(422, ['status' => 'error', 'message' => 'Alamat email tidak valid.']);
}

$phoneDigits = preg_replace('/\D+/', '', (string)($input['phone'] ?? '')) ?? '';
if (strlen($phoneDigits) < 9 || strlen($phoneDigits) > 15) {
    respond(422, ['status' => 'error', 'message' => 'Nomor WhatsApp tidak valid.']);
}

$paymentMethod = strtoupper(trim((string)($input['paymentMethod'] ?? 'SP')));
if (!in_array($paymentMethod, $ALLOWED_PAYMENT_METHODS, true)) {
    respond(422, ['status' => 'error', 'message' => 'Metode pembayaran tidak didukung.']);
}

$merchantOrderId = 'VMP-' . date('YmdHis') . '-' . bin2hex(random_bytes(3));

// Signature Duitku: MD5(merchantCode + merchantOrderId + paymentAmount + apiKey)
$signature = md5($credentials['merchantCode'] . $merchantOrderId . $paymentAmount . $credentials['apiKey']);

$endpoint = $credentials['isProduction']
    ? 'https://passport.duitku.com/webapi/api/merchant/v2/inquiry'
    : 'https://sandbox.duitku.com/webapi/api/merchant/v2/inquiry';

$params = [
    'merchantCode'    => $credentials['merchantCode'],
    'paymentAmount'   => $paymentAmount,
    'paymentMethod'   => $paymentMethod,
    'merchantOrderId' => $merchantOrderId,
    'productDetails'  => $productDetails,
    'customerVaName'  => $name,
    'email'           => $email,
    'phoneNumber'     => $phoneDigits,
    'callbackUrl'     => $credentials['baseUrl'] . '/server/duitku-callback.php',
    'returnUrl'       => $credentials['baseUrl'] . '/terima-kasih.html',
    'signature'       => $signature,
    'expiryPeriod'    => 1440,
];

$body = json_encode($params, JSON_THROW_ON_ERROR);

$ch = curl_init($endpoint);
curl_setopt_array($ch, [
    CURLOPT_CUSTOMREQUEST  => 'POST',
    CURLOPT_POSTFIELDS     => $body,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($body),
    ],
    // Verifikasi sertifikat WAJIB aktif: mencegah serangan man-in-the-middle.
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2,
    CURLOPT_TIMEOUT        => 30,
]);

$response = curl_exec($ch);
$curlErr  = curl_error($ch);
$status    = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($response === false || $curlErr !== '') {
    error_log('Duitku request gagal: ' . $curlErr);
    respond(502, ['status' => 'error', 'message' => 'Gagal menghubungi payment gateway. Silakan coba lagi.']);
}

$duitku = json_decode((string)$response, true);
if (!is_array($duitku) || $status >= 400) {
    error_log('Duitku respons tidak valid (HTTP ' . $status . '): ' . (string)$response);
    respond(502, ['status' => 'error', 'message' => 'Payment gateway menolak transaksi. Silakan hubungi admin.']);
}

// TODO: simpan $merchantOrderId + paymentAmount + status "PENDING" ke database,
// lalu verifikasi pelunasan lewat server/duitku-callback.php sebelum menganggap lunas.

respond(200, [
    'status'          => 'success',
    'merchantOrderId' => $merchantOrderId,
    'amount'          => $paymentAmount,
    'paymentUrl'      => $duitku['paymentUrl'] ?? null,
    'reference'       => $duitku['reference'] ?? null,
]);
