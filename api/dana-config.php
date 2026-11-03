<?php
/**
 * DANA Enterprise Configuration
 * Vega MediaPro (https://vegamediapro.web.id)
 */

return [
    'env' => getenv('DANA_ENV') ?: 'SANDBOX', // SANDBOX atau PRODUCTION
    'merchant_id' => getenv('DANA_MERCHANT_ID') ?: '216620010001053576210',
    'partner_id'  => getenv('DANA_PARTNER_ID')  ?: '2026110301432401037140',
    'origin'      => getenv('DANA_ORIGIN')      ?: 'https://vegamediapro.web.id',
    
    // URL API
    'sandbox_url'    => 'https://api.sandbox.dana.id',
    'production_url' => 'https://api.saas.dana.id',
    
    // Redirect & Webhook URLs
    'redirect_url' => 'https://vegamediapro.web.id/payment-finish.html',
    'notify_url'   => 'https://vegamediapro.web.id/api/dana-notify.php',
    
    // Path Kunci RSA (Private Key untuk Sign & Public Key DANA untuk Verifikasi)
    'private_key_path' => file_exists(__DIR__ . '/private_key.pem') 
        ? __DIR__ . '/private_key.pem' 
        : (file_exists('/root/uat-script/private_key.pem') ? '/root/uat-script/private_key.pem' : null),
        
    'public_key_path' => file_exists(__DIR__ . '/public_key.pem') 
        ? __DIR__ . '/public_key.pem' 
        : (file_exists('/root/uat-script/public_key.pem') ? '/root/uat-script/public_key.pem' : null),
];
