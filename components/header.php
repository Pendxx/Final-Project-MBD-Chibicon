<?php
// header.php - Shared HTML header and TopNavBar
// Expects: $page_title, $active_menu
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title><?= htmlspecialchars($page_title ?? 'Chibicon Admin') ?></title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com" rel="preconnect"/>
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    
    <!-- Material Symbols -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    
    <!-- Custom CSS/JS -->
    <link href="assets/css/app.css" rel="stylesheet" />
    <script src="assets/js/app.js" defer></script>
    
    <!-- Tailwind Configuration -->
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "on-surface": "#151c27",
                        "surface-container-low": "#f0f3ff",
                        "surface-variant": "#dce2f3",
                        "primary": "#b61722",
                        "on-primary": "#ffffff",
                        "on-tertiary-fixed-variant": "#004395",
                        "on-tertiary": "#ffffff",
                        "on-secondary-fixed": "#231b00",
                        "secondary-fixed": "#ffe083",
                        "on-surface-variant": "#5b403e",
                        "tertiary-fixed-dim": "#adc6ff",
                        "secondary-fixed-dim": "#eec200",
                        "on-primary-fixed-variant": "#930013",
                        "primary-container": "#da3437",
                        "inverse-surface": "#2a313d",
                        "outline": "#8f6f6d",
                        "secondary-container": "#fed01b",
                        "surface": "#f9f9ff",
                        "tertiary": "#0058be",
                        "surface-container-high": "#e2e8f8",
                        "primary-fixed": "#ffdad7",
                        "surface-container": "#e7eefe",
                        "outline-variant": "#e4beba",
                        "inverse-primary": "#ffb3ad",
                        "secondary": "#735c00",
                        "error": "#ba1a1a",
                        "on-secondary": "#ffffff",
                        "tertiary-container": "#2170e4",
                        "inverse-on-surface": "#ebf1ff",
                        "error-container": "#ffdad6",
                        "surface-bright": "#f9f9ff",
                        "background": "#f9f9ff",
                        "on-error-container": "#93000a",
                        "surface-container-highest": "#dce2f3",
                        primary: '#b61722',
                        'primary-container': '#93000a',
                        'primary-fixed': '#ffb3ad',
                        'on-primary': '#ffffff',
                        'on-primary-container': '#ffdad6',
                        'on-primary-fixed': '#410002',
                        secondary: '#D0C300',
                        'secondary-container': '#E7DB00',
                        'secondary-fixed': '#FFF000',
                        'on-secondary': '#ffffff',
                        'on-secondary-container': '#3E3A00',
                        'on-secondary-fixed': '#282500',
                        surface: '#F9FAFB',
                        'surface-variant': '#E5E7EB',
                        'on-surface': '#111827',
                        'on-surface-variant': '#4B5563',
                        background: '#F9FAFB',
                        'on-background': '#111827',
                        error: '#B3261E',
                        'error-container': '#F9DEDC',
                        'on-error': '#FFFFFF',
                        'on-error-container': '#410E0B',
                        'outline': '#79747E',
                        'outline-variant': '#CAC4D0',
                        'surface-container-lowest': '#FFFFFF',
                        'surface-container-low': '#F3F4F6',
                        'surface-container': '#E5E7EB',
                        'surface-container-high': '#D1D5DB',
                        'surface-container-highest': '#9CA3AF',
                        'surface-bright': '#F9FAFB',
                        'surface-dim': '#D1D5DB'
                    },
                    spacing: {
                        'gutter': '24px',
                        'container-margin': '32px',
                        'card-padding': '24px',
                        'section-gap': '40px',
                        'stack-sm': '8px',
                        'stack-md': '16px',
                        'stack-lg': '24px',
                    },
                    borderRadius: {
                        'none': '0',
                        'sm': '4px',
                        DEFAULT: '8px',
                        'md': '12px',
                        'lg': '16px',
                        'xl': '24px',
                        'full': '9999px',
                    },
                    fontSize: {
                        'display-lg': ['57px', { lineHeight: '64px', letterSpacing: '-0.25px' }],
                        'display-md': ['45px', { lineHeight: '52px', letterSpacing: '0px' }],
                        'display-sm': ['36px', { lineHeight: '44px', letterSpacing: '0px' }],
                        'headline-lg': ['32px', { lineHeight: '40px', letterSpacing: '0px' }],
                        'headline-md': ['28px', { lineHeight: '36px', letterSpacing: '0px' }],
                        'headline-sm': ['24px', { lineHeight: '32px', letterSpacing: '0px' }],
                        'title-lg': ['22px', { lineHeight: '28px', letterSpacing: '0px', fontWeight: '500' }],
                        'title-md': ['16px', { lineHeight: '24px', letterSpacing: '0.15px', fontWeight: '500' }],
                        'title-sm': ['14px', { lineHeight: '20px', letterSpacing: '0.1px', fontWeight: '500' }],
                        'body-lg': ['16px', { lineHeight: '24px', letterSpacing: '0.5px' }],
                        'body-md': ['14px', { lineHeight: '20px', letterSpacing: '0.25px' }],
                        'body-sm': ['12px', { lineHeight: '16px', letterSpacing: '0.4px' }],
                        'label-lg': ['14px', { lineHeight: '20px', letterSpacing: '0.1px', fontWeight: '500' }],
                        'label-md': ['12px', { lineHeight: '16px', letterSpacing: '0.5px', fontWeight: '500' }],
                        'label-sm': ['11px', { lineHeight: '16px', letterSpacing: '0.5px', fontWeight: '500' }],
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-background text-on-background font-body-md min-h-screen flex antialiased transition-colors duration-200">
    
    <?php include __DIR__ . '/sidebar.php'; ?>

    <!-- Main Content Wrapper -->
    <div class="flex-1 flex flex-col md:ml-[280px] min-h-screen transition-colors duration-200">
        <!-- Top App Bar -->
        <header class="sticky top-0 bg-background border-b border-outline-variant px-gutter py-3 flex items-center justify-between z-40 transition-colors duration-200">
            <div class="flex items-center gap-4">
                <button class="md:hidden text-on-surface-variant hover:bg-surface-container-low p-2 rounded-lg transition-colors">
                    <span class="material-symbols-outlined">menu</span>
                </button>
                <div class="hidden sm:block">
                    <h2 class="font-title-lg text-title-lg font-semibold text-on-surface"><?= htmlspecialchars($page_title ?? 'Dashboard') ?></h2>
                    <p class="font-label-sm text-label-sm text-on-surface-variant mt-0.5"><?= date('l, d F Y') ?></p>
                </div>
            </div>

            <!-- Header Actions -->
            <div class="flex items-center gap-3">
                <!-- Notification Bell -->
                <div class="relative" id="notifWrapper">
                    <button id="notifBtn" onclick="toggleNotif()" class="text-on-surface-variant hover:bg-surface-container-low transition-colors w-10 h-10 rounded-full flex items-center justify-center relative">
                        <span class="material-symbols-outlined">notifications</span>
                        <span class="absolute top-2 right-2 w-2 h-2 bg-primary rounded-full" id="notifDot"></span>
                    </button>
                    <!-- Dropdown Panel -->
                    <div id="notifPanel" class="hidden absolute right-0 top-12 w-80 bg-surface-container-lowest border border-outline-variant rounded-xl shadow-lg z-50 overflow-hidden transition-colors duration-200">
                        <div class="px-4 py-3 border-b border-outline-variant flex justify-between items-center">
                            <span class="font-semibold text-on-surface text-sm">Notifikasi</span>
                            <button onclick="clearNotif()" class="text-xs text-primary hover:underline">Tandai semua dibaca</button>
                        </div>
                        <div id="notifList" class="divide-y divide-outline-variant max-h-72 overflow-y-auto">
                            <div class="px-4 py-3 flex gap-3 items-start hover:bg-surface-container-low cursor-pointer transition-colors" data-notif>
                                <span class="material-symbols-outlined text-primary text-[20px] mt-0.5">confirmation_number</span>
                                <div><div class="text-sm font-medium text-on-surface">Tiket baru terjual</div><div class="text-xs text-on-surface-variant mt-0.5">Transaksi #TRX-001 - Lunas</div></div>
                            </div>
                            <div class="px-4 py-3 flex gap-3 items-start hover:bg-surface-container-low cursor-pointer transition-colors" data-notif>
                                <span class="material-symbols-outlined text-secondary text-[20px] mt-0.5">storefront</span>
                                <div><div class="text-sm font-medium text-on-surface">Booth baru ditambahkan</div><div class="text-xs text-on-surface-variant mt-0.5">Booth A-01 - Indoor</div></div>
                            </div>
                            <div class="px-4 py-3 flex gap-3 items-start hover:bg-surface-container-low cursor-pointer transition-colors" data-notif>
                                <span class="material-symbols-outlined text-success text-[20px] mt-0.5">person_add</span>
                                <div><div class="text-sm font-medium text-on-surface">Staff baru terdaftar</div><div class="text-xs text-on-surface-variant mt-0.5">Divisi Ticketing</div></div>
                            </div>
                        </div>
                        <div id="notifEmpty" class="hidden px-4 py-6 text-center text-sm text-on-surface-variant">
                            <span class="material-symbols-outlined text-[32px] block mb-1">notifications_off</span>Tidak ada notifikasi baru.
                        </div>
                    </div>
                </div>

                <div class="w-px h-6 bg-outline-variant mx-1 hidden sm:block"></div>

                <!-- Switch View (Dark Mode Toggle) -->
                <button id="darkToggle" onclick="toggleDark()" class="hidden lg:flex items-center gap-1.5 text-on-surface font-label-md text-label-md px-3 py-1.5 border border-outline-variant rounded-md hover:bg-surface-container-low transition-colors">
                    <span class="material-symbols-outlined text-[16px]" id="darkIcon">dark_mode</span>
                    <span id="darkLabel">Dark Mode</span>
                </button>

                <!-- Quick Action -->
                <button onclick="openQuickAction()" class="bg-primary text-on-primary font-label-md text-label-md px-4 py-1.5 rounded-md font-semibold hover:bg-primary-container transition-colors shadow-sm hidden md:block">
                    Quick Action
                </button>

                <div class="w-8 h-8 rounded-full bg-surface-container-highest border border-outline-variant overflow-hidden ml-2 cursor-pointer">
                    <?php $u = $_SESSION['user'] ?? []; ?>
                    <?php if (!empty($u['avatar'])): ?>
                        <img alt="<?= htmlspecialchars($u['name'] ?? 'User') ?>" class="w-full h-full object-cover" src="<?= htmlspecialchars($u['avatar']) ?>">
                    <?php else: ?>
                        <div class="w-full h-full bg-primary-fixed text-on-primary-fixed flex items-center justify-center font-bold text-xs uppercase">
                            <?= strtoupper(substr($u['name'] ?? 'A', 0, 2)) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </header>
        
        <?php include __DIR__ . '/toast.php'; ?>

<!-- ── Quick Action Modal ─────────────────────────────────────────────────────── -->
<div id="quickActionModal" class="fixed inset-0 z-[9999] hidden flex items-center justify-center">
    <div class="absolute inset-0 bg-black/50 modal-overlay opacity-0" onclick="closeQuickAction()"></div>
    <div class="relative bg-surface-container-lowest rounded-2xl shadow-2xl w-full max-w-sm mx-4 modal-content scale-95 opacity-0 transition-colors duration-200">
        <div class="px-6 py-4 border-b border-outline-variant flex justify-between items-center">
            <h3 class="font-title-lg font-bold text-on-surface">Quick Actions</h3>
            <button onclick="closeQuickAction()" class="text-on-surface-variant hover:bg-surface-container-low rounded-full p-1 transition-colors"><span class="material-symbols-outlined">close</span></button>
        </div>
        <div class="p-5 grid grid-cols-2 gap-3">
            <a href="tiket.php?tab=transaksi" onclick="closeQuickAction()" class="flex flex-col items-center justify-center p-4 rounded-xl bg-surface-container-low hover:bg-primary hover:text-on-primary border border-outline-variant hover:border-primary transition-all text-center gap-2 group">
                <span class="material-symbols-outlined text-primary group-hover:text-on-primary text-[28px]">qr_code_scanner</span>
                <span class="font-label-md text-label-md font-semibold text-on-surface group-hover:text-on-primary">Scan / Tiket</span>
            </a>
            <a href="panitia.php" onclick="closeQuickAction()" class="flex flex-col items-center justify-center p-4 rounded-xl bg-surface-container-low hover:bg-primary hover:text-on-primary border border-outline-variant hover:border-primary transition-all text-center gap-2 group">
                <span class="material-symbols-outlined text-primary group-hover:text-on-primary text-[28px]">person_add</span>
                <span class="font-label-md text-label-md font-semibold text-on-surface group-hover:text-on-primary">Tambah Staff</span>
            </a>
            <a href="acara.php" onclick="closeQuickAction()" class="flex flex-col items-center justify-center p-4 rounded-xl bg-surface-container-low hover:bg-primary hover:text-on-primary border border-outline-variant hover:border-primary transition-all text-center gap-2 group">
                <span class="material-symbols-outlined text-primary group-hover:text-on-primary text-[28px]">event_available</span>
                <span class="font-label-md text-label-md font-semibold text-on-surface group-hover:text-on-primary">Kelola Event</span>
            </a>
            <a href="booth.php" onclick="closeQuickAction()" class="flex flex-col items-center justify-center p-4 rounded-xl bg-surface-container-low hover:bg-primary hover:text-on-primary border border-outline-variant hover:border-primary transition-all text-center gap-2 group">
                <span class="material-symbols-outlined text-primary group-hover:text-on-primary text-[28px]">storefront</span>
                <span class="font-label-md text-label-md font-semibold text-on-surface group-hover:text-on-primary">Kelola Booth</span>
            </a>
        </div>
    </div>
</div>

