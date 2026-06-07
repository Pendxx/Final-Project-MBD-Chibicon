<?php
// index.php - Dashboard Overview
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth_check.php';

// Data Fetching
// 1. Total Pengunjung
$stmt = $db->query("SELECT COUNT(*) as total FROM Pengunjung");
$total_visitors = $stmt->fetch()['total'];

// 2. Total Pendapatan
$stmt = $db->query("SELECT SUM(t.total) as total FROM Transaksi t JOIN Status_Pembayaran sp ON t.status_pembayaran_id = sp.id WHERE sp.nama_status = 'Lunas'");
$total_revenue = $stmt->fetch()['total'] ?? 0;

// 3. Tiket Terjual
$stmt = $db->query("SELECT (SELECT SUM(kuantitas) FROM Detail_Transaksi) as sold, (SELECT SUM(kuota) FROM Tiket) as quota");
$tickets = $stmt->fetch();
$tickets_sold = $tickets['sold'] ?? 0;
$tickets_quota = $tickets['quota'] ?? 0;

// 4. Booth Aktif
$stmt = $db->query("SELECT COUNT(*) as active FROM Booth WHERE tenant_id IS NOT NULL");
$booths_active = $stmt->fetch()['active'];
$stmt = $db->query("SELECT COUNT(*) as total FROM Booth");
$booths_total = $stmt->fetch()['total'];

