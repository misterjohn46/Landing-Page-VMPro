<?php
/**
 * doit.id Payment Gateway Configuration
 * Vega MediaPro (https://vegamediapro.web.id)
 */

if (file_exists(__DIR__ . '/db.php')) {
    require_once __DIR__ . '/db.php';
}

$dbKey = function_exists('get_setting') ? get_setting('doit_api_key') : null;
$dbSec = function_exists('get_setting') ? get_setting('doit_signing_secret') : null;
$dbEnabled = function_exists('get_setting') ? get_setting('doit_enabled', '1') : '1';

return [
    'enabled' => $dbEnabled === '1',

    // API Key (pb_test_... untuk Sandbox, pb_live_... untuk Produksi)
    'api_key' => $dbKey ?: (getenv('DOIT_API_KEY') ?: 'pb_test_a9ad11ecb573b60a2242c75c2f325b9302c69921b5d43614'),
    
    // Webhook Signing Secret (dari menu Developer/Webhook)
    'signing_secret' => $dbSec ?: (getenv('DOIT_SIGNING_SECRET') ?: 'whs_77615d422f8a1341ce00706fcf0cf30e785bab4bf8efbcc6'),
    
    // Base URL doit.id (sama untuk Sandbox dan Produksi)
    'base_url' => 'https://pay.doit.id',
    
    // Return & Notify URLs
    'return_url' => 'https://vegamediapro.web.id/payment-finish.html',
    'notify_url' => 'https://vegamediapro.web.id/api/doit-notify.php',
];
