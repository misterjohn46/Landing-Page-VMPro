    </main>
  </div>

  <!-- Mobile Bottom Nav -->
  <div class="md:hidden fixed bottom-0 left-0 right-0 bg-slate-900 border-t border-slate-800 flex justify-around py-3 px-2 z-50">
    <a href="/admin/index.php" class="flex flex-col items-center text-xs <?= ($currentPage === 'index.php') ? 'text-indigo-400 font-bold' : 'text-slate-400' ?>">
      <i class="fa-solid fa-chart-pie text-base mb-1"></i>
      <span>Dashboard</span>
    </a>
    <a href="/admin/transactions.php" class="flex flex-col items-center text-xs <?= ($currentPage === 'transactions.php') ? 'text-indigo-400 font-bold' : 'text-slate-400' ?>">
      <i class="fa-solid fa-money-bill-transfer text-base mb-1"></i>
      <span>Transaksi</span>
    </a>
    <a href="/admin/settings.php" class="flex flex-col items-center text-xs <?= ($currentPage === 'settings.php') ? 'text-indigo-400 font-bold' : 'text-slate-400' ?>">
      <i class="fa-solid fa-sliders text-base mb-1"></i>
      <span>Pengaturan</span>
    </a>
    <a href="/" target="_blank" class="flex flex-col items-center text-xs text-slate-400">
      <i class="fa-solid fa-arrow-up-right-from-square text-base mb-1"></i>
      <span>Web</span>
    </a>
  </div>

</body>
</html>
