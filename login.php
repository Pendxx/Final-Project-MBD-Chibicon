<?php
// login.php - Halaman Login Chibicon Admin (Local only)
require_once __DIR__ . '/config/db.php';

// If already logged in, redirect to dashboard
if (isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

$error = '';

// Handle local admin login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Simple local admin credentials — ganti jika perlu
    if ($username === 'admin' && $password === 'chibicon2024') {
        $_SESSION['user'] = [
            'id'     => 'admin-local',
            'name'   => 'Administrator',
            'email'  => 'admin@chibicon.local',
            'avatar' => '',
            'login'  => 'local',
        ];
        $_SESSION['toast']      = 'Selamat datang kembali, Administrator! 👋';
        $_SESSION['toast_type'] = 'success';
        header('Location: index.php');
        exit;
    }
    $error = 'Username atau password salah.';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Chibicon Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#b61722',
                        'primary-dark': '#8a1019',
                    },
                    fontFamily: { geist: ['Geist', 'sans-serif'] }
                }
            }
        }
    </script>
    <style>
        * { font-family: 'Geist', sans-serif; }
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }

        /* Animated gradient background */
        .bg-animated {
            background: linear-gradient(-45deg, #0f0c29, #1a0a0d, #24243e, #1a0510);
            background-size: 400% 400%;
            animation: gradientShift 12s ease infinite;
        }
        @keyframes gradientShift {
            0%   { background-position: 0% 50%; }
            50%  { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* Floating blobs */
        .blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.15;
            animation: blobFloat 8s ease-in-out infinite;
        }
        @keyframes blobFloat {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33%       { transform: translate(30px, -20px) scale(1.1); }
            66%       { transform: translate(-20px, 15px) scale(0.9); }
        }

        /* Glassmorphism card */
        .glass-card {
            background: rgba(255, 255, 255, 0.06);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(255, 255, 255, 0.12);
        }

        /* Particle dots */
        .particle {
            position: absolute;
            width: 2px; height: 2px;
            border-radius: 50%;
            background: rgba(255,255,255,0.4);
            animation: particleFade 4s ease-in-out infinite;
        }
        @keyframes particleFade {
            0%, 100% { opacity: 0; transform: translateY(0); }
            50%       { opacity: 1; transform: translateY(-20px); }
        }

        .btn-login { transition: all 0.25s ease; }
        .btn-login:hover { transform: translateY(-2px); box-shadow: 0 8px 30px rgba(182,23,34,0.45); }
        input:focus { outline: none; box-shadow: 0 0 0 2px rgba(182,23,34,0.5); }
    </style>
</head>
<body class="bg-animated min-h-screen flex items-center justify-center relative overflow-hidden">

    <!-- Decorative blobs -->
    <div class="blob w-96 h-96 bg-red-600 top-0 left-0 -translate-x-1/2 -translate-y-1/2"></div>
    <div class="blob w-80 h-80 bg-purple-700 bottom-0 right-0 translate-x-1/4 translate-y-1/4" style="animation-delay:-3s"></div>
    <div class="blob w-64 h-64 bg-rose-500 top-1/2 left-1/4" style="animation-delay:-6s"></div>

    <!-- Particle dots -->
    <?php for($i=0; $i<20; $i++): ?>
    <div class="particle" style="
        left:<?= rand(0,100) ?>%;
        top:<?= rand(0,100) ?>%;
        animation-delay:<?= rand(0,4000)/1000 ?>s;
        animation-duration:<?= rand(3,6) ?>s;
    "></div>
    <?php endfor; ?>

    <!-- Main Card -->
    <div class="relative z-10 w-full max-w-sm mx-4">
        <div class="glass-card rounded-2xl p-8 shadow-2xl">

            <!-- Logo & Title -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-primary mb-4 shadow-lg shadow-red-900/50">
                    <span class="material-symbols-outlined text-white text-3xl">festival</span>
                </div>
                <h1 class="text-2xl font-bold text-white tracking-tight">Chibicon Admin</h1>
                <p class="text-sm text-white/50 mt-1">Event Management Platform</p>
            </div>

            <!-- Error Alert -->
            <?php if ($error): ?>
            <div class="mb-5 flex items-center gap-2 bg-red-500/20 border border-red-500/30 rounded-lg px-4 py-3 text-red-300 text-sm">
                <span class="material-symbols-outlined text-[18px]">error</span>
                <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form action="login.php" method="POST" class="space-y-4">
                <div>
                    <label class="block text-white/60 text-xs font-medium mb-1.5 uppercase tracking-wider">Username</label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-white/30 text-[18px]">person</span>
                        <input
                            type="text" name="username"
                            value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                            placeholder="admin"
                            autocomplete="username"
                            class="w-full border border-white/15 rounded-lg py-2.5 pl-10 pr-4 text-white placeholder-white/25 text-sm transition-all"
                            style="background: rgba(255,255,255,0.06);"
                        >
                    </div>
                </div>
                <div>
                    <label class="block text-white/60 text-xs font-medium mb-1.5 uppercase tracking-wider">Password</label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-white/30 text-[18px]">lock</span>
                        <input
                            type="password" name="password"
                            placeholder="••••••••"
                            autocomplete="current-password"
                            class="w-full border border-white/15 rounded-lg py-2.5 pl-10 pr-4 text-white placeholder-white/25 text-sm transition-all"
                            style="background: rgba(255,255,255,0.06);"
                        >
                    </div>
                </div>
                <button type="submit" class="btn-login w-full bg-primary hover:bg-primary-dark text-white font-semibold py-2.5 rounded-xl shadow-lg mt-2 transition-all flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-[18px]">login</span> Masuk ke Dashboard
                </button>
            </form>

            <!-- Hint -->
            <p class="text-center text-white/20 text-xs mt-6">
                Default: <code class="bg-white/10 px-1 rounded">admin</code> / <code class="bg-white/10 px-1 rounded">chibicon2024</code>
            </p>
        </div>

        <!-- Footer -->
        <p class="text-center text-white/20 text-xs mt-6">
            © <?= date('Y') ?> Chibicon Event Admin. All rights reserved.
        </p>
    </div>

</body>
</html>
