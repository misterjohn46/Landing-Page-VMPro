<?php
/**
 * DANA Enterprise Configuration
 * Vega MediaPro (https://vegamediapro.web.id)
 */

if (file_exists(__DIR__ . '/db.php')) {
    require_once __DIR__ . '/db.php';
}

$dbEnabled    = function_exists('get_setting') ? get_setting('dana_enabled', '1') : '1';
$dbEnv        = function_exists('get_setting') ? get_setting('dana_env', 'PRODUCTION') : 'PRODUCTION';
$dbMerchantId = function_exists('get_setting') ? get_setting('dana_merchant_id') : null;
$dbClientId   = function_exists('get_setting') ? get_setting('dana_client_id') : null;

return [
    'enabled'     => $dbEnabled === '1',
    'env'         => $dbEnv ?: 'PRODUCTION',
    'merchant_id' => $dbMerchantId ?: '216620010011034337246',
    'partner_id'  => $dbClientId ?: '2026111112325220504763',
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
