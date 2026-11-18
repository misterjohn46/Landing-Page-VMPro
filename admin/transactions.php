<?php
$pageTitle = 'Data Transaksi Pembayaran';
require_once __DIR__ . '/header.php';

$filterGateway = $_GET['gateway'] ?? 'all';
$filterStatus = $_GET['status'] ?? 'all';
$searchQuery = trim($_GET['q'] ?? '');

$page = max(1, (int)($_GET['p'] ?? 1));
$perPage = 25;
$offset = ($page - 1) * $perPage;

$transactions = [];
$totalRows = 0;

if ($pdo) {
    try {
        $whereClauses = [];
        $params = [];

        // Build unified CTE or subquery
        $baseSql = "
            SELECT 
                'doit' AS gateway,
                id AS db_id,
                payment_id AS trans_id,
                reference,
                customer_name,
                customer_phone,
                customer_email,
                package_name,
                amount,
                status,
                raw_data AS raw_payload,
                created_at
            FROM doit_transactions
            UNION ALL
            SELECT 
                'dana' AS gateway,
                id AS db_id,
                order_id AS trans_id,
                order_id AS reference,
                customer_name,
                customer_phone,
                customer_email,
                package_name,
                amount,
                status,
                raw_response AS raw_payload,
                created_at
            FROM dana_transactions
        ";

        $wrappedSql = "FROM ({$baseSql}) AS t WHERE 1=1";

        if ($filterGateway !== 'all') {
            $wrappedSql .= " AND t.gateway = :gateway";
            $params[':gateway'] = $filterGateway;
        }

        if ($filterStatus !== 'all') {
            if ($filterStatus === 'PAID') {
                $wrappedSql .= " AND t.status IN ('PAID', 'SUCCESS')";
            } else {
                $wrappedSql .= " AND t.status = :status";
                $params[':status'] = $filterStatus;
            }
        }

        if ($searchQuery !== '') {
            $wrappedSql .= " AND (t.reference LIKE :search OR t.trans_id LIKE :search OR t.customer_name LIKE :search OR t.customer_phone LIKE :search)";
            $params[':search'] = "%{$searchQuery}%";
        }

        // Hitung total data
        $countStmt = $pdo->prepare("SELECT COUNT(*) " . $wrappedSql);
        $countStmt->execute($params);
        $totalRows = (int)$countStmt->fetchColumn();

        // Ambil data dengan limit dan offset
        $dataStmt = $pdo->prepare("SELECT * " . $wrappedSql . " ORDER BY t.created_at DESC LIMIT {$perPage} OFFSET {$offset}");
        $dataStmt->execute($params);
        $transactions = $dataStmt->fetchAll();

    } catch (Exception $e) {
        $dbError = $e->getMessage();
    }
}

$totalPages = max(1, ceil($totalRows / $perPage));
?>

