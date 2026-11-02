<?php
/**
 * DANA Enterprise Webhook / Finish Notify Handler
 * Endpoint: https://vegamediapro.web.id/api/dana-notify.php
 * Also handles: /v1.0/debit/notify
 */

header('Content-Type: application/json; charset=utf-8');

// Ambil raw input dari DANA
$rawInput = file_get_contents('php://input');
$headers = getallheaders();

// Catat log untuk audit di Sandbox
$logFile = __DIR__ . '/dana_notify.log';
$logEntry = "[" . date('Y-m-d H:i:s') . "] " . ($_SERVER['REQUEST_METHOD'] ?? 'POST') . " " . ($_SERVER['REQUEST_URI'] ?? '') . "\n";
$logEntry .= "PAYLOAD: " . $rawInput . "\n";
$logEntry .= "--------------------------------------------------------\n";
@file_put_contents($logFile, $logEntry, FILE_APPEND);

// Update status jika database aktif
if (file_exists(__DIR__ . '/db.php')) {
    try {
        require_once __DIR__ . '/db.php';
        $data = json_decode($rawInput, true);
        if (isset($pdo) && $data) {
            $transId = $data['originalPartnerReferenceNo'] ?? $data['partnerReferenceNo'] ?? $data['merchantTransId'] ?? $data['orderId'] ?? null;
            $status = $data['latestTransactionStatus'] ?? $data['paymentStatus'] ?? '00';
            
            if ($transId) {
                $stmt = $pdo->prepare("UPDATE dana_transactions SET status = :status, raw_response = :raw, updated_at = NOW() WHERE order_id = :order_id");
                $stmt->execute([
                    ':status' => ($status === '05' ? 'CANCELLED' : ($status === '00' ? 'PAID' : $status)),
                    ':raw' => $rawInput,
                    ':order_id' => $transId
                ]);
            }
        }
    } catch (Exception $e) {
        // Abaikan error DB agar respons webhook tetap 200 OK
    }
}

// Kembalikan respons resmi SNAP BI yang diharapkan DANA
http_response_code(200);
echo json_encode([
    'responseCode' => '2005600',
    'responseMessage' => 'Successful'
], JSON_UNESCAPED_SLASHES);
exit;
