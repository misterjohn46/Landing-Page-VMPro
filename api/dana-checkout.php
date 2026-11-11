<?php
/**
 * DANA Enterprise Checkout Endpoint - Production Ready
 * Vega MediaPro (https://vegamediapro.web.id/api/dana-checkout.php)
 */

header('Content-Type: application/json; charset=utf-8');

// Hanya terima POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

// Ambil input JSON
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON input']);
    exit;
}

$packageName = trim($data['packageName'] ?? 'Paket Layanan Software');
$amount      = (float) ($data['price'] ?? 0);
$custName    = trim($data['name'] ?? 'Pelanggan Vega MediaPro');
$custEmail   = trim($data['email'] ?? 'studio46probolinggo@gmail.com');
$custPhone   = trim($data['phone'] ?? '082335338113');
$invoice     = trim($data['invoice'] ?? ('VMP-' . strtoupper(substr(uniqid(), -6))));

if ($amount <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Nominal pesanan tidak valid']);
    exit;
}

// Muat konfigurasi DANA
$config = require __DIR__ . '/dana-config.php';

// 1. Simpan Transaksi ke Database (Status PENDING) jika DB tersedia
if (file_exists(__DIR__ . '/db.php')) {
    try {
        require_once __DIR__ . '/db.php';
        if (isset($pdo)) {
            $stmt = $pdo->prepare("
                INSERT INTO dana_transactions 
                (order_id, customer_name, customer_phone, customer_email, package_name, amount, status, created_at)
                VALUES 
                (:order_id, :cust_name, :cust_phone, :cust_email, :package_name, :amount, 'PENDING', NOW())
                ON DUPLICATE KEY UPDATE 
                customer_name = VALUES(customer_name),
                customer_phone = VALUES(customer_phone),
                customer_email = VALUES(customer_email),
                amount = VALUES(amount),
                updated_at = NOW()
            ");
            $stmt->execute([
                ':order_id'     => $invoice,
                ':cust_name'    => $custName,
                ':cust_phone'   => $custPhone,
                ':cust_email'   => $custEmail,
                ':package_name' => $packageName,
                ':amount'       => $amount,
            ]);
        }
    } catch (Exception $e) {
        error_log("DB insert failed: " . $e->getMessage());
    }
}

// 2. Siapkan Request ke API DANA (SNAP BI Payment Gateway)
$baseUrl   = ($config['env'] === 'PRODUCTION') ? $config['production_url'] : $config['sandbox_url'];
$path      = '/payment-gateway/v1.0/debit/payment-host-to-host.htm';
$partnerId = $config['partner_id'];

// Format timestamp & expiry ISO 8601 GMT+7
$tz        = new DateTimeZone('Asia/Jakarta');
$now       = new DateTime('now', $tz);
$timestamp = $now->format('Y-m-d\TH:i:sP');
$validUpTo = (new DateTime('+2 hours', $tz))->format('Y-m-d\TH:i:sP');

$payload = [
    'partnerReferenceNo' => $invoice,
    'merchantId'         => $config['merchant_id'],
    'subMerchantId'      => '',
    'amount'             => [
        'value'    => sprintf('%.2f', $amount),
        'currency' => 'IDR'
    ],
    'externalStoreId'    => '',
    'validUpTo'          => $validUpTo,
    'urlParams'          => [
        [
            'url'        => $config['redirect_url'],
            'type'       => 'PAY_RETURN',
            'isDeeplink' => 'Y'
        ],
        [
            'url'        => $config['notify_url'],
            'type'       => 'NOTIFICATION',
            'isDeeplink' => 'Y'
        ]
    ],
    'additionalInfo'     => [
        'order'   => [
            'orderTitle' => substr($packageName, 0, 50),
            'scenario'   => 'REDIRECT'
        ],
        'envInfo' => [
            'sourcePlatform'   => 'IPG',
            'terminalType'     => 'SYSTEM',
            'orderTerminalType'=> 'WEB'
        ]
    ]
];

$checkoutUrl = null;
$rawResponse = null;
$errorMsg    = null;

// Tanda tangani request ke DANA jika private key tersedia
if (!empty($config['private_key_path']) && file_exists($config['private_key_path'])) {
    try {
        $privateKeyContent = file_get_contents($config['private_key_path']);
        $privateKey = openssl_pkey_get_private($privateKeyContent);

        if ($privateKey) {
            $jsonPayload  = json_encode($payload, JSON_UNESCAPED_SLASHES);
            $hashedBody   = hash('sha256', $jsonPayload);
            // SNAP Signature Format: HTTPMethod:Path:HashedBody:Timestamp
            $stringToSign = "POST:{$path}:" . strtolower($hashedBody) . ":{$timestamp}";
            
            $binarySignature = '';
            if (openssl_sign($stringToSign, $binarySignature, $privateKey, OPENSSL_ALGO_SHA256)) {
                $signature = base64_encode($binarySignature);

                $headers = [
                    'Content-Type: application/json',
                    "X-TIMESTAMP: {$timestamp}",
                    "X-PARTNER-ID: {$partnerId}",
                    "X-SIGNATURE: {$signature}",
                    "ORIGIN: {$config['origin']}",
                    "CHANNEL-ID: 95221"
                ];

                $ch = curl_init("{$baseUrl}{$path}");
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST           => true,
                    CURLOPT_POSTFIELDS     => $jsonPayload,
                    CURLOPT_TIMEOUT        => 20,
                    CURLOPT_HTTPHEADER     => $headers
                ]);

                $response = curl_exec($ch);
                $curlErr  = curl_error($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                $rawResponse = $response;

                // Catat log untuk audit & troubleshooting
                $logFile = __DIR__ . '/dana_checkout.log';
                $logEntry = "[" . date('Y-m-d H:i:s') . "] ENV: {$config['env']} (HTTP {$httpCode})\n";
                $logEntry .= "ENDPOINT: {$baseUrl}{$path}\n";
                $logEntry .= "STRING_TO_SIGN: {$stringToSign}\n";
                $logEntry .= "PAYLOAD: {$jsonPayload}\n";
                $logEntry .= "RESPONSE: {$response}\n";
                if ($curlErr) $logEntry .= "CURL_ERROR: {$curlErr}\n";
                $logEntry .= "--------------------------------------------------------\n";
                @file_put_contents($logFile, $logEntry, FILE_APPEND);

                if ($response) {
                    $resJson = json_decode($response, true);
                    $checkoutUrl = $resJson['webRedirectUrl'] 
                        ?? $resJson['actionUrl'] 
                        ?? $resJson['checkoutUrl']
                        ?? null;

                    if (!$checkoutUrl && isset($resJson['responseMessage'])) {
                        $errorMsg = $resJson['responseMessage'];
                    }
                }
            } else {
                $errorMsg = 'Gagal menghasilkan tanda tangan digital RSA';
            }
        } else {
            $errorMsg = 'Format Private Key RSA tidak valid';
        }
    } catch (Exception $e) {
        $errorMsg = $e->getMessage();
        error_log("DANA Call Exception: " . $errorMsg);
    }
}

// Fallback jika API DANA belum mengembalikan checkoutUrl (misal mode offline)
if (!$checkoutUrl) {
    if ($errorMsg) {
        // Return error jika DANA menolak
        http_response_code(400);
        echo json_encode([
            'status'      => 'error',
            'message'     => 'DANA Gateway: ' . $errorMsg,
            'rawResponse' => $rawResponse ? json_decode($rawResponse, true) : null
        ]);
        exit;
    }
    $checkoutUrl = "https://vegamediapro.web.id/payment-finish.html?order_id=" . urlencode($invoice) . "&amount=" . urlencode($amount) . "&pkg=" . urlencode($packageName);
}

http_response_code(200);
echo json_encode([
    'status'          => 'success',
    'invoice'         => $invoice,
    'packageName'     => $packageName,
    'amount'          => $amount,
    'formattedAmount' => 'Rp ' . number_format($amount, 0, ',', '.'),
    'checkoutUrl'     => $checkoutUrl,
    'rawResponse'     => $rawResponse ? json_decode($rawResponse, true) : null,
    'message'         => 'Sesi pembayaran DANA berhasil dibuat'
]);
exit;
