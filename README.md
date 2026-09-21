# Landing Page Vega MediaPro (VMPro)

Landing page modern dan responsif untuk usaha jasa pembuatan aplikasi perangkat lunak (Software Development), spesialisasi:
1. **Aplikasi Kasir / Point of Sale (POS)** untuk toko retail, cafe, resto, apotek, dan bengkel.
2. **Sistem Informasi Sekolah (SIMS / SIS)** untuk SD, SMP, SMA, SMK, madrasah, dan pesantren.
3. **Aplikasi Web & Mobile Kustom** sesuai kebutuhan bisnis.

---

## 🌟 Fitur Utama Website
- **Modern Tech Agency Design**: Menggunakan Tailwind CSS, Glassmorphism card effects, ambient gradients, dan font Plus Jakarta Sans.
- **100% Responsif**: Tampilan optimal di smartphone, tablet, maupun layar desktop.
- **Interactive Showcase Preview**: Tab interaktif untuk melihat preview dashboard POS Kasir dan SIM Sekolah.
- **Interactive Project Estimator & WhatsApp Generator**: Pengunjung dapat memilih kategori aplikasi dan fitur yang dibutuhkan, lalu mengklik tombol untuk langsung terhubung ke WhatsApp dengan pesan template rapi.
- **Alur Pemesanan Transparan**: Tombol paket harga membuka formulir pesanan → ringkasan → kirim ke WhatsApp. Halaman ini **tidak memproses pembayaran**; invoice dan instruksi pembayaran dikirim manual oleh tim.
- **Dual Support (HTML & PHP)**:
  - `index.html`: Siap dibuka langsung atau di-hosting di layanan static web (GitHub Pages, Vercel, Netlify, Cloudflare Pages).
  - `index.php`: Siap dijalankan di hosting PHP / cPanel / Laragon / XAMPP dengan variabel konfigurasi di baris teratas.

---

## 🚀 Cara Menjalankan

### Opsi 1: Buka Langsung (Tanpa Server)
Cukup klik ganda file `index.html` pada browser favorit Anda.

### Opsi 2: Menggunakan PHP Built-in Server
Buka terminal pada folder proyek ini, lalu jalankan:
```bash
php -S localhost:8000
```
Lalu buka peramban di `http://localhost:8000`.

---

## ⚙️ Konfigurasi Kontak & Brand
- Pada **`index.php`**: Ubah variabel `$whatsapp_num` dan `$brand_name` pada bagian atas file.
- Pada **`assets/js/main.js`**: Ubah nilai `WHATSAPP_NUMBER` jika menggunakan `index.html`.

---

## 🔐 Catatan Keamanan Pembayaran Online

Situs ini dipublikasikan lewat **GitHub Pages**, yang bersifat statis dan **tidak mengeksekusi PHP**. File `.php` yang berada di folder yang dipublikasikan akan disajikan sebagai *source code mentah* dan bisa diunduh siapa pun — karena itu:

- Kredensial payment gateway **tidak boleh** ditulis di dalam kode, baik di PHP maupun JavaScript.
- Folder `server/` dan `index.php` dikecualikan dari publikasi melalui `_config.yml`.
- Alur checkout di halaman ini berhenti pada pengiriman data pesanan ke WhatsApp. Tidak ada transaksi yang diproses di browser.

### Jika ingin mengaktifkan pembayaran online (Duitku)

1. Pindahkan folder `server/` ke hosting yang mengeksekusi PHP (cPanel / VPS), bukan GitHub Pages.
2. Set environment variable berikut di hosting tersebut:
   - `DUITKU_MERCHANT_CODE`
   - `DUITKU_API_KEY`
   - `DUITKU_PRODUCTION` (`true` untuk key production)
   - `APP_BASE_URL` (contoh: `https://vegamediapro.web.id`)

   Alternatif untuk hosting yang tidak mendukung env var: salin `server/credentials.example.php` menjadi `server/credentials.local.php` (file ini sudah di-`.gitignore`).
3. Daftarkan URL callback `https://domain-anda.com/server/duitku-callback.php` di dashboard Duitku.
4. Harga paket dikunci di server pada array `$PACKAGES` di `server/duitku-checkout.php`. Frontend hanya mengirim `packageId`, **bukan** nominal.

> ⚠️ Merchant Code dan API Key sandbox yang sebelumnya ter-commit di repository ini sudah terekspos publik. **Rotasi/ganti kredensial tersebut di dashboard Duitku** sebelum digunakan lagi.

---

## 📄 Lisensi
Hak Cipta &copy; 2026 Vega MediaPro. Seluruh hak cipta dilindungi.
