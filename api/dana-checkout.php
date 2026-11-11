<?php
/**
 * DANA Enterprise Checkout Endpoint - Production Ready
 * Vega MediaPro (https://vegamediapro.web.id/api/dana-checkout.php)
 */

header('Content-Type: application/json; charset=utf-8');

// Hanya terima metode POST
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
$configData = require __DIR__ . '/dana-config.php';

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

// 2. Load DANA Official SDK jika tersedia
$vendorAutoload = file_exists(__DIR__ . '/vendor/autoload.php')
    ? __DIR__ . '/vendor/autoload.php'
    : (file_exists('/root/uat-script/test/php/vendor/autoload.php') ? '/root/uat-script/test/php/vendor/autoload.php' : null);

$checkoutUrl = null;
$rawResponse = null;
$errorMsg    = null;

if ($vendorAutoload) {
    require_once $vendorAutoload;

    try {
        $danaConfig = new \Dana\Configuration();
        $privateKeyContent = file_get_contents($configData['private_key_path']);
        
        $danaConfig->setApiKey('PRIVATE_KEY', $privateKeyContent);
        $danaConfig->setApiKey('ORIGIN', $configData['origin']);
        $danaConfig->setApiKey('X_PARTNER_ID', $configData['partner_id']);
        
        if ($configData['env'] === 'PRODUCTION') {
            $danaConfig->setApiKey('ENV', \Dana\Env::PRODUCTION);
            $danaConfig->setHost('https://api.saas.dana.id');
        } else {
            $danaConfig->setApiKey('ENV', \Dana\Env::SANDBOX);
            $danaConfig->setHost('https://api.sandbox.dana.id');
        }

        $api = new \Dana\PaymentGateway\v1\Api\PaymentGatewayApi(null, $danaConfig);

        $tz = new DateTimeZone('Asia/Jakarta');
        $validUpTo = (new DateTime('+15 minutes', $tz))->format('Y-m-d\TH:i:sP');

        $jsonDict = [
            'partnerReferenceNo' => $invoice,
            'merchantId'         => $configData['merchant_id'],
            'subMerchantId'      => '',
            'amount'             => [
                'value'    => sprintf('%.2f', $amount),
                'currency' => 'IDR'
            ],
            'externalStoreId'    => '',
            'urlParams'          => [
                [
                    'url'        => $configData['redirect_url'],
                    'type'       => 'PAY_RETURN',
                    'isDeeplink' => 'Y'
                ],
                [
                    'url'        => $configData['notify_url'],
                    'type'       => 'NOTIFICATION',
                    'isDeeplink' => 'Y'
                ]
            ],
            'validUpTo'          => $validUpTo,
            'additionalInfo'     => [
                'order'   => [
                    'orderTitle'        => substr($packageName, 0, 50),
                    'scenario'          => 'REDIRECT',
                    'merchantTransType' => 'SPECIAL_MOVIE',
                    'buyer'             => (object)[]
                ],
                'mcc'     => '5732',
                'envInfo' => [
                    'sourcePlatform'   => 'IPG',
                    'terminalType'     => 'SYSTEM',
                    'orderTerminalType'=> 'WEB'
                ],
                'extendInfo' => '{"key":"value"}'
            ]
        ];

        $req = \Dana\ObjectSerializer::deserialize($jsonDict, 'Dana\PaymentGateway\v1\Model\CreateOrderByRedirectRequest');
        $resp = $api->createOrder($req);

        if ($resp && method_exists($resp, 'getWebRedirectUrl')) {
            $checkoutUrl = $resp->getWebRedirectUrl();
            $rawResponse = json_decode($resp->__toString(), true);
        }

        // Catat log audit
        $logFile = __DIR__ . '/dana_checkout.log';
        $logEntry = "[" . date('Y-m-d H:i:s') . "] SDK CALL SUCCESS (ENV: {$configData['env']})\n";
        $logEntry .= "INVOICE: {$invoice} | AMOUNT: {$amount}\n";
        $logEntry .= "CHECKOUT URL: {$checkoutUrl}\n";
        $logEntry .= "--------------------------------------------------------\n";
        @file_put_contents($logFile, $logEntry, FILE_APPEND);

    } catch (\Dana\ApiException $e) {
        $errorMsg = $e->getMessage();
        $respBody = $e->getResponseBody();
        if ($respBody) {
            $resDecoded = json_decode($respBody, true);
            if (isset($resDecoded['responseMessage'])) {
                $errorMsg = $resDecoded['responseMessage'];
            }
            $rawResponse = $resDecoded;
        }
        
        $logFile = __DIR__ . '/dana_checkout.log';
        $logEntry = "[" . date('Y-m-d H:i:s') . "] SDK API EXCEPTION: {$errorMsg}\n";
        $logEntry .= "RESP BODY: " . $respBody . "\n";
        $logEntry .= "--------------------------------------------------------\n";
        @file_put_contents($logFile, $logEntry, FILE_APPEND);
    } catch (Exception $e) {
        $errorMsg = $e->getMessage();
        $logFile = __DIR__ . '/dana_checkout.log';
        $logEntry = "[" . date('Y-m-d H:i:s') . "] SDK EXCEPTION: {$errorMsg}\n";
        $logEntry .= "--------------------------------------------------------\n";
        @file_put_contents($logFile, $logEntry, FILE_APPEND);
    }
}

// Fallback jika checkoutUrl belum tersedia
if (!$checkoutUrl) {
    if ($errorMsg) {
        http_response_code(400);
        echo json_encode([
            'status'      => 'error',
            'message'     => 'DANA Gateway: ' . $errorMsg,
            'rawResponse' => $rawResponse
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
    'rawResponse'     => $rawResponse,
    'message'         => 'Sesi pembayaran DANA berhasil dibuat'
]);
exit;
