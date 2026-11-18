<?php
require_once __DIR__ . '/auth.php';
check_auth();

$currentPage = basename($_SERVER['PHP_SELF']);
$admin = get_logged_admin();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $pageTitle ?? 'Admin Panel' ?> - Vega MediaPro</title>
  <!-- Tailwind CSS CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: {
            sans: ['Plus Jakarta Sans', 'sans-serif'],
          }
        }
      }
    }
  </script>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen flex antialiased selection:bg-indigo-500 selection:text-white">

  <!-- Sidebar -->
  <aside class="w-64 bg-slate-900 border-r border-slate-800 flex flex-col shrink-0 min-h-screen hidden md:flex">
    <!-- Brand -->
    <div class="h-20 flex items-center gap-3 px-6 border-b border-slate-800">
      <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-indigo-600 to-cyan-400 flex items-center justify-center text-white shadow-md shadow-indigo-500/20">
        <i class="fa-solid fa-code text-sm"></i>
      </div>
      <div>
        <span class="font-extrabold text-sm tracking-tight text-white block">Vega MediaPro</span>
        <span class="text-[10px] text-slate-400 font-medium">Admin Dashboard</span>
      </div>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 p-4 space-y-1 text-xs font-semibold">
      <a href="/admin/index.php" 
         class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition <?= ($currentPage === 'index.php') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' ?>">
        <i class="fa-solid fa-chart-pie w-4 text-center"></i>
        <span>Dashboard</span>
      </a>

      <a href="/admin/transactions.php" 
         class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition <?= ($currentPage === 'transactions.php') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' ?>">
        <i class="fa-solid fa-money-bill-transfer w-4 text-center"></i>
        <span>Data Transaksi</span>
      </a>

      <a href="/admin/settings.php" 
         class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition <?= ($currentPage === 'settings.php') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' ?>">
        <i class="fa-solid fa-sliders w-4 text-center"></i>
        <span>Pengaturan Gateway &amp; Akun</span>
      </a>

      <div class="pt-4 pb-2 px-3 text-[10px] uppercase font-bold text-slate-500 tracking-wider">
        Pintasan
      </div>

      <a href="/" target="_blank"
         class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800/60 transition">
        <i class="fa-solid fa-arrow-up-right-from-square w-4 text-center"></i>
        <span>Lihat Website</span>
      </a>

      <a href="/test-doit.html" target="_blank"
         class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800/60 transition">
        <i class="fa-solid fa-vial w-4 text-center text-purple-400"></i>
        <span>Tes doit.id</span>
      </a>

      <a href="/test-dana.html" target="_blank"
         class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800/60 transition">
        <i class="fa-solid fa-wallet w-4 text-center text-sky-400"></i>
        <span>Tes DANA</span>
      </a>
    </nav>

    <!-- User Profile & Logout -->
    <div class="p-4 border-t border-slate-800">
      <div class="flex items-center justify-between p-2 rounded-xl bg-slate-950/60 border border-slate-800/80">
        <div class="flex items-center gap-2.5 overflow-hidden">
          <div class="w-8 h-8 rounded-lg bg-indigo-500/20 text-indigo-400 flex items-center justify-center font-bold text-xs shrink-0">
            <?= strtoupper(substr($admin['username'] ?? 'A', 0, 1)) ?>
          </div>
          <div class="truncate">
            <span class="text-xs font-bold text-white block truncate"><?= htmlspecialchars($admin['name'] ?? 'Admin') ?></span>
            <span class="text-[10px] text-slate-400 font-mono">@<?= htmlspecialchars($admin['username'] ?? 'admin') ?></span>
          </div>
        </div>
        <a href="/admin/logout.php" title="Keluar" onclick="return confirm('Apakah Anda yakin ingin logout?')"
           class="p-2 text-rose-400 hover:text-rose-300 hover:bg-rose-500/10 rounded-lg transition">
          <i class="fa-solid fa-power-off text-xs"></i>
        </a>
      </div>
    </div>
  </aside>

  <!-- Main Content Wrapper -->
  <div class="flex-1 flex flex-col min-w-0">
    <!-- Topbar Mobile -->
    <header class="h-16 bg-slate-900 border-b border-slate-800 flex items-center justify-between px-4 md:px-8">
      <div class="flex items-center gap-3">
        <span class="text-xs md:text-sm font-bold text-white"><?= $pageTitle ?? 'Dashboard' ?></span>
      </div>
      <div class="flex items-center gap-3">
        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
          <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span> Sistem Aktif
        </span>
        <a href="/admin/logout.php" onclick="return confirm('Keluar dari panel admin?')" class="md:hidden text-rose-400 p-2">
          <i class="fa-solid fa-power-off text-sm"></i>
        </a>
      </div>
    </header>

    <!-- Main Content Area -->
    <main class="flex-1 p-4 md:p-8 overflow-y-auto">
