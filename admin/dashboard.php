<?php
// dashboard.php - Dashboard Overview
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth_check.php';

// Data Fetching
// 1. Total Pengunjung
$stmt = $db->query("SELECT COUNT(*) as total FROM Pengunjung");
$total_visitors = $stmt->fetch()['total'];

// 2. Total Pendapatan
$stmt = $db->query("SELECT SUM(t.total) as total FROM Transaksi t JOIN Status_Pembayaran sp ON t.status_pembayaran_id = sp.id WHERE sp.nama_status = 'Lunas'");
$total_revenue = $stmt->fetch()['total'] ?? 0;

// 3. Tiket Terjual
$stmt = $db->query("SELECT (SELECT SUM(kuantitas) FROM Detail_Transaksi dt JOIN Transaksi t ON dt.transaksi_id = t.id JOIN Status_Pembayaran sp ON t.status_pembayaran_id = sp.id WHERE sp.nama_status = 'Lunas') as sold, (SELECT SUM(kuota) FROM Tiket) as remaining");
$tickets = $stmt->fetch();
$tickets_sold = $tickets['sold'] ?? 0;
$tickets_remaining = $tickets['remaining'] ?? 0;
$tickets_total = $tickets_sold + $tickets_remaining;

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

// --- Chart Data Fetching ---

// 1. Daily Sales Trend (Last 7 Days)
$chart_data_filled = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $chart_data_filled[$date] = 0;
}

