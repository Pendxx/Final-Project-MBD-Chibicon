<?php
// report.php - Professional PDF Reporting
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth_check.php';

// Fetch all transactions for the report
$stmt = $db->query("
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
");
$all_trx = $stmt->fetchAll();

// Fetch Summary Stats
$total_rev = $db->query("SELECT SUM(t.total) as t FROM Transaksi t JOIN Status_Pembayaran sp ON t.status_pembayaran_id = sp.id WHERE sp.nama_status = 'Lunas'")->fetch()['t'] ?? 0;
$total_trx = count($all_trx);
$total_lunas = $db->query("SELECT COUNT(*) as t FROM Transaksi t JOIN Status_Pembayaran sp ON t.status_pembayaran_id = sp.id WHERE sp.nama_status = 'Lunas'")->fetch()['t'] ?? 0;

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Transaksi - CHIBICON 2026</title>
    <style>
        @page { size: A4; margin: 20mm; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', 'Segoe UI', Helvetica, Arial, sans-serif; }
        body { padding: 0; color: #1f2937; background: #fff; line-height: 1.5; }
        
        .header { border-bottom: 2px solid #b61722; padding-bottom: 20px; margin-bottom: 30px; display: flex; justify-content: space-between; align-items: flex-end; }
        .logo-area h1 { color: #b61722; font-size: 28px; font-weight: 800; letter-spacing: -0.025em; }
        .logo-area p { color: #6b7280; font-size: 12px; font-weight: 500; text-transform: uppercase; }
        .report-info { text-align: right; }
        .report-info h2 { font-size: 18px; font-weight: 700; color: #111827; }
        .report-info p { font-size: 12px; color: #6b7280; }

        .summary-grid { display: flex; gap: 15px; margin-bottom: 30px; }
        .summary-card { flex: 1; border: 1px solid #e5e7eb; border-radius: 12px; padding: 15px; background: #f9fafb; }
        .summary-card .label { font-size: 10px; font-weight: 600; color: #6b7280; text-transform: uppercase; margin-bottom: 4px; }
        .summary-card .value { font-size: 18px; font-weight: 700; color: #b61722; }

        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; border: 1px solid #e5e7eb; }
        thead th { background: #f3f4f6; color: #374151; padding: 12px 15px; text-align: left; font-size: 11px; font-weight: 600; text-transform: uppercase; border-bottom: 2px solid #e5e7eb; }
        tbody td { padding: 10px 15px; font-size: 12px; border-bottom: 1px solid #f3f4f6; }
        tbody tr:nth-child(even) { background-color: #fafafa; }
        
        .badge { display: inline-block; padding: 2px 10px; border-radius: 9999px; font-size: 10px; font-weight: 600; text-transform: uppercase; }
        .badge-lunas { background-color: #def7ec; color: #03543f; }
        .badge-pending { background-color: #fdf6b2; color: #723b1a; }
        .badge-gagal { background-color: #fde8e8; color: #9b1c1c; }

        .footer { position: fixed; bottom: 0; left: 0; right: 0; padding: 20px 0; border-top: 1px solid #e5e7eb; text-align: center; font-size: 10px; color: #9ca3af; }

        @media print {
            .no-print { display: none !important; }
            body { padding: 0; }
        }
        
        .toolbar { background: #111827; color: #fff; padding: 15px 40px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; border-radius: 0 0 16px 16px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
        .btn-print { background: #b61722; color: #fff; border: none; padding: 8px 20px; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: background 0.2s; }
        .btn-print:hover { background: #9d131b; }
        .btn-back { color: #9ca3af; text-decoration: none; font-size: 14px; transition: color 0.2s; }
        .btn-back:hover { color: #fff; }
    </style>
</head>
<body>
    <div class="toolbar no-print">
        <a href="dashboard.php" class="btn-back">← Kembali ke Dashboard</a>
        <button onclick="window.print()" class="btn-print">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
            Cetak Laporan
        </button>
    </div>

    <div style="max-width: 800px; margin: 0 auto; padding: 0 20px;">
        <div class="header">
            <div class="logo-area">
                <p>Event Management System</p>
                <h1>CHIBICON 2026</h1>
            </div>
            <div class="report-info">
                <h2>Laporan Transaksi</h2>
                <p>Periode: Seluruh Waktu</p>
                <p>Dicetak: <?= date('d M Y, H:i') ?></p>
            </div>
        </div>

        <div class="summary-grid">
            <div class="summary-card">
                <div class="label">Total Pendapatan</div>
                <div class="value">Rp <?= number_format($total_rev, 0, ',', '.') ?></div>
            </div>
            <div class="summary-card">
                <div class="label">Total Transaksi</div>
                <div class="value"><?= number_format($total_trx) ?></div>
            </div>
            <div class="summary-card">
                <div class="label">Transaksi Lunas</div>
                <div class="value"><?= number_format($total_lunas) ?></div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>ID TRX</th>
                    <th>Nama Pengunjung</th>
                    <th>Metode</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Waktu</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($all_trx as $row): ?>
                <tr>
                    <td style="font-family: monospace; font-weight: 600;"><?= htmlspecialchars($row['trx_code']) ?></td>
                    <td><?= htmlspecialchars($row['visitor_name']) ?></td>
                    <td><?= htmlspecialchars($row['payment_method']) ?></td>
                    <td style="font-weight: 600;">Rp <?= number_format($row['amount'], 0, ',', '.') ?></td>
                    <td>
                        <?php 
                        $status_class = 'badge-pending';
                        if ($row['status'] === 'Lunas') $status_class = 'badge-lunas';
                        if ($row['status'] === 'Dibatalkan') $status_class = 'badge-gagal';
                        ?>
                        <span class="badge <?= $status_class ?>"><?= htmlspecialchars($row['status']) ?></span>
                    </td>
                    <td><?= date('d/m/y H:i', strtotime($row['time'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div style="margin-top: 50px; text-align: right; font-size: 12px;">
            <p>Mengetahui,</p>
            <div style="margin-top: 60px;">
                <div style="display: inline-block; width: 200px; border-top: 1px solid #000; padding-top: 5px; text-align: center;">
                    Admin Chibicon 2026
                </div>
            </div>
        </div>

        <div class="footer">
            &copy; 2026 Chibicon Event. Dokumen ini digenerate secara otomatis melalui sistem.
        </div>
    </div>

    <script>
        window.onload = function() {
            // Optional: window.print();
        }
    </script>
</body>
</html>
