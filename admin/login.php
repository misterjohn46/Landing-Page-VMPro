<?php
/**
 * Admin Login Page
 * Vega MediaPro
 */

require_once __DIR__ . '/auth.php';

// Jika sudah login, langsung ke dashboard
if (!empty($_SESSION['admin_logged_in'])) {
    header('Location: /admin/index.php');
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Silakan masukkan username dan password.';
    } else {
        if ($pdo) {
            $stmt = $pdo->prepare("SELECT id, username, password_hash, name FROM admin_users WHERE username = :u LIMIT 1");
            $stmt->execute([':u' => $username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                // Regenerate session id untuk keamanan
                session_regenerate_id(true);
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $user['id'];
                $_SESSION['admin_username'] = $user['username'];
                $_SESSION['admin_name'] = $user['name'];

                header('Location: /admin/index.php');
                exit;
            } else {
                $error = 'Username atau password yang Anda masukkan salah.';
            }
        } else {
            $error = 'Koneksi database gagal. Silakan periksa konfigurasi MySQL.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login Admin - Vega MediaPro</title>
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
<body class="bg-slate-950 text-slate-100 min-h-screen flex items-center justify-center p-4 antialiased selection:bg-indigo-500 selection:text-white">
  
  <div class="max-w-md w-full bg-slate-900/90 backdrop-blur-xl border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-2xl relative overflow-hidden">
    <!-- Glow accent -->
    <div class="absolute -top-24 -left-24 w-48 h-48 bg-indigo-500/20 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute -bottom-24 -right-24 w-48 h-48 bg-cyan-500/20 rounded-full blur-3xl pointer-events-none"></div>

    <!-- Header -->
    <div class="text-center mb-7">
      <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-indigo-600 to-cyan-400 flex items-center justify-center text-white mx-auto shadow-lg shadow-indigo-500/30 mb-4 text-xl">
        <i class="fa-solid fa-shield-halved"></i>
      </div>
      <h1 class="text-2xl font-extrabold text-white tracking-tight">Vega MediaPro</h1>
      <p class="text-slate-400 text-xs mt-1">Panel Administrasi &amp; Manajemen Pembayaran</p>
    </div>

    <?php if ($error): ?>
      <div class="mb-5 p-3.5 rounded-xl bg-rose-500/10 border border-rose-500/30 text-xs text-rose-300 flex items-start gap-2.5">
        <i class="fa-solid fa-circle-exclamation mt-0.5 text-rose-400 shrink-0"></i>
        <span><?= htmlspecialchars($error) ?></span>
      </div>
    <?php endif; ?>

    <form method="POST" action="" class="space-y-4">
      <div>
        <label class="block text-xs font-semibold text-slate-300 mb-1.5">Username</label>
        <div class="relative">
          <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-500 text-xs">
            <i class="fa-solid fa-user"></i>
          </span>
          <input type="text" name="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required autofocus
                 placeholder="admin"
                 class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-xs placeholder:text-slate-600 focus:outline-none focus:border-indigo-500 transition">
        </div>
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-300 mb-1.5">Password</label>
        <div class="relative">
          <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-500 text-xs">
            <i class="fa-solid fa-lock"></i>
          </span>
          <input type="password" name="password" required
                 placeholder="••••••••"
                 class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-xs placeholder:text-slate-600 focus:outline-none focus:border-indigo-500 transition">
        </div>
      </div>

      <div class="pt-2">
        <button type="submit"
                class="w-full py-3 rounded-xl bg-gradient-to-r from-indigo-600 to-cyan-500 hover:from-indigo-500 hover:to-cyan-400 text-white font-bold text-xs shadow-lg shadow-indigo-500/25 transition flex items-center justify-center gap-2 cursor-pointer">
          <i class="fa-solid fa-right-to-bracket"></i>
          <span>Masuk ke Dashboard</span>
        </button>
      </div>
    </form>

    <div class="mt-6 text-center border-t border-slate-800/80 pt-4">
      <a href="/" class="text-xs text-slate-500 hover:text-slate-300 transition flex items-center justify-center gap-1.5">
        <i class="fa-solid fa-arrow-left text-[10px]"></i>
        <span>Kembali ke Website Utama</span>
      </a>
    </div>
  </div>

</body>
</html>
