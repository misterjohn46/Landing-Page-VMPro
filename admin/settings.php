<?php
$pageTitle = 'Pengaturan Gateway & Akun';
require_once __DIR__ . '/header.php';

$successMsg = '';
$errorMsg = '';

// Proses form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'save_doit') {
        $doitEnabled = isset($_POST['doit_enabled']) ? '1' : '0';
        $doitApiKey = trim($_POST['doit_api_key'] ?? '');
        $doitSigningSec = trim($_POST['doit_signing_secret'] ?? '');

        set_setting('doit_enabled', $doitEnabled);
        set_setting('doit_api_key', $doitApiKey);
        set_setting('doit_signing_secret', $doitSigningSec);

        $successMsg = 'Pengaturan gateway doit.id berhasil diperbarui.';
    }

    if ($action === 'save_dana') {
        $danaEnabled = isset($_POST['dana_enabled']) ? '1' : '0';
        $danaEnv = trim($_POST['dana_env'] ?? 'PRODUCTION');
        $danaMerchantId = trim($_POST['dana_merchant_id'] ?? '');
        $danaClientId = trim($_POST['dana_client_id'] ?? '');

        set_setting('dana_enabled', $danaEnabled);
        set_setting('dana_env', $danaEnv);
        set_setting('dana_merchant_id', $danaMerchantId);
        set_setting('dana_client_id', $danaClientId);

        $successMsg = 'Pengaturan gateway DANA Enterprise berhasil disimpan.';
    }

    if ($action === 'change_profile') {
        $newName = trim($_POST['name'] ?? '');
        $newUsername = trim($_POST['username'] ?? '');

        if (empty($newUsername)) {
            $errorMsg = 'Username tidak boleh kosong.';
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE admin_users SET name = :n, username = :u WHERE id = :id");
                $stmt->execute([
                    ':n' => $newName ?: 'Administrator',
                    ':u' => $newUsername,
                    ':id' => $_SESSION['admin_id'],
                ]);
                $successMsg = 'Profil admin berhasil diperbarui.';
                // Refresh admin data
                $admin = get_logged_admin();
            } catch (Exception $e) {
                $errorMsg = 'Gagal menyimpan profil: ' . $e->getMessage();
            }
        }
    }

    if ($action === 'change_password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($currentPassword) || empty($newPassword)) {
            $errorMsg = 'Semua kolom password wajib diisi.';
        } elseif ($newPassword !== $confirmPassword) {
            $errorMsg = 'Konfirmasi password baru tidak cocok.';
        } elseif (strlen($newPassword) < 6) {
            $errorMsg = 'Password baru minimal harus 6 karakter.';
        } else {
            // Ambil hash password saat ini
            $stmt = $pdo->prepare("SELECT password_hash FROM admin_users WHERE id = :id LIMIT 1");
            $stmt->execute([':id' => $_SESSION['admin_id']]);
            $currentHash = $stmt->fetchColumn();

            if (!$currentHash || !password_verify($currentPassword, $currentHash)) {
                $errorMsg = 'Password saat ini salah. Silakan coba lagi.';
            } else {
                $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
                $updateStmt = $pdo->prepare("UPDATE admin_users SET password_hash = :h WHERE id = :id");
                $updateStmt->execute([':h' => $newHash, ':id' => $_SESSION['admin_id']]);
                $successMsg = 'Password admin berhasil diganti! Gunakan password baru untuk login selanjutnya.';
            }
        }
    }
}

// Ambil nilai terkini untuk ditampilkan di form
$valDoitEnabled = get_setting('doit_enabled', '1') === '1';
$valDoitApiKey = get_setting('doit_api_key', 'pb_test_a9ad11ecb573b60a2242c75c2f325b9302c69921b5d43614');
$valDoitSigningSec = get_setting('doit_signing_secret', 'whs_77615d422f8a1341ce00706fcf0cf30e785bab4bf8efbcc6');

$valDanaEnabled = get_setting('dana_enabled', '1') === '1';
$valDanaEnv = get_setting('dana_env', 'PRODUCTION');
$valDanaMerchantId = get_setting('dana_merchant_id', '216620010011034337246');
$valDanaClientId = get_setting('dana_client_id', '2026111112325220504763');
?>