<div class="space-y-6">

  <!-- Header & Actions -->
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
      <h1 class="text-xl md:text-2xl font-black text-white tracking-tight">Riwayat Transaksi</h1>
      <p class="text-xs md:text-sm text-slate-400 mt-1">Daftar lengkap log transaksi doit.id &amp; DANA Enterprise.</p>
    </div>
    <div class="flex items-center gap-2">
      <button onclick="window.location.reload()" class="px-3.5 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-xl text-xs font-semibold transition flex items-center gap-2">
        <i class="fa-solid fa-arrows-rotate"></i>
        <span>Refresh</span>
      </button>
    </div>
  </div>

  <!-- Filter & Search Toolbar -->
  <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4">
    <form method="GET" action="/admin/transactions.php" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
      <!-- Search Input -->
      <div>
        <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Pencarian</label>
        <div class="relative">
          <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 text-xs"></i>
          <input type="text" name="q" value="<?= htmlspecialchars($searchQuery) ?>" placeholder="Ref / Nama / No HP..." 
                 class="w-full bg-slate-950 border border-slate-800 rounded-xl pl-9 pr-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500">
        </div>
      </div>

      <!-- Filter Gateway -->
      <div>
        <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Gateway</label>
        <select name="gateway" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500">
          <option value="all" <?= $filterGateway === 'all' ? 'selected' : '' ?>>Semua Gateway</option>
          <option value="doit" <?= $filterGateway === 'doit' ? 'selected' : '' ?>>doit.id (Doitpay)</option>
          <option value="dana" <?= $filterGateway === 'dana' ? 'selected' : '' ?>>DANA Enterprise</option>
        </select>
      </div>

      <!-- Filter Status -->
      <div>
        <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Status Pembayaran</label>
        <select name="status" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500">
          <option value="all" <?= $filterStatus === 'all' ? 'selected' : '' ?>>Semua Status</option>
          <option value="PAID" <?= $filterStatus === 'PAID' ? 'selected' : '' ?>>LUNAS (PAID / SUCCESS)</option>
          <option value="PENDING" <?= $filterStatus === 'PENDING' ? 'selected' : '' ?>>MENUNGGU (PENDING)</option>
          <option value="EXPIRED" <?= $filterStatus === 'EXPIRED' ? 'selected' : '' ?>>KADALUWARSA (EXPIRED)</option>
        </select>
      </div>

      <!-- Button Submit & Reset -->
      <div class="flex items-end gap-2">
        <button type="submit" class="flex-1 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-semibold transition flex items-center justify-center gap-1.5">
          <i class="fa-solid fa-filter text-xs"></i>
          <span>Terapkan</span>
        </button>
        <?php if ($filterGateway !== 'all' || $filterStatus !== 'all' || $searchQuery !== ''): ?>
          <a href="/admin/transactions.php" class="px-3 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-semibold transition" title="Reset filter">
            <i class="fa-solid fa-xmark"></i>
          </a>
        <?php endif; ?>
      </div>
    </form>
  </div>

  <!-- Table Card -->
  <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-xl">
    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs">
        <thead class="bg-slate-950/70 text-slate-400 border-b border-slate-800 uppercase tracking-wider font-semibold text-[10px]">
          <tr>
            <th class="py-3 px-4">Gateway</th>
            <th class="py-3 px-4">Referensi &amp; ID</th>
            <th class="py-3 px-4">Customer</th>
            <th class="py-3 px-4">Paket</th>
            <th class="py-3 px-4">Nominal</th>
            <th class="py-3 px-4">Status</th>
            <th class="py-3 px-4">Waktu</th>
            <th class="py-3 px-4 text-center">Payload</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-800/60">
          <?php if (empty($transactions)): ?>
            <tr>
              <td colspan="8" class="py-12 text-center text-slate-500">
                <i class="fa-solid fa-folder-open text-3xl mb-2 block"></i>
                Tidak ada data transaksi yang sesuai filter.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($transactions as $idx => $t): ?>
              <tr class="hover:bg-slate-800/40 transition">
                <!-- Gateway -->
                <td class="py-3 px-4 whitespace-nowrap">
                  <?php if ($t['gateway'] === 'doit'): ?>
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-500/10 text-purple-400 border border-purple-500/20">
                      <i class="fa-solid fa-bolt text-[9px]"></i> doit.id
                    </span>
                  <?php else: ?>
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-sky-500/10 text-sky-400 border border-sky-500/20">
                      <i class="fa-solid fa-wallet text-[9px]"></i> DANA
                    </span>
                  <?php endif; ?>
                </td>

                <!-- Referensi & ID -->
                <td class="py-3 px-4 font-mono text-slate-200 whitespace-nowrap">
                  <div class="font-bold text-xs"><?= htmlspecialchars($t['reference']) ?></div>
                  <div class="text-[10px] text-slate-500"><?= htmlspecialchars($t['trans_id']) ?></div>
                </td>

                <!-- Customer -->
                <td class="py-3 px-4 whitespace-nowrap">
                  <div class="font-semibold text-slate-200"><?= htmlspecialchars($t['customer_name'] ?: '-') ?></div>
                  <div class="text-[10px] text-slate-400"><?= htmlspecialchars($t['customer_phone'] ?: '') ?> <?= $t['customer_email'] ? "• " . htmlspecialchars($t['customer_email']) : '' ?></div>
                </td>

                <!-- Paket -->
                <td class="py-3 px-4 text-slate-300 whitespace-nowrap">
                  <?= htmlspecialchars($t['package_name'] ?: 'Layanan Pembuatan Web') ?>
                </td>

                <!-- Nominal -->
                <td class="py-3 px-4 font-bold text-white whitespace-nowrap">
                  Rp <?= number_format($t['amount'], 0, ',', '.') ?>
                </td>

                <!-- Status -->
                <td class="py-3 px-4 whitespace-nowrap">
                  <?php
                    $st = strtoupper($t['status']);
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

                <!-- Waktu -->
                <td class="py-3 px-4 text-slate-400 whitespace-nowrap text-[11px]">
                  <?= date('d M Y, H:i', strtotime($t['created_at'])) ?>
                </td>

                <!-- Payload Modal Trigger -->
                <td class="py-3 px-4 text-center whitespace-nowrap">
                  <button type="button" 
                          onclick="openPayloadModal('<?= htmlspecialchars($t['reference']) ?>', <?= htmlspecialchars(json_encode($t['raw_payload']), ENT_QUOTES, 'UTF-8') ?>)" 
                          class="p-1.5 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-lg text-xs transition" title="Lihat Raw JSON Data">
                    <i class="fa-solid fa-code"></i>
                  </button>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Pagination Footer -->
    <div class="p-4 border-t border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs text-slate-400">
      <div>
        Menampilkan data <strong><?= min($totalRows, $offset + 1) ?></strong> - <strong><?= min($totalRows, $offset + count($transactions)) ?></strong> dari <strong><?= $totalRows ?></strong> transaksi
      </div>
      <?php if ($totalPages > 1): ?>
        <div class="flex items-center gap-1">
          <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="?p=<?= $i ?>&gateway=<?= urlencode($filterGateway) ?>&status=<?= urlencode($filterStatus) ?>&q=<?= urlencode($searchQuery) ?>" 
               class="px-2.5 py-1 rounded-lg text-xs font-semibold <?= $i === $page ? 'bg-indigo-600 text-white' : 'bg-slate-800 hover:bg-slate-700 text-slate-300' ?> transition">
              <?= $i ?>
            </a>
          <?php endfor; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

