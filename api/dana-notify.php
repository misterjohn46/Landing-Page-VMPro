<?php
/**
 * DANA Enterprise Webhook / Finish Notify Handler
 * Endpoint: https://vegamediapro.web.id/api/dana-notify.php
 */

header('Content-Type: application/json; charset=utf-8');

// Ambil raw input dari DANA
$rawInput = file_get_contents('php://input');
$headers = getallheaders();

// Catat log untuk keperluan audit dan debugging di Sandbox
$logFile = __DIR__ . '/dana_notify.log';
$logEntry = "[" . date('Y-m-d H:i:s') . "] IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN') . "\n";
$logEntry .= "HEADERS: " . json_encode($headers) . "\n";
$logEntry .= "PAYLOAD: " . $rawInput . "\n";
$logEntry .= "--------------------------------------------------------\n";
file_put_contents($logFile, $logEntry, FILE_APPEND);

// Parse JSON payload jika ada
$data = json_decode($rawInput, true);

// Update status jika database sudah dikonfigurasi
if (file_exists(__DIR__ . '/db.php')) {
    try {
        require_once __DIR__ . '/db.php';
        if (isset($pdo) && $data) {
            // Tangkap nomor referensi / order ID
            $transId = $data['merchantTransId'] ?? $data['originalPartnerReferenceNo'] ?? $data['orderId'] ?? null;
            $status = $data['paymentStatus'] ?? $data['latestTransactionStatus'] ?? $data['status'] ?? 'SUCCESS';
            
            if ($transId) {
                $stmt = $pdo->prepare("UPDATE dana_transactions SET status = :status, raw_response = :raw, updated_at = NOW() WHERE order_id = :order_id");
                $stmt->execute([
                    ':status' => $status,
                    ':raw' => $rawInput,
                    ':order_id' => $transId
                ]);
            }
        }
    } catch (Exception $e) {
        error_log("DANA DB Error: " . $e->getMessage());
    }
}

// Kembalikan respons standar sukses ke DANA (DANA Open API & SNAP BI kompatibel)
http_response_code(200);
echo json_encode([
    'responseCode' => '2005600',
    'responseMessage' => 'Successful',
    'response' => [
        'head' => [
            'version' => '2.0',
            'function' => 'dana.payment.finishNotify',
            'respTime' => date('Y-m-d\TH:i:sP'),
            'status' => 'SUCCESS'
        ],
        'body' => [
            'resultInfo' => [
                'resultStatus' => 'S',
                'resultCodeId' => '00000000',
                'resultCode' => 'SUCCESS',
                'resultMsg' => 'Success'
            ]
        ]
    ]
]);
exit;
