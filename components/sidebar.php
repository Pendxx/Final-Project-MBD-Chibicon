<?php
// sidebar.php - Shared Side Navigation Component
$active = $active_menu ?? 'dashboard';

function nav_class($menu, $active) {
    $base = "flex items-center gap-3 px-4 py-3 rounded-lg font-title-md text-title-md active:scale-95 transition-all duration-200";
    if ($menu === $active) {
        return "$base bg-primary-fixed text-primary border-l-4 border-primary font-bold";
    }
    return "$base text-on-surface-variant hover:bg-surface-container-low transition-colors border-l-4 border-transparent";
}

function icon_fill($menu, $active) {
    return $menu === $active ? 'font-variation-settings: \'FILL\' 1;' : '';
}
?>
<aside class="w-[280px] h-screen fixed left-0 top-0 hidden md:flex flex-col bg-surface-container-lowest border-r border-outline-variant shadow-sm z-50 py-stack-md flex-shrink-0">
    <!-- Header -->
    <div class="px-gutter mb-8 flex items-center gap-3">
        <div class="w-10 h-10 rounded-lg bg-primary-container flex items-center justify-center text-on-primary-container">
            <span class="material-symbols-outlined font-bold">festival</span>
        </div>
        <div>
            <h1 class="font-headline-md text-headline-md font-bold text-primary">Chibicon Admin</h1>
            <p class="font-label-md text-label-md text-on-surface-variant">Event Management</p>
        </div>
    </div>
    
    <!-- CTA -->
    <div class="px-gutter mb-6">
        <a href="acara.php?action=create" class="w-full bg-primary text-on-primary font-title-md text-title-md py-2 px-4 rounded-lg font-bold hover:bg-primary-container transition-colors shadow-sm flex items-center justify-center gap-2 active:scale-95 duration-200">
            <span class="material-symbols-outlined text-[20px]">add</span>
            Create New Event
        </a>
    </div>

    <!-- Navigation Links -->
    <nav class="flex-1 overflow-y-auto px-4 flex flex-col gap-1">
        <a href="index.php" class="<?= nav_class('dashboard', $active) ?>">
            <span class="material-symbols-outlined" style="<?= icon_fill('dashboard', $active) ?>">dashboard</span>
            <span>Dashboard</span>
        </a>
        <a href="pengunjung.php" class="<?= nav_class('visitors', $active) ?>">
            <span class="material-symbols-outlined" style="<?= icon_fill('visitors', $active) ?>">group</span>
            <span>Visitors</span>
        </a>
        <a href="tiket.php" class="<?= nav_class('ticketing', $active) ?>">
            <span class="material-symbols-outlined" style="<?= icon_fill('ticketing', $active) ?>">confirmation_number</span>
            <span>Ticketing</span>
        </a>
        <a href="acara.php" class="<?= nav_class('events', $active) ?>">
            <span class="material-symbols-outlined" style="<?= icon_fill('events', $active) ?>">event_available</span>
            <span>Events</span>
        </a>
        <a href="booth.php" class="<?= nav_class('bazaar', $active) ?>">
            <span class="material-symbols-outlined" style="<?= icon_fill('bazaar', $active) ?>">storefront</span>
            <span>Bazaar</span>
        </a>
        <a href="panitia.php" class="<?= nav_class('staff', $active) ?>">
            <span class="material-symbols-outlined" style="<?= icon_fill('staff', $active) ?>">badge</span>
            <span>Staff</span>
        </a>
    </nav>

    <!-- Footer Links -->
    <div class="px-4 mt-auto border-t border-outline-variant pt-4">
        <!-- Logged-in user info -->
        <?php $u = $_SESSION['user'] ?? []; ?>
        <div class="flex items-center gap-3 px-4 py-3 mb-1">
            <?php if (!empty($u['avatar'])): ?>
                <img src="<?= htmlspecialchars($u['avatar']) ?>" alt="avatar" class="w-8 h-8 rounded-full object-cover border border-outline-variant">
            <?php else: ?>
                <div class="w-8 h-8 rounded-full bg-primary-fixed text-on-primary-fixed flex items-center justify-center font-bold text-xs uppercase">
                    <?= strtoupper(substr($u['name'] ?? 'A', 0, 2)) ?>
                </div>
            <?php endif; ?>
            <div class="min-w-0">
                <div class="font-semibold text-on-surface text-sm truncate"><?= htmlspecialchars($u['name'] ?? 'Admin') ?></div>
                <div class="text-xs text-on-surface-variant truncate"><?= htmlspecialchars($u['email'] ?? '') ?></div>
            </div>
        </div>
        <a href="logout.php" class="flex items-center gap-3 px-4 py-3 rounded-lg text-error hover:bg-error-container transition-colors font-title-md text-title-md">
            <span class="material-symbols-outlined">logout</span>
            <span>Logout</span>
        </a>
    </div>
</aside>
