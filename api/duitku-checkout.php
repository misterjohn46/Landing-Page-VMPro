<?php
/**
 * Duitku Sandbox Checkout Handler (PHP Backend)
 * Endpoint ini menerima request dari form checkout dan membuat transaksi di Sandbox Duitku
 */

header('Content-Type: application/json');

// Konfigurasi Sandbox Duitku
$merchantCode = "DXXXX"; // Ganti dengan Merchant Code Sandbox Anda
$apiKey       = "xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"; // Ganti dengan API Key Sandbox Anda
$sandboxUrl   = "https://sandbox.duitku.com/webapi/api/merchant/v2/inquiry";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    $paymentAmount   = isset($input['amount']) ? (int)$input['amount'] : 500000;
    $email           = isset($input['email']) ? filter_var($input['email'], FILTER_SANITIZE_EMAIL) : 'customer@example.com';
    $phoneNumber     = isset($input['phone']) ? $input['phone'] : '082335338113';
    $productDetails  = isset($input['packageName']) ? $input['packageName'] : 'DP Jasa Pembuatan Aplikasi';
    $merchantOrderId = 'VMP-' . time();
    $paymentMethod   = isset($input['paymentMethod']) ? $input['paymentMethod'] : 'VC'; // Default QRIS / VA

    // Signature Formula Duitku: MD5(merchantCode + merchantOrderId + paymentAmount + apiKey)
    $signature = md5($merchantCode . $merchantOrderId . $paymentAmount . $apiKey);

    $params = array(
        'merchantCode'    => $merchantCode,
        'paymentAmount'   => $paymentAmount,
        'paymentMethod'   => $paymentMethod,
        'merchantOrderId' => $merchantOrderId,
        'productDetails'  => $productDetails,
        'additionalParam' => '',
        'merchantUserInfo'=> '',
        'customerVaName'  => isset($input['name']) ? $input['name'] : 'Pelanggan Vega MediaPro',
        'email'           => $email,
        'phoneNumber'     => $phoneNumber,
        'callbackUrl'     => 'https://vegamediapro.web.id/callback.php',
        'returnUrl'       => 'https://vegamediapro.web.id/return.php',
        'signature'       => $signature,
        'expiryPeriod'    => 1440
    );

    $params_string = json_encode($params);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $sandboxUrl);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $params_string);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Content-Length: ' . strlen($params_string)
    ));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

    $response = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);

    if ($err) {
        echo json_encode(array('status' => 'error', 'message' => $err));
    } else {
        echo $response;
    }
    exit;
}

echo json_encode(array('status' => 'error', 'message' => 'Invalid request method'));