// Recent Transactions
$stmt = $db->query("
    SELECT 
        CONCAT('TRX-', LPAD(t.id, 4, '0')) as trx_code,
        p.nama_lengkap as visitor_name,
        t.total as amount,
        sp.nama_status as status,
        t.tgl_wkt_transaksi as time
    FROM Transaksi t
    LEFT JOIN Pengunjung p ON t.pengunjung_id = p.id
    LEFT JOIN Status_Pembayaran sp ON t.status_pembayaran_id = sp.id
    ORDER BY t.tgl_wkt_transaksi DESC LIMIT 10
");
$recent_transactions = $stmt->fetchAll();

// Export PDF - fetch all transactions then render print page
if (isset($_GET['export']) && $_GET['export'] === 'pdf') {
    $all_trx = $db->query("
        SELECT 
            CONCAT('TRX-', LPAD(t.id, 4, '0')) as trx_code,
            p.nama_lengkap as visitor_name,
            t.total as amount,
            sp.nama_status as status,
            mp.nama_metode as payment_method,
            t.tgl_wkt_transaksi as time
        FROM Transaksi t
        LEFT JOIN Pengunjung p ON t.pengunjung_id = p.id
        LEFT JOIN Status_Pembayaran sp ON t.status_pembayaran_id = sp.id
        LEFT JOIN Metode_Pembayaran mp ON t.metode_pembayaran_id = mp.id
        ORDER BY t.tgl_wkt_transaksi DESC
    ")->fetchAll();
    $total_rev = $db->query("SELECT SUM(t.total) as t FROM Transaksi t JOIN Status_Pembayaran sp ON t.status_pembayaran_id = sp.id WHERE sp.nama_status = 'Lunas'")->fetch()['t'] ?? 0;
    $total_vis = $db->query("SELECT COUNT(*) as t FROM Pengunjung")->fetch()['t'] ?? 0;
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <title></title>
        <style>
            * { margin:0; padding:0; box-sizing:border-box; font-family: 'Segoe UI', sans-serif; }
            body { padding: 40px; color: #111; }
            h1 { font-size: 22px; color: #b61722; margin-bottom: 4px; }
            .meta { font-size: 12px; color: #666; margin-bottom: 24px; }
            .stats { display:flex; gap:20px; margin-bottom:24px; }
            .stat-box { border:1px solid #ddd; border-radius:8px; padding:12px 20px; flex:1; }
            .stat-box .label { font-size:11px; color:#888; text-transform:uppercase; }
            .stat-box .value { font-size:20px; font-weight:700; color:#b61722; }
            table { width:100%; border-collapse:collapse; font-size:13px; }
            thead th { background:#b61722; color:#fff; padding:10px 12px; text-align:left; font-size:11px; text-transform:uppercase; }
            tbody tr:nth-child(even) { background:#fafafa; }
            tbody td { padding:9px 12px; border-bottom:1px solid #eee; }
            .badge { display:inline-block; padding:2px 8px; border-radius:999px; font-size:11px; font-weight:600; }
            .badge.lunas { background:#DEF7EC; color:#03543F; }
            .badge.pending { background:#FDF6B2; color:#723B13; }
            .badge.gagal { background:#FDE8E8; color:#9B1C1C; }
            @media print { 
                .no-print { display:none; } 
                @page { margin: 0mm; size: auto; }
                body { margin: 20mm; }
            }
        </style>
    </head>
    <body>
        <div class="no-print" style="margin-bottom:24px">
            <button onclick="window.print()" style="background:#b61722;color:#fff;border:none;padding:10px 24px;border-radius:8px;font-size:14px;font-weight:600;cursor:pointer;">🖨️ Print / Save as PDF</button>
            <a href="index.php" style="margin-left:12px;color:#b61722;text-decoration:none;font-weight:600;">← Kembali</a>
        </div>
        <h1>Laporan Transaksi</h1>
        <div class="meta">Digenerate: <?= date('d/m/Y H:i') ?> | Total Records: <?= count($all_trx) ?></div>
        <div class="stats">
            <div class="stat-box"><div class="label">Total Pendapatan</div><div class="value">Rp <?= number_format($total_rev,0,',','.') ?></div></div>
            <div class="stat-box"><div class="label">Total Pengunjung</div><div class="value"><?= number_format($total_vis) ?></div></div>
            <div class="stat-box"><div class="label">Total Transaksi</div><div class="value"><?= count($all_trx) ?></div></div>
        </div>
        <table>
            <thead><tr><th>#</th><th>ID Transaksi</th><th>Nama Pembeli</th><th>Total</th><th>Metode</th><th>Status</th><th>Waktu</th></tr></thead>
            <tbody>
            <?php foreach ($all_trx as $i => $row): ?>
            <tr>
                <td><?= $i+1 ?></td>
                <td><?= htmlspecialchars($row['trx_code']) ?></td>
                <td><?= htmlspecialchars($row['visitor_name']) ?></td>
                <td>Rp <?= number_format($row['amount'],0,',','.') ?></td>
                <td><?= htmlspecialchars($row['payment_method']) ?></td>
                <td><span class="badge <?= strtolower($row['status']) ?>"><?= htmlspecialchars($row['status']) ?></span></td>
                <td><?= htmlspecialchars($row['time']) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <script>window.onload = function(){ window.print(); }</script>
    </body>
    </html>
    <?php
    exit;
}

// Layout Config
$page_title = "Chibicon Admin - Dashboard Overview";
$active_menu = "dashboard";
include __DIR__ . '/components/header.php';
?>

<!-- Canvas -->
<div class="p-container-margin md:p-section-gap flex-1 flex flex-col gap-section-gap">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-end gap-4">
        <div>
            <h2 class="font-display-lg text-display-lg font-bold text-on-surface tracking-tight">Dashboard Overview</h2>
            <p class="font-body-md text-body-md text-on-surface-variant mt-1">Real-time metrics and recent activities for the event.</p>
        </div>
        <div class="flex gap-2">
            <button id="todayBtn" onclick="filterToday()" class="px-4 py-2 rounded-lg bg-surface-container-lowest border border-outline-variant text-on-surface font-title-md text-title-md hover:bg-surface-container-low transition-colors flex items-center gap-2 shadow-sm">
                <span class="material-symbols-outlined text-[18px]">calendar_today</span> Today
            </button>
            <a href="?export=pdf" target="_blank" class="px-4 py-2 rounded-lg bg-surface-container-lowest border border-outline-variant text-on-surface font-title-md text-title-md hover:bg-surface-container-low transition-colors flex items-center gap-2 shadow-sm">
                <span class="material-symbols-outlined text-[18px]">picture_as_pdf</span> Export PDF
            </a>
        </div>
    </div>

    <!-- Summary Widgets Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Widget 1: Total Pengunjung -->
        <div class="bg-surface-container-lowest rounded-xl p-card-padding border border-outline-variant shadow-[0px_1px_3px_rgba(0,0,0,0.1)] relative overflow-hidden group">
            <div class="absolute top-0 right-0 p-4">
                <div class="w-10 h-10 rounded-lg bg-secondary-fixed text-on-secondary-fixed-variant flex items-center justify-center transform group-hover:scale-110 transition-transform duration-300">
                    <span class="material-symbols-outlined text-[24px]">groups</span>
                </div>
            </div>
            <div class="flex flex-col gap-2">
                <span class="font-title-md text-title-md text-on-surface-variant">Total Pengunjung</span>
                <div class="flex items-baseline gap-2">
                    <span class="font-headline-lg text-headline-lg font-bold text-on-surface tracking-tight"><?= number_format($total_visitors) ?></span>
                    <span class="font-label-md text-label-md text-surface-tint flex items-center">
                        <span class="material-symbols-outlined text-[12px]">trending_up</span> +12%
                    </span>
                </div>
            </div>
            <div class="absolute bottom-0 left-0 w-full h-1 bg-gradient-to-r from-secondary-fixed to-transparent opacity-50"></div>
        </div>

        <!-- Widget 2: Total Pendapatan -->
        <div class="bg-surface-container-lowest rounded-xl p-card-padding border border-outline-variant shadow-[0px_1px_3px_rgba(0,0,0,0.1)] relative overflow-hidden group">
            <div class="absolute top-0 right-0 p-4">
                <div class="w-10 h-10 rounded-lg bg-primary-fixed text-primary flex items-center justify-center transform group-hover:scale-110 transition-transform duration-300">
                    <span class="material-symbols-outlined text-[24px]">payments</span>
                </div>
            </div>
            <div class="flex flex-col gap-2">
                <span class="font-title-md text-title-md text-on-surface-variant">Total Pendapatan</span>
                <div class="flex items-baseline gap-1">
                    <span class="font-headline-lg text-headline-lg font-bold text-on-surface tracking-tight"><?= format_currency($total_revenue) ?></span>
                </div>
            </div>
            <div class="absolute bottom-0 left-0 w-full h-1 bg-gradient-to-r from-primary to-transparent opacity-50"></div>
        </div>

        <!-- Widget 3: Tiket Terjual -->
        <div class="bg-surface-container-lowest rounded-xl p-card-padding border border-outline-variant shadow-[0px_1px_3px_rgba(0,0,0,0.1)] relative overflow-hidden group">
            <div class="absolute top-0 right-0 p-4">
                <div class="w-10 h-10 rounded-lg bg-secondary-fixed text-on-secondary-fixed-variant flex items-center justify-center transform group-hover:scale-110 transition-transform duration-300">
                    <span class="material-symbols-outlined text-[24px]">local_activity</span>
                </div>
            </div>
            <div class="flex flex-col gap-2">
                <span class="font-title-md text-title-md text-on-surface-variant">Tiket Terjual</span>
                <div class="flex items-baseline gap-2">
                    <span class="font-headline-lg text-headline-lg font-bold text-on-surface tracking-tight"><?= number_format($tickets_sold) ?></span>
                    <span class="font-label-md text-label-md text-on-surface-variant">/ <?= number_format($tickets_quota) ?> Maks</span>
                </div>
            </div>
            <div class="w-full bg-surface-container-high rounded-full h-1.5 mt-2 overflow-hidden">
                <div class="bg-secondary-fixed-dim h-1.5 rounded-full" style="width: <?= $tickets_quota > 0 ? ($tickets_sold/$tickets_quota)*100 : 0 ?>%"></div>
            </div>
            <div class="absolute bottom-0 left-0 w-full h-1 bg-gradient-to-r from-secondary-fixed to-transparent opacity-50"></div>
        </div>

        <!-- Widget 4: Booth Aktif -->
        <div class="bg-surface-container-lowest rounded-xl p-card-padding border border-outline-variant shadow-[0px_1px_3px_rgba(0,0,0,0.1)] relative overflow-hidden group">
            <div class="absolute top-0 right-0 p-4">
                <div class="w-10 h-10 rounded-lg bg-primary-fixed text-primary flex items-center justify-center transform group-hover:scale-110 transition-transform duration-300">
                    <span class="material-symbols-outlined text-[24px]">store</span>
                </div>
            </div>
            <div class="flex flex-col gap-2">
                <span class="font-title-md text-title-md text-on-surface-variant">Jumlah Booth Aktif</span>
                <div class="flex items-baseline gap-2">
                    <span class="font-headline-lg text-headline-lg font-bold text-on-surface tracking-tight"><?= $booths_active ?></span>
                    <span class="font-label-md text-label-md text-on-surface-variant">/ <?= $booths_total ?> Maks</span>
                </div>
            </div>
            <div class="w-full bg-surface-container-high rounded-full h-1.5 mt-2 overflow-hidden">
                <div class="bg-primary h-1.5 rounded-full" style="width: <?= $booths_total > 0 ? ($booths_active/$booths_total)*100 : 0 ?>%"></div>
            </div>
        </div>
    </div>

    <!-- Main Content Area Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Recent Transactions -->
        <div class="lg:col-span-2 bg-surface-container-lowest rounded-xl border border-outline-variant shadow-[0px_1px_3px_rgba(0,0,0,0.1)] flex flex-col overflow-hidden">
            <div class="px-6 py-4 border-b border-outline-variant flex justify-between items-center bg-surface-bright">
                <h3 class="font-title-lg text-title-lg font-bold text-on-surface">Recent Transactions</h3>
                <a href="tiket.php" class="text-primary font-label-md text-label-md font-bold hover:underline">View All</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-surface-container-low border-b border-outline-variant">
                            <th class="px-6 py-3 font-label-md text-label-md font-semibold text-on-surface-variant uppercase tracking-wider">ID Transaksi</th>
                            <th class="px-6 py-3 font-label-md text-label-md font-semibold text-on-surface-variant uppercase tracking-wider">Nama Lengkap</th>
                            <th class="px-6 py-3 font-label-md text-label-md font-semibold text-on-surface-variant uppercase tracking-wider text-right">Total Tagihan</th>
                            <th class="px-6 py-3 font-label-md text-label-md font-semibold text-on-surface-variant uppercase tracking-wider text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody id="recentTableBody" class="font-body-md text-body-md text-on-surface divide-y divide-outline-variant">
                        <?php foreach($recent_transactions as $trx): 
                            $trx_date = date('d/m/Y', strtotime($trx['time']));
                        ?>
                        <tr class="hover:bg-surface-container-low transition-colors group cursor-pointer" data-date="<?= $trx_date ?>">
                            <td class="px-6 py-4 whitespace-nowrap font-medium group-hover:text-primary transition-colors"><?= htmlspecialchars($trx['trx_code']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($trx['visitor_name']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-right font-medium"><?= format_currency($trx['amount']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <?php if($trx['status'] == 'Lunas'): ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[12px] font-semibold bg-[#DEF7EC] text-[#03543F]">Paid</span>
                                <?php elseif($trx['status'] == 'Menunggu Pembayaran'): ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[12px] font-semibold bg-[#FDF6B2] text-[#723B13]">Pending</span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[12px] font-semibold bg-[#FDE8E8] text-[#9B1C1C]">Failed</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if(empty($recent_transactions)): ?>
                        <tr>
                            <td colspan="4" class="px-6 py-8 text-center text-on-surface-variant">Belum ada transaksi.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Right Column (Quick Actions & Status) -->
        <div class="flex flex-col gap-6">
            <!-- Quick Actions -->
            <div class="bg-surface-container-lowest rounded-xl p-card-padding border border-outline-variant shadow-[0px_1px_3px_rgba(0,0,0,0.1)]">
                <h3 class="font-title-lg text-title-lg font-bold text-on-surface mb-4">Quick Actions</h3>
                <div class="grid grid-cols-2 gap-3">
                    <a href="tiket.php?tab=transaksi" class="flex flex-col items-center justify-center p-4 rounded-lg bg-surface-container-low hover:bg-surface-container-high border border-transparent hover:border-outline-variant transition-all text-center gap-2">
                        <span class="material-symbols-outlined text-primary text-[28px]">qr_code_scanner</span>
                        <span class="font-label-md text-label-md font-semibold text-on-surface">Scan Ticket</span>
                    </a>
                    <button onclick="openQuickAction()" class="flex flex-col items-center justify-center p-4 rounded-lg bg-surface-container-low hover:bg-surface-container-high border border-transparent hover:border-outline-variant transition-all text-center gap-2">
                        <span class="material-symbols-outlined text-secondary text-[28px]">campaign</span>
                        <span class="font-label-md text-label-md font-semibold text-on-surface">Quick Actions</span>
                    </button>
                    <a href="panitia.php" class="flex flex-col items-center justify-center p-4 rounded-lg bg-surface-container-low hover:bg-surface-container-high border border-transparent hover:border-outline-variant transition-all text-center gap-2">
                        <span class="material-symbols-outlined text-primary text-[28px]">person_add</span>
                        <span class="font-label-md text-label-md font-semibold text-on-surface">Add Staff</span>
                    </a>
                    <a href="?export=pdf" target="_blank" class="flex flex-col items-center justify-center p-4 rounded-lg bg-surface-container-low hover:bg-surface-container-high border border-transparent hover:border-outline-variant transition-all text-center gap-2">
                        <span class="material-symbols-outlined text-secondary text-[28px]">picture_as_pdf</span>
                        <span class="font-label-md text-label-md font-semibold text-on-surface">Export PDF</span>
                    </a>
                </div>
            </div>

            <!-- System Status Glassmorphism Card -->
            <div class="rounded-xl p-card-padding border border-outline-variant shadow-[0px_1px_3px_rgba(0,0,0,0.1)] relative overflow-hidden bg-white/60 backdrop-blur-md">
                <div class="absolute inset-0 bg-gradient-to-br from-primary-fixed/30 to-transparent pointer-events-none"></div>
                <div class="relative z-10">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-title-lg text-title-lg font-bold text-on-surface">System Status</h3>
                        <span class="flex items-center gap-1 font-label-md text-[12px] text-[#03543F] bg-[#DEF7EC] px-2 py-1 rounded-full font-semibold">
                            <span class="w-1.5 h-1.5 bg-[#03543F] rounded-full animate-pulse"></span> Online
                        </span>
                    </div>
                    <div class="space-y-4">
                        <div>
                            <div class="flex justify-between font-label-md text-label-md mb-1">
                                <span class="text-on-surface-variant">Server Load</span>
                                <span class="font-bold text-on-surface" id="server-load-text">24%</span>
                            </div>
                            <div class="w-full bg-surface-container-high rounded-full h-1.5">
                                <div class="bg-primary h-1.5 rounded-full transition-all duration-1000" id="server-load-bar" style="width: 24%"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex justify-between font-label-md text-label-md mb-1">
                                <span class="text-on-surface-variant">Database Storage</span>
                                <span class="font-bold text-on-surface">3%</span>
                            </div>
                            <div class="w-full bg-surface-container-high rounded-full h-1.5">
                                <div class="bg-secondary-fixed-dim h-1.5 rounded-full" style="width: 3%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Simulate dynamic server load
    setInterval(() => {
        const load = Math.floor(Math.random() * 20) + 20;
        document.getElementById('server-load-text').innerText = load + '%';
        document.getElementById('server-load-bar').style.width = load + '%';
    }, 5000);

    // ── Today Filter ──────────────────────────────────────────────────────────
    let todayActive = false;
    function filterToday() {
        todayActive = !todayActive;
        const btn  = document.getElementById('todayBtn');
        const rows = document.querySelectorAll('#recentTableBody tr');
        
        // Ensure format exactly matches PHP date('d/m/Y')
        const now = new Date();
        const dd = String(now.getDate()).padStart(2, '0');
        const mm = String(now.getMonth() + 1).padStart(2, '0'); // January is 0!
        const yyyy = now.getFullYear();
        const todayStr = dd + '/' + mm + '/' + yyyy;

        if (todayActive) {
            btn.classList.add('bg-primary', 'text-on-primary', 'border-primary');
            btn.classList.remove('bg-surface-container-lowest', 'text-on-surface');
        } else {
            btn.classList.remove('bg-primary', 'text-on-primary', 'border-primary');
            btn.classList.add('bg-surface-container-lowest', 'text-on-surface');
        }

        rows.forEach(row => {
            if (!todayActive) { 
                row.style.display = ''; 
                return; 
            }
            if (row.getAttribute('data-date') === todayStr) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }
</script>

    </div>
</body>
</html>