<div class="max-w-5xl space-y-8">

  <!-- Header -->
  <div>
    <h1 class="text-xl md:text-2xl font-black text-white tracking-tight">Pengaturan Sistem</h1>
    <p class="text-xs md:text-sm text-slate-400 mt-1">Kelola kredensial API payment gateway dan keamanan akun admin Anda.</p>
  </div>

  <!-- Flash Alerts -->
  <?php if ($successMsg): ?>
    <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs md:text-sm flex items-center gap-3">
      <i class="fa-solid fa-circle-check text-base shrink-0"></i>
      <span><?= htmlspecialchars($successMsg) ?></span>
    </div>
  <?php endif; ?>

  <?php if ($errorMsg): ?>
    <div class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-400 text-xs md:text-sm flex items-center gap-3">
      <i class="fa-solid fa-triangle-exclamation text-base shrink-0"></i>
      <span><?= htmlspecialchars($errorMsg) ?></span>
    </div>
  <?php endif; ?>

  <!-- SECTION 1: doit.id Settings -->
  <div id="doit" class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-xl">
    <div class="p-5 md:p-6 border-b border-slate-800 flex items-center gap-3 bg-gradient-to-r from-purple-950/40 to-slate-900">
      <div class="w-10 h-10 rounded-xl bg-purple-600/20 border border-purple-500/30 text-purple-400 flex items-center justify-center font-black">
        <i class="fa-solid fa-bolt"></i>
      </div>
      <div>
        <h2 class="text-sm md:text-base font-bold text-white">Konfigurasi Gateway doit.id (Doitpay)</h2>
        <p class="text-xs text-slate-400">Pembayaran QRIS Dinamis &amp; Virtual Account (Sandbox &amp; Produksi)</p>
      </div>
    </div>

    <form method="POST" action="/admin/settings.php" class="p-5 md:p-6 space-y-5">
      <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
      <input type="hidden" name="action" value="save_doit">

      <!-- Status Toggle -->
      <div class="flex items-center justify-between p-3.5 rounded-xl bg-slate-950/60 border border-slate-800/80">
        <div>
          <span class="text-xs font-bold text-white block">Aktifkan Gateway doit.id</span>
          <span class="text-[11px] text-slate-400">Tampilkan pilihan metode pembayaran doit.id di formulir pesanan</span>
        </div>
        <label class="relative inline-flex items-center cursor-pointer">
          <input type="checkbox" name="doit_enabled" value="1" <?= $valDoitEnabled ? 'checked' : '' ?> class="sr-only peer">
          <div class="w-11 h-6 bg-slate-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-600"></div>
        </label>
      </div>

      <!-- API Key -->
      <div>
        <div class="flex items-center justify-between mb-1.5">
          <label class="text-xs font-bold text-slate-300">API Key (Public Bearer Token)</label>
          <span class="text-[10px] text-slate-500 font-mono">Contoh: pb_test_... / pb_live_...</span>
        </div>
        <input type="text" name="doit_api_key" value="<?= htmlspecialchars($valDoitApiKey) ?>" required
               placeholder="pb_live_... atau pb_test_..."
               class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-xs text-white font-mono focus:outline-none focus:border-purple-500">
        <p class="text-[11px] text-slate-500 mt-1">
          <i class="fa-solid fa-circle-info text-indigo-400 mr-1"></i> Mode sandbox otomatis aktif jika diawali <code class="text-purple-300">pb_test_</code>. Untuk aktivasi live produksi, ganti dengan <code class="text-emerald-300">pb_live_</code> dari portal doit.id.
        </p>
      </div>

      <!-- Signing Secret -->
      <div>
        <div class="flex items-center justify-between mb-1.5">
          <label class="text-xs font-bold text-slate-300">Webhook Signing Secret</label>
          <span class="text-[10px] text-slate-500 font-mono">whs_...</span>
        </div>
        <input type="text" name="doit_signing_secret" value="<?= htmlspecialchars($valDoitSigningSec) ?>" required
               placeholder="whs_..."
               class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-xs text-white font-mono focus:outline-none focus:border-purple-500">
        <p class="text-[11px] text-slate-500 mt-1">
          Digunakan untuk memvalidasi header <code class="text-slate-400">PayBridge-Signature</code> pada webhook callback.
        </p>
      </div>

      <!-- Webhook URL Info -->
      <div class="p-3.5 rounded-xl bg-purple-500/5 border border-purple-500/20 text-xs">
        <div class="font-bold text-purple-300 mb-1 flex items-center gap-1.5">
          <i class="fa-solid fa-link"></i> Webhook Callback URL (Pasang di Portal doit.id Developer):
        </div>
        <div class="font-mono text-emerald-400 select-all bg-slate-950/80 p-2 rounded-lg border border-slate-800 mt-1 text-[11px]">
          https://vegamediapro.web.id/api/doit-notify.php
        </div>
      </div>

      <!-- Submit Button -->
      <div class="flex justify-end pt-2">
        <button type="submit" class="px-5 py-2.5 bg-purple-600 hover:bg-purple-500 text-white rounded-xl text-xs font-bold shadow-lg shadow-purple-600/30 transition flex items-center gap-2">
          <i class="fa-solid fa-floppy-disk"></i>
          <span>Simpan Konfigurasi doit.id</span>
        </button>
      </div>
    </form>
  </div>

  <!-- SECTION 2: DANA Enterprise Settings -->
  <div id="dana" class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-xl">
    <div class="p-5 md:p-6 border-b border-slate-800 flex items-center gap-3 bg-gradient-to-r from-sky-950/40 to-slate-900">
      <div class="w-10 h-10 rounded-xl bg-sky-600/20 border border-sky-500/30 text-sky-400 flex items-center justify-center font-black">
        <i class="fa-solid fa-wallet"></i>
      </div>
      <div>
        <h2 class="text-sm md:text-base font-bold text-white">Konfigurasi Gateway DANA Enterprise</h2>
        <p class="text-xs text-slate-400">Pembayaran SNAP BI &amp; Saldo DANA Merchant</p>
      </div>
    </div>

    <form method="POST" action="/admin/settings.php" class="p-5 md:p-6 space-y-5">
      <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
      <input type="hidden" name="action" value="save_dana">

      <!-- Status Toggle -->
      <div class="flex items-center justify-between p-3.5 rounded-xl bg-slate-950/60 border border-slate-800/80">
        <div>
          <span class="text-xs font-bold text-white block">Aktifkan Gateway DANA</span>
          <span class="text-[11px] text-slate-400">Tampilkan pilihan pembayaran DANA di halaman checkout</span>
        </div>
        <label class="relative inline-flex items-center cursor-pointer">
          <input type="checkbox" name="dana_enabled" value="1" <?= $valDanaEnabled ? 'checked' : '' ?> class="sr-only peer">
          <div class="w-11 h-6 bg-slate-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-sky-600"></div>
        </label>
      </div>

      <!-- Environment Selector -->
      <div>
        <label class="block text-xs font-bold text-slate-300 mb-1.5">Mode Lingkungan (Environment)</label>
        <select name="dana_env" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-sky-500">
          <option value="PRODUCTION" <?= $valDanaEnv === 'PRODUCTION' ? 'selected' : '' ?>>PRODUCTION (https://api.saas.dana.id)</option>
          <option value="SANDBOX" <?= $valDanaEnv === 'SANDBOX' ? 'selected' : '' ?>>SANDBOX (https://api.sandbox.dana.id)</option>
        </select>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <!-- Merchant ID -->
        <div>
          <label class="block text-xs font-bold text-slate-300 mb-1.5">Merchant ID</label>
          <input type="text" name="dana_merchant_id" value="<?= htmlspecialchars($valDanaMerchantId) ?>" required
                 class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-xs text-white font-mono focus:outline-none focus:border-sky-500">
        </div>
        <!-- Client ID -->
        <div>
          <label class="block text-xs font-bold text-slate-300 mb-1.5">Client ID / Partner ID</label>
          <input type="text" name="dana_client_id" value="<?= htmlspecialchars($valDanaClientId) ?>" required
                 class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-xs text-white font-mono focus:outline-none focus:border-sky-500">
        </div>
      </div>

      <!-- RSA Key info -->
      <div class="p-3.5 rounded-xl bg-sky-500/5 border border-sky-500/20 text-xs space-y-1">
        <div class="font-bold text-sky-300 flex items-center gap-1.5">
          <i class="fa-solid fa-key"></i> Kunci RSA (Private / Public Key):
        </div>
        <p class="text-slate-400 text-[11px]">
          Kunci enkripsi RSA tersimpan aman di server file system (<code class="text-sky-300">/root/production_keys/private_key.pem</code>) dan dilindungi oleh izin akses ketat.
        </p>
      </div>

      <!-- Submit Button -->
      <div class="flex justify-end pt-2">
        <button type="submit" class="px-5 py-2.5 bg-sky-600 hover:bg-sky-500 text-white rounded-xl text-xs font-bold shadow-lg shadow-sky-600/30 transition flex items-center gap-2">
          <i class="fa-solid fa-floppy-disk"></i>
          <span>Simpan Konfigurasi DANA</span>
        </button>
      </div>
    </form>
  </div>

  <!-- SECTION 3: Admin Profile & Change Password -->
  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

    <!-- Profil Admin -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-xl">
      <div class="p-5 border-b border-slate-800 flex items-center gap-3">
        <div class="w-9 h-9 rounded-xl bg-indigo-600/20 border border-indigo-500/30 text-indigo-400 flex items-center justify-center font-bold text-sm">
          <i class="fa-solid fa-user-gear"></i>
        </div>
        <div>
          <h2 class="text-sm font-bold text-white">Profil Pengguna Admin</h2>
          <p class="text-[11px] text-slate-400">Ubah nama tampilan dan username</p>
        </div>
      </div>

      <form method="POST" action="/admin/settings.php" class="p-5 space-y-4">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <input type="hidden" name="action" value="change_profile">

        <div>
          <label class="block text-xs font-bold text-slate-300 mb-1.5">Nama Lengkap</label>
          <input type="text" name="name" value="<?= htmlspecialchars($admin['name'] ?? '') ?>" required
                 class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-indigo-500">
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-300 mb-1.5">Username Login</label>
          <input type="text" name="username" value="<?= htmlspecialchars($admin['username'] ?? '') ?>" required
                 class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-xs text-white font-mono focus:outline-none focus:border-indigo-500">
        </div>

        <div class="pt-2">
          <button type="submit" class="w-full py-2.5 bg-slate-800 hover:bg-slate-700 text-white rounded-xl text-xs font-bold transition">
            Simpan Perubahan Profil
          </button>
        </div>
      </form>
    </div>

    <!-- Ganti Password -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-xl">
      <div class="p-5 border-b border-slate-800 flex items-center gap-3">
        <div class="w-9 h-9 rounded-xl bg-rose-600/20 border border-rose-500/30 text-rose-400 flex items-center justify-center font-bold text-sm">
          <i class="fa-solid fa-shield-halved"></i>
        </div>
        <div>
          <h2 class="text-sm font-bold text-white">Ganti Password Admin</h2>
          <p class="text-[11px] text-slate-400">Perbarui kata sandi untuk keamanan akun</p>
        </div>
      </div>

      <form method="POST" action="/admin/settings.php" class="p-5 space-y-4">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <input type="hidden" name="action" value="change_password">

        <div>
          <label class="block text-xs font-bold text-slate-300 mb-1.5">Password Saat Ini</label>
          <input type="password" name="current_password" required placeholder="Masukkan password saat ini"
                 class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-rose-500">
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-300 mb-1.5">Password Baru</label>
          <input type="password" name="new_password" required placeholder="Minimal 6 karakter"
                 class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-rose-500">
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-300 mb-1.5">Konfirmasi Password Baru</label>
          <input type="password" name="confirm_password" required placeholder="Ulangi password baru"
                 class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-rose-500">
        </div>

        <div class="pt-2">
          <button type="submit" class="w-full py-2.5 bg-rose-600 hover:bg-rose-500 text-white rounded-xl text-xs font-bold shadow-lg shadow-rose-600/30 transition flex items-center justify-center gap-2">
            <i class="fa-solid fa-lock"></i>
            <span>Ubah Password Sekarang</span>
          </button>
        </div>
      </form>
    </div>

  </div>

</div>

<?php require_once __DIR__ . '/footer.php'; ?>
