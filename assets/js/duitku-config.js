/**
 * Konfigurasi Duitku Payment Gateway Sandbox
 * Anda dapat mengisi Merchant Code dan API Key Sandbox Anda di sini.
 * Data ini aman karena hanya digunakan untuk lingkungan Sandbox (Uji Coba).
 */
const DUITKU_CONFIG = {
  isSandbox: true,
  // Masukkan Merchant Code Sandbox dari dashboard Duitku Anda (contoh: D1234)
  merchantCode: "DXXXX", 
  // Masukkan API Key Sandbox dari dashboard Duitku Anda
  apiKey: "xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
  // URL Sandbox Duitku
  sandboxEndpoint: "https://sandbox.duitku.com/webapi/api/merchant/v2/inquiry"
};
