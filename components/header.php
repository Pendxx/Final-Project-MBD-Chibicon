<?php
// header.php - Shared HTML header and TopNavBar
// Expects: $page_title, $active_menu
?>
<!DOCTYPE html>
<html class="dark" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title><?= htmlspecialchars($page_title ?? 'Chibicon Admin') ?></title>
    
    <!-- Premium Fonts -->
    <link href="https://fonts.googleapis.com" rel="preconnect"/>
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
    <link href="https://fonts.googleapis.com/css2?family=Geist+Mono:wght@400;500;600;700&family=Geist:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
    
    <!-- Material Symbols -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries,aspect-ratio"></script>
    
    <!-- Custom CSS/JS -->
    <link href="../assets/css/app.css" rel="stylesheet" />
    <script src="../assets/js/app.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Tailwind Configuration -->
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'Geist', 'Inter', 'sans-serif'],
                        display: ['Geist', 'sans-serif'],
                        mono: ['Geist Mono', 'monospace'],
                    },
                    colors: {
                        primary: {
                            DEFAULT: 'var(--accent)',
                            hover: 'var(--accent-hover)',
                        },
                        surface: {
                            DEFAULT: 'var(--bg-surface)',
                            dim: 'var(--bg-surface-dim)',
                            bright: 'var(--bg-surface-bright)',
                        },
                        "on-surface": 'var(--text-main)',
                        "on-surface-variant": 'var(--text-muted)',
                        outline: {
                            DEFAULT: 'var(--glass-border)',
                            variant: 'var(--bg-surface-dim)',
                        },
                        glass: 'var(--glass-bg)',
                    },
                    boxShadow: {
                        'soft': 'var(--shadow-soft)',
                        'premium': 'var(--shadow-premium)',
                    },
                    transitionTimingFunction: {
                        'premium': 'var(--premium-bezier)',
                    },
                    spacing: {
                        'gutter': '32px',
                        'container-margin': '48px',
                    },
                    borderRadius: {
                        DEFAULT: '16px',
                        'md': '20px',
                        'lg': '24px',
                        'xl': '32px',
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-surface text-on-surface font-sans min-h-screen flex antialiased transition-colors duration-500 overflow-x-hidden">
    
    <?php include __DIR__ . '/sidebar.php'; ?>

    <!-- Main Content Wrapper -->
    <div class="flex-1 flex flex-col md:ml-[280px] min-h-[100dvh] transition-all duration-700 ease-premium relative z-10">
        <!-- Top App Bar - Ethereal Glass Pill -->
        <header class="sticky top-0 z-40 px-6 py-6 transition-all duration-500">
            <div class="mx-auto w-full flex items-center justify-between px-6 py-4 bg-glass backdrop-blur-2xl border border-outline shadow-soft rounded-[2rem]">
                <div class="flex items-center gap-4">
                    <button onclick="toggleSidebar()" class="md:hidden text-on-surface-variant hover:bg-surface-dim p-2 rounded-xl transition-colors">
                        <span class="material-symbols-outlined">menu</span>
                    </button>
                    <div class="hidden sm:block">
                        <div class="flex items-center gap-3">
                            <span class="w-2 h-2 rounded-full bg-primary animate-pulse" style="box-shadow: 0 0 10px var(--accent)"></span>
                            <h2 class="font-extrabold text-lg tracking-tight"><?= htmlspecialchars($page_title ?? 'Command Center') ?></h2>
                        </div>
                    </div>
                </div>

                <!-- Header Actions -->
                <div class="flex items-center gap-4">
                    <!-- Quick Action -->
                    <button onclick="openQuickAction()" class="group bg-surface-dim text-on-surface text-sm px-6 py-2.5 rounded-full font-bold hover:bg-surface-bright transition-all border border-outline shadow-soft flex items-center gap-2 active:scale-95">
                        <span>Quick Action</span>
                        <div class="w-6 h-6 rounded-full bg-surface-dim flex items-center justify-center group-hover:bg-primary group-hover:text-on-surface transition-colors">
                            <span class="material-symbols-outlined text-[14px]">bolt</span>
                        </div>
                    </button>

                    <div class="w-px h-6 bg-outline mx-1 hidden sm:block"></div>

                    <!-- User Avatar -->
                    <div class="w-11 h-11 rounded-full bg-surface-dim border border-outline overflow-hidden cursor-pointer active:scale-90 transition-transform ring-2 ring-transparent hover:ring-primary/50 flex items-center justify-center">
                        <?php $u = $_SESSION['user'] ?? []; ?>
                        <span class="font-bold text-sm text-primary"><?= strtoupper(substr($u['name'] ?? 'AD', 0, 2)) ?></span>
                    </div>
                </div>
            </div>
        </header>
        
        <?php include __DIR__ . '/toast.php'; ?>

<!-- ── Quick Action Modal ─────────────────────────────────────────────────────── -->
<div id="quickActionModal" class="fixed inset-0 z-[9999] hidden flex items-center justify-center">
    <div class="absolute inset-0 modal-overlay opacity-0" onclick="closeQuickAction()"></div>
    <div class="relative rounded-[2rem] w-full max-w-sm mx-4 modal-content scale-95 opacity-0 transition-all duration-500 ease-premium overflow-hidden p-1">
        <div class="bg-surface rounded-[30px] h-full w-full p-2">
            <div class="px-6 py-4 border-b border-outline flex justify-between items-center">
                <h3 class="font-bold text-xl tracking-tight text-on-surface">Actions</h3>
                <button onclick="closeQuickAction()" class="text-on-surface-variant hover:text-on-surface rounded-full p-2 transition-colors"><span class="material-symbols-outlined">close</span></button>
            </div>
            <div class="p-4 grid grid-cols-2 gap-3">
                <a href="tiket.php?tab=transaksi" class="group flex flex-col items-center justify-center p-6 rounded-2xl bg-surface-dim hover:bg-primary transition-all text-center gap-3">
                    <span class="material-symbols-outlined text-primary group-hover:text-surface text-3xl">qr_code_scanner</span>
                    <span class="text-xs font-bold text-on-surface group-hover:text-surface">Scan Tiket</span>
                </a>
                <a href="acara.php" class="group flex flex-col items-center justify-center p-6 rounded-2xl bg-surface-dim hover:bg-primary transition-all text-center gap-3">
                    <span class="material-symbols-outlined text-primary group-hover:text-surface text-3xl">event_available</span>
                    <span class="text-xs font-bold text-on-surface group-hover:text-surface">Kelola Event</span>
                </a>
            </div>
        </div>
    </div>
</div>
