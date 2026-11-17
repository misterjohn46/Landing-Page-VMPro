<?php
/**
 * doit.id Webhook Handler
 * Endpoint: https://vegamediapro.web.id/api/doit-notify.php
 */

header('Content-Type: application/json; charset=utf-8');

$config = require __DIR__ . '/doit-config.php';
$signingSecret = $config['signing_secret'];

// 1. Ambil raw body
$rawBody = file_get_contents('php://input');

// 2. Ambil header PayBridge-Signature
$signatureHeader = $_SERVER['HTTP_PAYBRIDGE_SIGNATURE'] ?? '';
if (!$signatureHeader && function_exists('getallheaders')) {
    $headers = getallheaders();
    foreach ($headers as $k => $v) {
        if (strcasecmp($k, 'PayBridge-Signature') === 0) {
            $signatureHeader = $v;
            break;
        }
    }
}

// Catat log untuk audit & debugging
$logFile = __DIR__ . '/doit_notify.log';
$logEntry = "[" . date('Y-m-d H:i:s') . "] METHOD: " . ($_SERVER['REQUEST_METHOD'] ?? 'POST') . "\n";
$logEntry .= "SIGNATURE: {$signatureHeader}\n";
$logEntry .= "BODY: {$rawBody}\n";

// 3. Verifikasi Signature
// Format header: PayBridge-Signature: t=1755612912,v1=5f8a...
parse_str(str_replace(',', '&', $signatureHeader), $p);

if (empty($p['t']) || empty($p['v1'])) {
    $logEntry .= "RESULT: 400 Missing signature parameters\n--------------------------------------------------------\n";
    @file_put_contents($logFile, $logEntry, FILE_APPEND);
    http_response_code(400);
    echo json_encode(['error' => 'Missing signature components']);
    exit;
}

$calculatedSignature = hash_hmac('sha256', $p['t'] . '.' . $rawBody, $signingSecret);

if (!hash_equals($calculatedSignature, $p['v1'])) {
    // Skenario 7 Onboarding: Tolak signature palsu dengan non-2xx
    $logEntry .= "RESULT: 401 Invalid signature\n--------------------------------------------------------\n";
    @file_put_contents($logFile, $logEntry, FILE_APPEND);
    http_response_code(401);
    echo json_encode(['error' => 'Invalid signature']);
    exit;
}

// 4. Parse Event JSON
$event = json_decode($rawBody, true);
if (!$event || !isset($event['id'])) {
    $logEntry .= "RESULT: 400 Invalid JSON or missing event id\n--------------------------------------------------------\n";
    @file_put_contents($logFile, $logEntry, FILE_APPEND);
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

$eventId   = $event['id'];
$eventType = $event['type'] ?? 'unknown';

// 5. Idempotency & Deduplikasi Event (Skenario 8 Onboarding: Tahan webhook ganda)
$dedupDir = __DIR__ . '/.cache';
if (!is_dir($dedupDir)) {
    @mkdir($dedupDir, 0755, true);
}
$eventCacheFile = $dedupDir . '/doit_event_' . preg_replace('/[^a-zA-Z0-9_-]/', '', $eventId);

$isDuplicate = file_exists($eventCacheFile);
if ($isDuplicate) {
    $logEntry .= "RESULT: 200 Duplicate event ignored (Dedup OK)\n--------------------------------------------------------\n";
    @file_put_contents($logFile, $logEntry, FILE_APPEND);
    http_response_code(200);
    echo json_encode([
        'status'  => 'success',
        'message' => 'Duplicate event acknowledged'
    ]);
    exit;
}

// Tandai event sudah diproses
@file_put_contents($eventCacheFile, date('c'));

// 6. Proses Event Bisnis
if (file_exists(__DIR__ . '/db.php')) {
    try {
        require_once __DIR__ . '/db.php';
        if (isset($pdo)) {
            // Pastikan tabel doit_transactions ada
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS doit_transactions (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    payment_id VARCHAR(64) NOT NULL UNIQUE,
                    reference VARCHAR(128) NOT NULL,
                    amount INT NOT NULL,
                    status VARCHAR(30) NOT NULL,
                    customer_name VARCHAR(100) NULL,
                    customer_phone VARCHAR(30) NULL,
                    raw_data LONGTEXT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX (reference)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");

            $data = $event['data'] ?? [];
            $paymentId = $data['id'] ?? null;
            $reference = $data['reference'] ?? null;
            $status    = $data['status'] ?? 'pending';
            $amount    = (int) ($data['amount'] ?? 0);
            $custName  = $data['customer']['name'] ?? null;
            $custPhone = $data['customer']['phone'] ?? null;

            if ($paymentId && $reference) {
                $stmt = $pdo->prepare("
                    INSERT INTO doit_transactions 
                    (payment_id, reference, amount, status, customer_name, customer_phone, raw_data, updated_at)
                    VALUES 
                    (:payment_id, :reference, :amount, :status, :cust_name, :cust_phone, :raw_data, NOW())
                    ON DUPLICATE KEY UPDATE 
                    status = VALUES(status),
                    raw_data = VALUES(raw_data),
                    updated_at = NOW()
                ");
                $stmt->execute([
                    ':payment_id'  => $paymentId,
                    ':reference'   => $reference,
                    ':amount'      => $amount,
                    ':status'      => strtoupper($status),
                    ':cust_name'   => $custName,
                    ':cust_phone'  => $custPhone,
                    ':raw_data'    => json_encode($data, JSON_UNESCAPED_SLASHES)
                ]);
            }
        }
    } catch (Exception $e) {
        $logEntry .= "DB ERROR: " . $e->getMessage() . "\n";
    }
}

$logEntry .= "RESULT: 200 Success ({$eventType})\n--------------------------------------------------------\n";
@file_put_contents($logFile, $logEntry, FILE_APPEND);

// 7. Respon 200 OK ke doit.id
http_response_code(200);
echo json_encode([
    'status'    => 'success',
    'event_id'  => $eventId,
    'type'      => $eventType
]);
exit;
