<?php
$pageTitle = 'Dashboard Utama';
require_once __DIR__ . '/header.php';

// Inisialisasi statistik
$totalRevenue = 0;
$totalSuccessCount = 0;
$totalPendingCount = 0;
$recentTransactions = [];

// Status Payment Gateway
$doitConfig = file_exists(__DIR__ . '/../api/doit-config.php') ? require __DIR__ . '/../api/doit-config.php' : [];
$danaConfig = file_exists(__DIR__ . '/../api/dana-config.php') ? require __DIR__ . '/../api/dana-config.php' : [];

$isDoitEnabled = !empty($doitConfig['enabled']);
$doitApiKey = $doitConfig['api_key'] ?? '';
$isDoitLive = str_starts_with($doitApiKey, 'pb_live_');

$isDanaEnabled = !empty($danaConfig['enabled']);
$danaEnv = $danaConfig['env'] ?? 'PRODUCTION';

if ($pdo) {
    try {
        // 1. Hitung transaksi doit.id
        $stmt = $pdo->query("SELECT 
            COUNT(*) as total_count,
            SUM(CASE WHEN status = 'PAID' THEN 1 ELSE 0 END) as paid_count,
            SUM(CASE WHEN status = 'PENDING' THEN 1 ELSE 0 END) as pending_count,
            SUM(CASE WHEN status = 'PAID' THEN amount ELSE 0 END) as paid_sum
            FROM doit_transactions");
        $doitStats = $stmt->fetch();

        // 2. Hitung transaksi DANA
        $stmt = $pdo->query("SELECT 
            COUNT(*) as total_count,
            SUM(CASE WHEN status IN ('PAID', 'SUCCESS') THEN 1 ELSE 0 END) as paid_count,
            SUM(CASE WHEN status = 'PENDING' THEN 1 ELSE 0 END) as pending_count,
            SUM(CASE WHEN status IN ('PAID', 'SUCCESS') THEN amount ELSE 0 END) as paid_sum
            FROM dana_transactions");
        $danaStats = $stmt->fetch();

        $totalRevenue = ($doitStats['paid_sum'] ?? 0) + ($danaStats['paid_sum'] ?? 0);
        $totalSuccessCount = ($doitStats['paid_count'] ?? 0) + ($danaStats['paid_count'] ?? 0);
        $totalPendingCount = ($doitStats['pending_count'] ?? 0) + ($danaStats['pending_count'] ?? 0);

        // 3. Ambil 10 transaksi gabungan terbaru
        $unionQuery = "
            (SELECT 
                'doit' AS gateway,
                payment_id AS trans_id,
                reference,
                customer_name,
                customer_phone,
                package_name,
                amount,
                status,
                created_at
            FROM doit_transactions)
            UNION ALL
            (SELECT 
                'dana' AS gateway,
                order_id AS trans_id,
                order_id AS reference,
                customer_name,
                customer_phone,
                package_name,
                amount,
                status,
                created_at
            FROM dana_transactions)
            ORDER BY created_at DESC
            LIMIT 10
        ";
        $recentTransactions = $pdo->query($unionQuery)->fetchAll();

    } catch (Exception $e) {
        $dbError = $e->getMessage();
    }
}
?>

<div class="space-y-6">

  <!-- Welcome Banner -->
  <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-indigo-900/60 via-slate-900 to-slate-900 border border-indigo-500/20 p-6 md:p-8">
    <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
      <div>
        <h1 class="text-xl md:text-2xl font-black text-white tracking-tight">
          Halo, <?= htmlspecialchars($admin['name'] ?? 'Admin') ?>! 👋
        </h1>
        <p class="text-xs md:text-sm text-slate-400 mt-1">
          Pantau performa penjualan paket dan kelola konfigurasi payment gateway Vega MediaPro secara terpusat.
        </p>
      </div>
      <div class="flex items-center gap-2">
        <a href="/admin/settings.php" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-semibold shadow-lg shadow-indigo-600/30 transition flex items-center gap-2">
          <i class="fa-solid fa-sliders"></i>
          <span>Pengaturan Gateway</span>
        </a>
      </div>
    </div>
  </div>

  <!-- KPI Cards -->
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
    <!-- Total Pendapatan -->
    <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-5 hover:border-slate-700 transition">
      <div class="flex items-center justify-between">
        <span class="text-xs font-semibold text-slate-400">Total Omzet Lunas</span>
        <div class="w-9 h-9 rounded-xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center text-sm">
          <i class="fa-solid fa-rupiah-sign"></i>
        </div>
      </div>
      <div class="mt-3">
        <div class="text-xl md:text-2xl font-black text-white">
          Rp <?= number_format($totalRevenue, 0, ',', '.') ?>
        </div>
        <div class="text-[11px] text-slate-500 mt-1 flex items-center gap-1.5">
          <span class="text-emerald-400 font-semibold"><i class="fa-solid fa-check-circle"></i> Berhasil masuk</span>
        </div>
      </div>
    </div>

    <!-- Transaksi Sukses -->
    <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-5 hover:border-slate-700 transition">
      <div class="flex items-center justify-between">
        <span class="text-xs font-semibold text-slate-400">Transaksi Lunas</span>
        <div class="w-9 h-9 rounded-xl bg-indigo-500/10 text-indigo-400 flex items-center justify-center text-sm">
          <i class="fa-solid fa-circle-check"></i>
        </div>
      </div>
      <div class="mt-3">
        <div class="text-xl md:text-2xl font-black text-white">
          <?= number_format($totalSuccessCount, 0, ',', '.') ?>
        </div>
        <div class="text-[11px] text-slate-500 mt-1">
          Pesanan terkonfirmasi
        </div>
      </div>
    </div>

    <!-- Transaksi Pending -->
    <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-5 hover:border-slate-700 transition">
      <div class="flex items-center justify-between">
        <span class="text-xs font-semibold text-slate-400">Transaksi Menunggu</span>
        <div class="w-9 h-9 rounded-xl bg-amber-500/10 text-amber-400 flex items-center justify-center text-sm">
          <i class="fa-solid fa-clock-rotate-left"></i>
        </div>
      </div>
      <div class="mt-3">
        <div class="text-xl md:text-2xl font-black text-white">
          <?= number_format($totalPendingCount, 0, ',', '.') ?>
        </div>
        <div class="text-[11px] text-slate-500 mt-1">
          Menunggu pembayaran customer
        </div>
      </div>
    </div>

    <!-- Payment Gateways Status -->
    <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-5 hover:border-slate-700 transition">
      <div class="flex items-center justify-between">
        <span class="text-xs font-semibold text-slate-400">Gateway Terpasang</span>
        <div class="w-9 h-9 rounded-xl bg-purple-500/10 text-purple-400 flex items-center justify-center text-sm">
          <i class="fa-solid fa-credit-card"></i>
        </div>
      </div>
      <div class="mt-3 space-y-1.5">
        <div class="flex items-center justify-between text-xs">
          <span class="text-slate-300 font-medium">doit.id</span>
          <?php if ($isDoitEnabled): ?>
            <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full <?= $isDoitLive ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-purple-500/10 text-purple-400 border border-purple-500/20' ?>">
              <span class="w-1.5 h-1.5 rounded-full <?= $isDoitLive ? 'bg-emerald-400' : 'bg-purple-400' ?>"></span>
              <?= $isDoitLive ? 'LIVE' : 'SANDBOX' ?>
            </span>
          <?php else: ?>
            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-rose-500/10 text-rose-400 border border-rose-500/20">NONAKTIF</span>
          <?php endif; ?>
        </div>
        <div class="flex items-center justify-between text-xs">
          <span class="text-slate-300 font-medium">DANA Enterprise</span>
          <?php if ($isDanaEnabled): ?>
            <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full <?= $danaEnv === 'PRODUCTION' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-sky-500/10 text-sky-400 border border-sky-500/20' ?>">
              <span class="w-1.5 h-1.5 rounded-full <?= $danaEnv === 'PRODUCTION' ? 'bg-emerald-400' : 'bg-sky-400' ?>"></span>
              <?= $danaEnv ?>
            </span>
          <?php else: ?>
            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-rose-500/10 text-rose-400 border border-rose-500/20">NONAKTIF</span>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Gateway Quick Info Cards -->
  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <!-- Card doit.id -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 relative overflow-hidden">
      <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-purple-600/10 border border-purple-500/20 text-purple-400 flex items-center justify-center font-black text-sm">
            D
          </div>
          <div>
            <h3 class="text-sm font-bold text-white">doit.id (Doitpay)</h3>
            <p class="text-[11px] text-slate-400">QRIS Realtime &amp; Virtual Account Semua Bank</p>
          </div>
        </div>
        <span class="text-xs px-2.5 py-1 rounded-full font-bold <?= $isDoitEnabled ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-rose-500/10 text-rose-400' ?>">
          <?= $isDoitEnabled ? ($isDoitLive ? 'Produksi Aktif' : 'Sandbox Aktif') : 'Nonaktif' ?>
        </span>
      </div>
      <div class="space-y-2 text-xs text-slate-400 bg-slate-950/60 p-3 rounded-xl border border-slate-800/80 font-mono">
        <div class="flex justify-between items-center">
          <span class="text-slate-500">API Key:</span>
          <span class="text-slate-200"><?= htmlspecialchars(substr($doitApiKey, 0, 14)) ?>...</span>
        </div>
        <div class="flex justify-between items-center">
          <span class="text-slate-500">Webhook:</span>
          <span class="text-emerald-400 truncate max-w-[200px]" title="https://vegamediapro.web.id/api/doit-notify.php">.../api/doit-notify.php</span>
        </div>
      </div>
      <div class="mt-4 flex gap-2">
        <a href="/admin/settings.php#doit" class="flex-1 text-center py-2 bg-slate-800 hover:bg-slate-700 text-white rounded-xl text-xs font-semibold transition">
          Konfigurasi Kunci API
        </a>
        <a href="/test-doit.html" target="_blank" class="px-3 py-2 bg-purple-600/20 hover:bg-purple-600/30 text-purple-300 rounded-xl text-xs font-semibold transition flex items-center gap-1.5">
          <i class="fa-solid fa-vial"></i> Uji
        </a>
      </div>
    </div>

    <!-- Card DANA -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 relative overflow-hidden">
      <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-sky-600/10 border border-sky-500/20 text-sky-400 flex items-center justify-center font-black text-sm">
            <i class="fa-solid fa-wallet"></i>
          </div>
          <div>
            <h3 class="text-sm font-bold text-white">DANA Enterprise</h3>
            <p class="text-[11px] text-slate-400">SNAP BI &amp; Saldo DANA Langsung</p>
          </div>
        </div>
        <span class="text-xs px-2.5 py-1 rounded-full font-bold <?= $isDanaEnabled ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-rose-500/10 text-rose-400' ?>">
          <?= $isDanaEnabled ? ($danaEnv === 'PRODUCTION' ? 'Produksi Aktif' : 'Sandbox Aktif') : 'Nonaktif' ?>
        </span>
      </div>
      <div class="space-y-2 text-xs text-slate-400 bg-slate-950/60 p-3 rounded-xl border border-slate-800/80 font-mono">
        <div class="flex justify-between items-center">
          <span class="text-slate-500">Merchant ID:</span>
          <span class="text-slate-200"><?= htmlspecialchars($danaConfig['merchant_id'] ?? '-') ?></span>
        </div>
        <div class="flex justify-between items-center">
          <span class="text-slate-500">Client ID:</span>
          <span class="text-slate-200"><?= htmlspecialchars($danaConfig['partner_id'] ?? '-') ?></span>
        </div>
      </div>
      <div class="mt-4 flex gap-2">
        <a href="/admin/settings.php#dana" class="flex-1 text-center py-2 bg-slate-800 hover:bg-slate-700 text-white rounded-xl text-xs font-semibold transition">
          Konfigurasi DANA
        </a>
        <a href="/test-dana.html" target="_blank" class="px-3 py-2 bg-sky-600/20 hover:bg-sky-600/30 text-sky-300 rounded-xl text-xs font-semibold transition flex items-center gap-1.5">
          <i class="fa-solid fa-vial"></i> Uji
        </a>
      </div>
    </div>
  </div>

  <!-- Recent Transactions Table -->
  <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-xl">
    <div class="p-5 border-b border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
      <div>
        <h2 class="text-sm md:text-base font-bold text-white">Transaksi Terbaru</h2>
        <p class="text-xs text-slate-400 mt-0.5">10 data pembayaran masuk terakhir dari semua gateway</p>
      </div>
      <a href="/admin/transactions.php" class="inline-flex items-center gap-1.5 text-xs text-indigo-400 hover:text-indigo-300 font-semibold transition">
        <span>Lihat Semua Transaksi</span>
        <i class="fa-solid fa-arrow-right text-[10px]"></i>
      </a>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs">
        <thead class="bg-slate-950/70 text-slate-400 border-b border-slate-800 uppercase tracking-wider font-semibold text-[10px]">
          <tr>
            <th class="py-3 px-4">Gateway</th>
            <th class="py-3 px-4">Referensi / Order ID</th>
            <th class="py-3 px-4">Customer</th>
            <th class="py-3 px-4">Paket</th>
            <th class="py-3 px-4">Nominal</th>
            <th class="py-3 px-4">Status</th>
            <th class="py-3 px-4">Waktu</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-800/60">
          <?php if (empty($recentTransactions)): ?>
            <tr>
              <td colspan="7" class="py-8 text-center text-slate-500">
                <i class="fa-solid fa-inbox text-2xl mb-2 block"></i>
                Belum ada data transaksi yang tercatat.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($recentTransactions as $row): ?>
              <tr class="hover:bg-slate-800/40 transition">
                <td class="py-3 px-4 whitespace-nowrap">
                  <?php if ($row['gateway'] === 'doit'): ?>
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-500/10 text-purple-400 border border-purple-500/20">
                      <i class="fa-solid fa-bolt text-[9px]"></i> doit.id
                    </span>
                  <?php else: ?>
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-sky-500/10 text-sky-400 border border-sky-500/20">
                      <i class="fa-solid fa-wallet text-[9px]"></i> DANA
                    </span>
                  <?php endif; ?>
                </td>
                <td class="py-3 px-4 font-mono text-slate-200 whitespace-nowrap">
                  <div class="font-bold"><?= htmlspecialchars($row['reference']) ?></div>
                  <div class="text-[10px] text-slate-500"><?= htmlspecialchars($row['trans_id']) ?></div>
                </td>
                <td class="py-3 px-4 whitespace-nowrap">
                  <div class="font-semibold text-slate-200"><?= htmlspecialchars($row['customer_name'] ?: 'Customer') ?></div>
                  <div class="text-[10px] text-slate-500"><?= htmlspecialchars($row['customer_phone'] ?: '-') ?></div>
                </td>
                <td class="py-3 px-4 text-slate-300 whitespace-nowrap">
                  <?= htmlspecialchars($row['package_name'] ?: 'Layanan Pembuatan Web') ?>
                </td>
                <td class="py-3 px-4 font-bold text-white whitespace-nowrap">
                  Rp <?= number_format($row['amount'], 0, ',', '.') ?>
                </td>
                <td class="py-3 px-4 whitespace-nowrap">
                  <?php
                    $st = strtoupper($row['status']);
                    if (in_array($st, ['PAID', 'SUCCESS'])):
                  ?>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                      LUNAS
                    </span>
                  <?php elseif ($st === 'PENDING'): ?>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/10 text-amber-400 border border-amber-500/20">
                      MENUNGGU
                    </span>
                  <?php elseif (in_array($st, ['EXPIRED', 'FAILED'])): ?>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-500/10 text-rose-400 border border-rose-500/20">
                      KADALUWARSA
                    </span>
                  <?php else: ?>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-700 text-slate-300">
                      <?= htmlspecialchars($st) ?>
                    </span>
                  <?php endif; ?>
                </td>
                <td class="py-3 px-4 text-slate-400 whitespace-nowrap text-[11px]">
                  <?= date('d M Y, H:i', strtotime($row['created_at'])) ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<?php require_once __DIR__ . '/footer.php'; ?>
