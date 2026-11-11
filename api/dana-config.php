<?php
/**
 * DANA Enterprise Configuration - PRODUCTION
 * Vega MediaPro (https://vegamediapro.web.id)
 */

return [
    'env' => 'PRODUCTION', // Mode Live Production Aktif
    'merchant_id' => '216620010011034337246', // Production Merchant ID
    'partner_id'  => '2026111112325220504763', // Production Client ID / X-Partner-ID
    'origin'      => 'https://vegamediapro.web.id',
    
    // URL API
    'sandbox_url'    => 'https://api.sandbox.dana.id',
    'production_url' => 'https://api.saas.dana.id',
    
    // Redirect & Webhook URLs
    'redirect_url' => 'https://vegamediapro.web.id/payment-finish.html',
    'notify_url'   => 'https://vegamediapro.web.id/api/dana-notify.php',
    
    // Path Kunci RSA Produksi
    'private_key_path' => file_exists(__DIR__ . '/production_private_key.pem') 
        ? __DIR__ . '/production_private_key.pem' 
        : (file_exists('/root/production_keys/private_key.pem') 
            ? '/root/production_keys/private_key.pem' 
            : __DIR__ . '/private_key.pem'),
        
    'public_key_path' => file_exists(__DIR__ . '/production_public_key.pem') 
        ? __DIR__ . '/production_public_key.pem' 
        : (file_exists('/root/production_keys/public_key.pem') 
            ? '/root/production_keys/public_key.pem' 
            : __DIR__ . '/public_key.pem'),
];