$sales_stmt = $db->query("
    SELECT DATE(tgl_wkt_transaksi) as sale_date, SUM(total) as daily_revenue 
    FROM Transaksi 
    JOIN Status_Pembayaran sp ON Transaksi.status_pembayaran_id = sp.id 
    WHERE sp.nama_status = 'Lunas' 
    AND tgl_wkt_transaksi >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    GROUP BY DATE(tgl_wkt_transaksi)
    ORDER BY sale_date ASC
");
$sales_results = $sales_stmt->fetchAll();

foreach ($sales_results as $row) {
    if (isset($chart_data_filled[$row['sale_date']])) {
        $chart_data_filled[$row['sale_date']] = (float)$row['daily_revenue'];
    }
}

$chart_labels = [];
$chart_revenues = [];
foreach ($chart_data_filled as $date => $revenue) {
    $chart_labels[] = date('d M', strtotime($date));
    $chart_revenues[] = $revenue;
}

// 2. Ticket Distribution
$dist_stmt = $db->query("
    SELECT tk.nama_tiket, SUM(dt.kuantitas) as sold_count
    FROM Detail_Transaksi dt
    JOIN Tiket tk ON dt.tiket_id = tk.id
    JOIN Transaksi t ON dt.transaksi_id = t.id
    JOIN Status_Pembayaran sp ON t.status_pembayaran_id = sp.id
    WHERE sp.nama_status = 'Lunas'
    GROUP BY tk.id, tk.nama_tiket
");
$dist_data = $dist_stmt->fetchAll();

$dist_labels = [];
$dist_counts = [];
foreach ($dist_data as $row) {
    $dist_labels[] = $row['nama_tiket'];
    $dist_counts[] = (int)$row['sold_count'];
}

// Layout Config
$page_title = "Dashboard Overview";
$active_menu = "dashboard";
include __DIR__ . '/../components/header.php';
?>

<main class="flex-1 p-gutter md:p-container-margin overflow-y-auto">
    <!-- Page Welcome Header -->
    <div class="mb-12 animate-fade-in-up">
        <div class="flex items-center gap-3 mb-2">
            <span class="rounded-full px-3 py-1 bg-primary/10 text-primary text-[10px] uppercase tracking-[0.2em] font-bold">Chibicon Management v2.0</span>
        </div>
        <h2 class="font-extrabold text-4xl tracking-tight text-[#111827]">Dashboard Overview</h2>
        <p class="text-on-surface-variant mt-2 max-w-2xl font-medium">Selamat datang kembali, admin! Berikut adalah ringkasan data operasional Chibicon secara real-time.</p>
    </div>

    <!-- Summary Bento Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
        <!-- Card 1: Visitors -->
        <div class="premium-card-outer animate-fade-in-up stagger-1">
            <div class="premium-card-inner p-8 flex flex-col justify-between">
                <div class="flex justify-between items-start">
                    <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center shadow-soft">
                        <span class="material-symbols-outlined text-2xl">group</span>
                    </div>
                </div>
                <div class="mt-8">
                    <p class="text-[11px] uppercase tracking-widest font-bold text-on-surface-variant opacity-60">Total Pengunjung</p>
                    <h3 class="text-3xl font-extrabold mt-1 tracking-tight"><?= number_format($total_visitors) ?></h3>
                </div>
            </div>
        </div>

        <!-- Card 2: Revenue -->
        <div class="premium-card-outer animate-fade-in-up stagger-2">
            <div class="premium-card-inner p-8 flex flex-col justify-between">
                <div class="flex justify-between items-start">
                    <div class="w-12 h-12 rounded-2xl bg-green-50 text-green-600 flex items-center justify-center shadow-soft">
                        <span class="material-symbols-outlined text-2xl">payments</span>
                    </div>
                </div>
                <div class="mt-8">
                    <p class="text-[11px] uppercase tracking-widest font-bold text-on-surface-variant opacity-60">Total Pendapatan</p>
                    <h3 class="text-3xl font-extrabold mt-1 tracking-tight"><?= format_currency($total_revenue) ?></h3>
                </div>
            </div>
        </div>

        <!-- Card 3: Tickets -->
        <div class="premium-card-outer animate-fade-in-up stagger-3">
            <div class="premium-card-inner p-8 flex flex-col justify-between">
                <div class="flex justify-between items-start">
                    <div class="w-12 h-12 rounded-2xl bg-red-50 text-primary flex items-center justify-center shadow-soft">
                        <span class="material-symbols-outlined text-2xl">confirmation_number</span>
                    </div>
                </div>
                <div class="mt-8">
                    <p class="text-[11px] uppercase tracking-widest font-bold text-on-surface-variant opacity-60">Tiket Terjual</p>
                    <h3 class="text-3xl font-extrabold mt-1 tracking-tight"><?= number_format($tickets_sold) ?></h3>
                </div>
            </div>
        </div>

        <!-- Card 4: Booths -->
        <div class="premium-card-outer animate-fade-in-up stagger-4">
            <div class="premium-card-inner p-8 flex flex-col justify-between">
                <div class="flex justify-between items-start">
                    <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center shadow-soft">
                        <span class="material-symbols-outlined text-2xl">storefront</span>
                    </div>
                </div>
                <div class="mt-8">
                    <p class="text-[11px] uppercase tracking-widest font-bold text-on-surface-variant opacity-60">Booth Aktif</p>
                    <h3 class="text-3xl font-extrabold mt-1 tracking-tight"><?= $booths_active ?> <span class="text-sm font-bold text-on-surface-variant">/ <?= $booths_total ?></span></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Section - Asymmetrical Bento -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-12">
        <!-- Daily Sales Chart - Large Card -->
        <div class="lg:col-span-2 premium-card-outer animate-fade-in-up stagger-2">
            <div class="premium-card-inner p-10">
                <div class="flex justify-between items-center mb-10">
                    <div>
                        <h3 class="text-2xl font-extrabold tracking-tight">Tren Penjualan Harian</h3>
                        <p class="text-sm text-on-surface-variant font-medium mt-1">Data penjualan dalam 7 hari terakhir.</p>
                    </div>
                    <div class="flex gap-2">
                        <button id="todayBtn" onclick="filterToday()" class="px-4 py-2 rounded-xl text-xs font-bold border border-outline hover:bg-surface-dim transition-all active:scale-95 shadow-soft">Lihat Hari Ini</button>
                    </div>
                </div>
                <div class="h-[350px] w-full">
                    <canvas id="salesChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Ticket Distribution Chart - Small Card -->
        <div class="premium-card-outer animate-fade-in-up stagger-3">
            <div class="premium-card-inner p-10 flex flex-col">
                <h3 class="text-2xl font-extrabold tracking-tight mb-2">Proporsi Tiket</h3>
                <p class="text-sm text-on-surface-variant font-medium mb-10">Distribusi jenis tiket terjual.</p>
                <div class="flex-1 flex items-center justify-center">
                    <div class="h-[280px] w-full">
                        <canvas id="distChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Transactions Area -->
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
        <!-- Transaction Table -->
        <div class="lg:col-span-3 premium-card-outer animate-fade-in-up stagger-4">
            <div class="premium-card-inner flex flex-col overflow-hidden">
                <div class="px-10 py-8 border-b border-outline-variant flex justify-between items-center bg-surface-bright">
                    <h3 class="text-2xl font-extrabold tracking-tight">Transaksi Terkini</h3>
                    <a href="tiket.php" class="group flex items-center gap-2 text-primary font-bold text-sm hover:underline">
                        Lihat Semua 
                        <span class="material-symbols-outlined text-[18px] group-hover:translate-x-1 transition-transform">arrow_forward</span>
                    </a>
                </div>
                <div class="overflow-x-auto">
                    <table class="premium-table">
                        <thead>
                            <tr>
                                <th>Invoice</th>
                                <th>Nama Lengkap</th>
                                <th class="text-right">Total Tagihan</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody id="recentTableBody">
                            <?php foreach($recent_transactions as $trx): 
                                $trx_date = date('d/m/Y', strtotime($trx['time']));
                            ?>
                            <tr class="group" data-date="<?= $trx_date ?>">
                                <td class="font-bold text-primary"><?= htmlspecialchars($trx['trx_code']) ?></td>
                                <td class="font-semibold"><?= htmlspecialchars($trx['visitor_name']) ?></td>
                                <td class="text-right font-bold"><?= format_currency($trx['amount']) ?></td>
                                <td class="text-center">
                                    <?php if($trx['status'] == 'Lunas'): ?>
                                        <span class="px-3 py-1 rounded-full badge-success text-[10px] font-bold uppercase tracking-wider">Paid</span>
                                    <?php elseif($trx['status'] == 'Menunggu Pembayaran'): ?>
                                        <span class="px-3 py-1 rounded-full badge-warning text-[10px] font-bold uppercase tracking-wider">Pending</span>
                                    <?php else: ?>
                                        <span class="px-3 py-1 rounded-full badge-error text-[10px] font-bold uppercase tracking-wider">Failed</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Quick Info / System Health -->
        <div class="flex flex-col gap-6 animate-fade-in-up stagger-4">
            <div class="premium-card-outer">
                <div class="premium-card-inner p-8">
                    <h3 class="font-extrabold text-xl mb-6">Status Sistem</h3>
                    <div class="space-y-8">
                        <div>
                            <div class="flex justify-between items-center mb-3">
                                <span class="text-[11px] uppercase tracking-widest font-bold text-on-surface-variant">Server Load</span>
                                <span class="text-sm font-extrabold text-primary" id="server-load-text">24%</span>
                            </div>
                            <div class="w-full bg-surface-dim rounded-full h-2">
                                <div class="bg-primary h-2 rounded-full transition-all duration-1000 shadow-soft" id="server-load-bar" style="width: 24%"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex justify-between items-center mb-3">
                                <span class="text-[11px] uppercase tracking-widest font-bold text-on-surface-variant">Database Storage</span>
                                <span class="text-sm font-extrabold">3.2 GB</span>
                            </div>
                            <div class="w-full bg-surface-dim rounded-full h-2 overflow-hidden">
                                <div class="bg-[#111827] h-2 rounded-full" style="width: 15%"></div>
                            </div>
                            <p class="text-[10px] text-on-surface-variant mt-3 font-medium flex items-center gap-1">
                                <span class="material-symbols-outlined text-[14px] text-green-500">check_circle</span> 
                                Infrastruktur berjalan normal
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
            <a href="report.php" target="_blank" class="group premium-card-outer hover:scale-[1.02] transition-all duration-500 active:scale-95">
                <div class="premium-card-inner p-8 bg-primary text-white flex items-center justify-between">
                    <div>
                        <h4 class="font-extrabold text-lg">Export Report</h4>
                        <p class="text-xs opacity-70 font-medium mt-1">Unduh laporan PDF lengkap</p>
                    </div>
                    <div class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center group-hover:rotate-12 transition-transform">
                        <span class="material-symbols-outlined">picture_as_pdf</span>
                    </div>
                </div>
            </a>
        </div>
    </div>
</main>

<script>
    // --- Chart.js Initialization ---
    Chart.defaults.color = '#A1A1AA';
    Chart.defaults.font.family = "'Geist', sans-serif";
    
    const salesCtx = document.getElementById('salesChart').getContext('2d');
    const distCtx = document.getElementById('distChart').getContext('2d');

    // 1. Daily Sales Chart
    new Chart(salesCtx, {
        type: 'line',
        data: {
            labels: <?= json_encode($chart_labels) ?>,
            datasets: [{
                label: 'Revenue (IDR)',
                data: <?= json_encode($chart_revenues) ?>,
                borderColor: '#B61722',
                backgroundColor: 'rgba(182, 23, 34, 0.05)',
                borderWidth: 4,
                fill: true,
                tension: 0.4,
                pointRadius: 0,
                pointHoverRadius: 6,
                pointHoverBackgroundColor: '#B61722',
                pointHoverBorderColor: '#fff',
                pointHoverBorderWidth: 3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { intersect: false, mode: 'index' },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#111827',
                    padding: 12,
                    titleFont: { size: 14, weight: 'bold' },
                    bodyFont: { size: 13 },
                    callbacks: {
                        label: (context) => ' Rp ' + context.parsed.y.toLocaleString()
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(0,0,0,0.03)', drawBorder: false },
                    ticks: {
                        callback: (v) => 'Rp ' + (v/1000) + 'k',
                        font: { size: 11, weight: '600' }
                    }
                },
                x: {
                    grid: { display: false },
                    ticks: { font: { size: 11, weight: '600' } }
                }
            }
        }
    });

    // 2. Ticket Distribution Chart
    new Chart(distCtx, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($dist_labels) ?>,
            datasets: [{
                data: <?= json_encode($dist_counts) ?>,
                backgroundColor: [
                    '#B61722', '#4B5563', '#10B981', '#F59E0B', '#6366F1'
                ],
                borderWidth: 8,
                borderColor: '#ffffff',
                hoverOffset: 20
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        usePointStyle: true,
                        padding: 25,
                        font: { size: 11, weight: 'bold' }
                    }
                }
            },
            cutout: '75%'
        }
    });

    // Simulate dynamic server load
    setInterval(() => {
        const load = Math.floor(Math.random() * 15) + 20;
        const text = document.getElementById('server-load-text');
        const bar = document.getElementById('server-load-bar');
        if(text) text.innerText = load + '%';
        if(bar) bar.style.width = load + '%';
    }, 4000);

    // ── Today Filter ──────────────────────────────────────────────────────────
    let todayActive = false;
    function filterToday() {
        todayActive = !todayActive;
        const btn  = document.getElementById('todayBtn');
        const rows = document.querySelectorAll('#recentTableBody tr');
        const now = new Date();
        const todayStr = String(now.getDate()).padStart(2, '0') + '/' + String(now.getMonth() + 1).padStart(2, '0') + '/' + now.getFullYear();

        if (todayActive) {
            btn.classList.add('bg-primary', 'text-white', 'border-primary');
            btn.classList.remove('bg-white', 'text-on-surface');
        } else {
            btn.classList.remove('bg-primary', 'text-white', 'border-primary');
            btn.classList.add('bg-white', 'text-on-surface');
        }

        rows.forEach(row => {
            row.style.display = (!todayActive || row.getAttribute('data-date') === todayStr) ? '' : 'none';
        });
    }
</script>
</body>
</html>
