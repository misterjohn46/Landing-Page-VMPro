<?php
/**
 * doit.id Checkout Endpoint
 * Vega MediaPro (https://vegamediapro.web.id/api/doit-checkout.php)
 */

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

$input = file_get_contents('php://input');
$data  = json_decode($input, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON input']);
    exit;
}

$packageName = trim($data['packageName'] ?? 'Paket Layanan Software');
$amount      = (int) round((float) ($data['price'] ?? 0));
$custName    = trim($data['name'] ?? 'Pelanggan Vega MediaPro');
$custEmail   = trim($data['email'] ?? 'studio46probolinggo@gmail.com');
$custPhone   = trim($data['phone'] ?? '082335338113');
$rail        = trim($data['rail'] ?? 'any');
$invoice     = trim($data['invoice'] ?? ('VMP-' . strtoupper(substr(uniqid(), -8))));

if ($amount <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Nominal pesanan tidak valid']);
    exit;
}

$config = require __DIR__ . '/doit-config.php';
$apiKey = $config['api_key'];
$baseUrl = rtrim($config['base_url'], '/');

// Siapkan payload ke doit.id
$payload = [
    'amount'     => $amount,
    'rail'       => $rail,
    'reference'  => $invoice,
    'customer'   => [
        'name'  => $custName,
        'phone' => $custPhone,
    ],
    'return_url' => $config['return_url'] . '?order_id=' . urlencode($invoice) . '&amount=' . urlencode($amount) . '&pkg=' . urlencode($packageName) . '&gateway=doit',
    'metadata'   => [
        'packageName'   => $packageName,
        'customerEmail' => $custEmail,
    ]
];

$jsonPayload = json_encode($payload, JSON_UNESCAPED_SLASHES);

$ch = curl_init("{$baseUrl}/v1/payments");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $jsonPayload,
    CURLOPT_TIMEOUT        => 20,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        "Authorization: Bearer {$apiKey}",
        "Idempotency-Key: {$invoice}"
    ]
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr  = curl_error($ch);
curl_close($ch);

// Catat log
$logFile = __DIR__ . '/doit_checkout.log';
$logEntry = "[" . date('Y-m-d H:i:s') . "] POST /v1/payments (HTTP {$httpCode})\n";
$logEntry .= "PAYLOAD: {$jsonPayload}\n";
$logEntry .= "RESPONSE: {$response}\n";
if ($curlErr) $logEntry .= "CURL_ERROR: {$curlErr}\n";
$logEntry .= "--------------------------------------------------------\n";
@file_put_contents($logFile, $logEntry, FILE_APPEND);

if ($httpCode >= 200 && $httpCode < 300) {
    $resJson = json_decode($response, true);
    
    // Simpan ke DB lokal jika tersedia
    if (file_exists(__DIR__ . '/db.php')) {
        try {
            require_once __DIR__ . '/db.php';
            if (isset($pdo)) {
                $stmt = $pdo->prepare("
                    INSERT INTO doit_transactions 
                    (payment_id, reference, amount, status, customer_name, customer_phone, raw_data, created_at)
                    VALUES 
                    (:payment_id, :reference, :amount, 'PENDING', :cust_name, :cust_phone, :raw_data, NOW())
                    ON DUPLICATE KEY UPDATE updated_at = NOW()
                ");
                $stmt->execute([
                    ':payment_id'  => $resJson['id'] ?? $invoice,
                    ':reference'   => $invoice,
                    ':amount'      => $amount,
                    ':cust_name'   => $custName,
                    ':cust_phone'  => $custPhone,
                    ':raw_data'    => $response
                ]);
            }
        } catch (Exception $e) {
            error_log("DB insert failed: " . $e->getMessage());
        }
    }

    http_response_code(200);
    echo json_encode([
        'status'          => 'success',
        'id'              => $resJson['id'] ?? null,
        'invoice'         => $invoice,
        'packageName'     => $packageName,
        'amount'          => $amount,
        'formattedAmount' => 'Rp ' . number_format($amount, 0, ',', '.'),
        'hosted_url'      => $resJson['hosted_url'] ?? null,
        'qr_content'      => $resJson['qr_content'] ?? null,
        'va_number'       => $resJson['va_number'] ?? null,
        'raw'             => $resJson
    ]);
    exit;
} else {
    http_response_code($httpCode ?: 400);
    $resJson = json_decode($response, true);
    echo json_encode([
        'status'  => 'error',
        'message' => $resJson['message'] ?? 'Gagal membuat pembayaran di doit.id',
        'raw'     => $resJson ?: $response
    ]);
    exit;
}