</div>

<!-- Raw Payload Modal -->
<div id="payloadModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
  <div class="bg-slate-900 border border-slate-800 rounded-2xl max-w-2xl w-full overflow-hidden shadow-2xl flex flex-col max-h-[85vh]">
    <div class="p-4 border-b border-slate-800 flex items-center justify-between">
      <div class="flex items-center gap-2">
        <i class="fa-solid fa-terminal text-indigo-400"></i>
        <span class="font-bold text-sm text-white" id="modalRefTitle">Raw Webhook Data</span>
      </div>
      <button onclick="closePayloadModal()" class="text-slate-400 hover:text-white p-1 rounded-lg transition">
        <i class="fa-solid fa-xmark text-lg"></i>
      </button>
    </div>
    <div class="p-4 overflow-y-auto flex-1 bg-slate-950 font-mono text-xs text-emerald-400">
      <pre id="modalPayloadBody" class="whitespace-pre-wrap break-all"></pre>
    </div>
    <div class="p-3 border-t border-slate-800 flex justify-end">
      <button onclick="closePayloadModal()" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white rounded-xl text-xs font-semibold transition">
        Tutup
      </button>
    </div>
  </div>
</div>

<script>
function openPayloadModal(ref, rawData) {
  document.getElementById('modalRefTitle').innerText = 'Data Payload: ' + ref;
  let formatted = rawData;
  try {
    const parsed = typeof rawData === 'string' ? JSON.parse(rawData) : rawData;
    formatted = JSON.stringify(parsed, null, 2);
  } catch (e) {
    // leave as raw string
  }
  document.getElementById('modalPayloadBody').innerText = formatted || '(Tidak ada raw data tercatat)';
  document.getElementById('payloadModal').classList.remove('hidden');
}

function closePayloadModal() {
  document.getElementById('payloadModal').classList.add('hidden');
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
