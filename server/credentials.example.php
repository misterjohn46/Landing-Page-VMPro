<?php
/**
 * Contoh konfigurasi kredensial Duitku.
 *
 * Cara pakai:
 *   1. Salin file ini menjadi `credentials.local.php` di folder yang sama.
 *   2. Isi nilainya dengan kredensial dari dashboard Duitku.
 *   3. JANGAN commit `credentials.local.php` (sudah masuk .gitignore).
 *
 * Alternatif yang lebih disarankan: set environment variable di hosting
 * (DUITKU_MERCHANT_CODE, DUITKU_API_KEY, DUITKU_PRODUCTION, APP_BASE_URL)
 * dan biarkan file ini tidak dibuat sama sekali.
 */

return [
    'merchantCode' => 'ISI_MERCHANT_CODE_ANDA',
    'apiKey'       => 'ISI_API_KEY_ANDA',
    'isProduction' => false, // true untuk key production
    'baseUrl'      => 'https://domain-anda.com',
];
