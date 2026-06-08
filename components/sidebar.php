<?php
// sidebar.php - Elite Ethereal Glass Sidebar
$active = $active_menu ?? 'dashboard';

function nav_class($menu, $active) {
    $base = "group flex items-center gap-4 px-6 py-4 rounded-[18px] font-bold text-sm transition-all duration-300 active:scale-95";
    if ($menu === $active) {
        return "$base bg-surface-dim text-on-surface shadow-[inset_0_1px_1px_rgba(255,255,255,0.1)] border border-outline";
    }
    return "$base text-on-surface-variant hover:bg-surface-dim hover:text-on-surface border border-transparent";
}

function icon_class($menu, $active) {
    if ($menu === $active) return "text-primary";
    return "text-on-surface-variant group-hover:text-primary transition-colors";
}
?>
<!-- Sidebar Overlay for Mobile -->
<div id="sidebar-overlay" class="fixed inset-0 bg-black/60 backdrop-blur-md z-[45] md:hidden hidden" onclick="toggleSidebar()"></div>

<aside class="w-[280px] h-[100dvh] fixed left-0 top-0 hidden md:flex flex-col bg-glass backdrop-blur-3xl border-r border-outline z-50 transition-all duration-700 ease-premium">
    <!-- Header / Brand -->
    <div class="px-8 pt-12 pb-10 flex items-center gap-4 relative">
        <div class="absolute -top-10 -left-10 w-32 h-32 bg-primary/20 rounded-full blur-3xl pointer-events-none"></div>
        <div class="w-12 h-12 rounded-[18px] bg-gradient-to-br from-primary to-[#ff8c42] flex items-center justify-center text-white shadow-[0_0_20px_var(--accent)] relative z-10">
            <span class="material-symbols-outlined font-bold text-2xl">festival</span>
        </div>
        <div class="relative z-10">
            <h1 class="font-extrabold text-2xl tracking-tighter text-on-surface">Chibicon</h1>
            <p class="text-[9px] uppercase tracking-[0.3em] font-black text-primary mt-1">Command Center</p>
        </div>
    </div>
    
    <!-- Navigation Links -->
    <nav class="flex-1 overflow-y-auto px-4 flex flex-col gap-2 relative z-10">
        <a href="dashboard.php" class="<?= nav_class('dashboard', $active) ?>">
            <span class="material-symbols-outlined <?= icon_class('dashboard', $active) ?>">grid_view</span>
            <span>Dashboard</span>
        </a>
        <a href="pengunjung.php" class="<?= nav_class('visitors', $active) ?>">
            <span class="material-symbols-outlined <?= icon_class('visitors', $active) ?>">group</span>
            <span>Visitors</span>
        </a>
        <a href="tiket.php" class="<?= nav_class('ticketing', $active) ?>">
            <span class="material-symbols-outlined <?= icon_class('ticketing', $active) ?>">confirmation_number</span>
            <span>Ticketing</span>
        </a>
        <a href="acara.php" class="<?= nav_class('events', $active) ?>">
            <span class="material-symbols-outlined <?= icon_class('events', $active) ?>">event_available</span>
            <span>Events & Stage</span>
        </a>
        <a href="booth.php" class="<?= nav_class('bazaar', $active) ?>">
            <span class="material-symbols-outlined <?= icon_class('bazaar', $active) ?>">storefront</span>
            <span>Bazaar Map</span>
        </a>
        <a href="panitia.php" class="<?= nav_class('staff', $active) ?>">
            <span class="material-symbols-outlined <?= icon_class('staff', $active) ?>">badge</span>
            <span>Staff Roster</span>
        </a>
    </nav>

    <!-- Footer Profile -->
    <div class="p-6 mt-auto border-t border-outline bg-surface-bright relative z-10">
        <?php $u = $_SESSION['user'] ?? []; ?>
        <a href="logout.php" class="group flex items-center justify-between px-4 py-3 rounded-2xl bg-surface-dim hover:bg-error/20 border border-outline hover:border-error/30 transition-all duration-300 active:scale-95">
            <span class="font-bold text-sm text-on-surface-variant group-hover:text-red-500 transition-colors">Sign Out</span>
            <span class="material-symbols-outlined text-[20px] text-on-surface-variant group-hover:text-red-500 transition-colors">logout</span>
        </a>
    </div>
</aside>
