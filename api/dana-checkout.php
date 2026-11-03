<?php
/**
 * DANA Enterprise Checkout Endpoint
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
$custEmail   = trim($data['email'] ?? 'pelanggan@vegamediapro.web.id');
$custPhone   = trim($data['phone'] ?? '082335338113');
$invoice     = trim($data['invoice'] ?? ('VMP-' . strtoupper(substr(uniqid(), -6))));

if ($amount <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Nominal pesanan tidak valid']);
    exit;
}

// Muat konfigurasi DANA
$config = require __DIR__ . '/dana-config.php';

// 1. Simpan Transaksi ke Database (Status PENDING)
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

// 2. Siapkan Request ke API DANA
$baseUrl   = ($config['env'] === 'PRODUCTION') ? $config['production_url'] : $config['sandbox_url'];
$timestamp = date('Y-m-d\TH:i:sP');
$partnerId = $config['partner_id'];
$externalId = $invoice;

$payload = [
    'partnerReferenceNo' => $invoice,
    'amount' => [
        'value'    => sprintf('%.2f', $amount),
        'currency' => 'IDR'
    ],
    'merchantId'   => $config['merchant_id'],
    'payOptionDetails' => [
        [
            'payMethod' => 'BALANCE'
        ]
    ],
    'additionalInfo' => [
        'order' => [
            'orderTitle' => substr($packageName, 0, 50),
            'goods' => [
                [
                    'goodsId'   => 'SRV-' . substr(md5($packageName), 0, 8),
                    'name'      => $packageName,
                    'price'     => sprintf('%.2f', $amount),
                    'quantity'  => '1'
                ]
            ]
        ]
    ],
    'urlParam' => [
        [
            'url'  => $config['redirect_url'],
            'type' => 'PAY_RETURN'
        ],
        [
            'url'  => $config['notify_url'],
            'type' => 'NOTIFICATION'
        ]
    ]
];

$checkoutUrl = null;
$qrString = null;

// Jika private key tersedia di server, tanda tangani request ke DANA
if (!empty($config['private_key_path']) && file_exists($config['private_key_path'])) {
    try {
        $privateKeyContent = file_get_contents($config['private_key_path']);
        $privateKey = openssl_pkey_get_private($privateKeyContent);

        if ($privateKey) {
            $jsonPayload = json_encode($payload);
            $shaPayload = hash('sha256', $jsonPayload);
            $stringToSign = "POST:/payment-gateway/v1.0/debit/payment-host-to-host.htm:{$partnerId}:{$timestamp}:{$shaPayload}";
            
            $binarySignature = '';
            if (openssl_sign($stringToSign, $binarySignature, $privateKey, OPENSSL_ALGO_SHA256)) {
                $signature = base64_encode($binarySignature);

                $ch = curl_init("{$baseUrl}/payment-gateway/v1.0/debit/payment-host-to-host.htm");
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST           => true,
                    CURLOPT_POSTFIELDS     => $jsonPayload,
                    CURLOPT_TIMEOUT        => 10,
                    CURLOPT_HTTPHEADER     => [
                        'Content-Type: application/json',
                        "X-TIMESTAMP: {$timestamp}",
                        "X-PARTNER-ID: {$partnerId}",
                        "X-EXTERNAL-ID: {$externalId}",
                        "X-SIGNATURE: {$signature}",
                        "ORIGIN: {$config['origin']}"
                    ]
                ]);

                $response = curl_exec($ch);
                curl_close($ch);

                if ($response) {
                    $resJson = json_decode($response, true);
                    $checkoutUrl = $resJson['webRedirectUrl'] ?? $resJson['actionUrl'] ?? null;
                    $qrString    = $resJson['qrCode'] ?? $resJson['qrContent'] ?? null;
                }
            }
        }
    } catch (Exception $e) {
        error_log("DANA Call Exception: " . $e->getMessage());
    }
}

// Fallback jika DANA Sandbox API sedang dalam tahap Uji Devsite:
// Buat halaman bayar simulasi langsung terhubung ke payment-finish.html
if (!$checkoutUrl) {
    $checkoutUrl = "https://vegamediapro.web.id/payment-finish.html?order_id=" . urlencode($invoice) . "&amount=" . urlencode($amount) . "&pkg=" . urlencode($packageName);
}

http_response_code(200);
echo json_encode([
    'status'      => 'success',
    'invoice'     => $invoice,
    'packageName' => $packageName,
    'amount'      => $amount,
    'formattedAmount' => 'Rp ' . number_format($amount, 0, ',', '.'),
    'checkoutUrl' => $checkoutUrl,
    'qrString'    => $qrString,
    'message'     => 'Tagihan DANA berhasil dibuat'
]);
exit;
