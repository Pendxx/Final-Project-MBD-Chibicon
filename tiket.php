<?php
// tiket.php - Manajemen Tiket & Transaksi
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth_check.php';

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Ticket Actions
    if ($action === 'create_ticket' || $action === 'edit_ticket') {
        $id = $_POST['id'] ?? null;
        $name = $_POST['name'] ?? '';
        $price = $_POST['price'] ?? 0;
        $description = $_POST['description'] ?? '';
        $quota = $_POST['quota'] ?? 0;
        
        if ($action === 'create_ticket') {
            $stmt = $db->prepare("INSERT INTO Tiket (nama_tiket, harga, deskripsi, kuota) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $price, $description, $quota]);
            $msg = "Tiket baru berhasil ditambahkan!";
        } else {
            $stmt = $db->prepare("UPDATE Tiket SET nama_tiket=?, harga=?, deskripsi=?, kuota=? WHERE id=?");
            $stmt->execute([$name, $price, $description, $quota, $id]);
            $msg = "Informasi tiket berhasil diupdate!";
        }
    } elseif ($action === 'delete_ticket') {
        $id = $_POST['id'] ?? null;
        if ($id) {
            $stmt = $db->prepare("DELETE FROM Tiket WHERE id = ?");
            $stmt->execute([$id]);
            $msg = "Tiket berhasil dihapus.";
        }
    }
    
    // Transaction Actions
    if ($action === 'create_trx' || $action === 'edit_trx') {
        $id = $_POST['id'] ?? null;
        $pengunjung_id = $_POST['pengunjung_id'] ?? null;
        $ticket_id = $_POST['ticket_id'] ?? null;
        $qty = $_POST['qty'] ?? 1;
        $metode_pembayaran_id = $_POST['metode_pembayaran_id'] ?? 2;
        $status_pembayaran_id = $_POST['status_pembayaran_id'] ?? 1;
        
        // Get ticket price to compute amount
        $stmt = $db->prepare("SELECT harga FROM Tiket WHERE id = ?");
        $stmt->execute([$ticket_id]);
        $ticket = $stmt->fetch();
        $amount = $ticket ? ($ticket['harga'] * $qty) : 0;

        if ($action === 'create_trx') {
            $stmt = $db->prepare("INSERT INTO Transaksi (total, metode_pembayaran_id, status_pembayaran_id, pengunjung_id, panitia_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$amount, $metode_pembayaran_id, $status_pembayaran_id, $pengunjung_id, 1]); // admin panitia
            $trx_id = $db->lastInsertId();
            
            $stmt = $db->prepare("INSERT INTO Detail_Transaksi (kuantitas, subtotal, tiket_id, transaksi_id) VALUES (?, ?, ?, ?)");
            $stmt->execute([$qty, $amount, $ticket_id, $trx_id]);
            
            $msg = "Transaksi berhasil dicatat!";
        } else {
            $stmt = $db->prepare("UPDATE Transaksi SET total=?, metode_pembayaran_id=?, status_pembayaran_id=?, pengunjung_id=? WHERE id=?");
            $stmt->execute([$amount, $metode_pembayaran_id, $status_pembayaran_id, $pengunjung_id, $id]);
            
            $stmt = $db->prepare("UPDATE Detail_Transaksi SET kuantitas=?, subtotal=?, tiket_id=? WHERE transaksi_id=?");
            $stmt->execute([$qty, $amount, $ticket_id, $id]);
            
            $msg = "Transaksi berhasil diupdate!";
        }
    } elseif ($action === 'delete_trx') {
        $id = $_POST['id'] ?? null;
        if ($id) {
            $stmt = $db->prepare("DELETE FROM Transaksi WHERE id = ?");
            $stmt->execute([$id]);
            $msg = "Transaksi berhasil dihapus.";
        }
    }
    
    // Check if AJAX Request
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        echo json_encode(['success' => true, 'message' => $msg]);
        exit;
    }

    $_SESSION['toast'] = $msg;
    header("Location: tiket.php");
    exit;
}

// Partial rendering for AJAX table refresh
if (isset($_GET['partial'])) {
    if ($_GET['partial'] === 'tickets') {
        $tickets = $db->query("
            SELECT t.*, 
            CONCAT('TKT-', LPAD(t.id, 3, '0')) as ticket_code,
            (SELECT COALESCE(SUM(kuantitas), 0) FROM Detail_Transaksi WHERE tiket_id = t.id) as sold 
            FROM Tiket t ORDER BY id ASC
        ")->fetchAll();
        foreach($tickets as $t): 
            $pct = $t['kuota'] > 0 ? ($t['sold'] / $t['kuota']) * 100 : 0;
            $bar_color = $pct > 90 ? 'bg-error' : 'bg-tertiary-container';
        ?>
        <tr class="border-b border-outline-variant hover:bg-surface-bright transition-colors group">
            <td class="px-6 py-4 font-mono text-on-surface-variant"><?= $t['ticket_code'] ?></td>
            <td class="px-6 py-4 font-semibold text-on-surface"><?= htmlspecialchars($t['nama_tiket']) ?></td>
            <td class="px-6 py-4 text-on-surface"><?= format_currency($t['harga']) ?></td>
            <td class="px-6 py-4 text-on-surface-variant truncate max-w-xs"><?= htmlspecialchars($t['deskripsi']) ?></td>
            <td class="px-6 py-4 text-right">
                <div class="inline-flex items-center justify-end gap-2 <?= $pct > 90 ? 'text-error font-semibold' : 'text-on-surface' ?>">
                    <span><?= $t['sold'] ?> / <?= $t['kuota'] ?></span>
                    <div class="w-16 h-1.5 bg-surface-variant rounded-full overflow-hidden">
                        <div class="<?= $bar_color ?> h-full" style="width: <?= $pct ?>%"></div>
                    </div>
                </div>
            </td>
            <td class="px-6 py-4 text-center">
                <button onclick='editTicket(<?= json_encode($t) ?>)' class="text-primary hover:text-primary-container p-1 rounded transition-colors">
                    <span class="material-symbols-outlined text-[20px]">edit</span>
                </button>
                <form action="" method="POST" class="inline delete-form">
                    <input type="hidden" name="action" value="delete_ticket">
                    <input type="hidden" name="id" value="<?= $t['id'] ?>">
                    <button type="submit" class="text-error hover:text-error-container p-1 rounded transition-colors">
                        <span class="material-symbols-outlined text-[20px]">delete</span>
                    </button>
                </form>
            </td>
        </tr>
        <?php endforeach;
        exit;
    }
    
    if ($_GET['partial'] === 'transactions') {
        $transactions = $db->query("
            SELECT 
                CONCAT('TRX-', LPAD(t.id, 4, '0')) as trx_code,
                t.id, t.tgl_wkt_transaksi as time, t.total as amount,
                sp.nama_status as status, sp.id as status_pembayaran_id,
                p.nama_lengkap as visitor_name, p.id as pengunjung_id,
                dt.kuantitas as qty, tk.nama_tiket as ticket_name, tk.id as ticket_id,
                mp.nama_metode as payment_method, mp.id as metode_pembayaran_id
            FROM Transaksi t 
            LEFT JOIN Detail_Transaksi dt ON t.id = dt.transaksi_id
            LEFT JOIN Tiket tk ON dt.tiket_id = tk.id
            LEFT JOIN Pengunjung p ON t.pengunjung_id = p.id
            LEFT JOIN Status_Pembayaran sp ON t.status_pembayaran_id = sp.id
            LEFT JOIN Metode_Pembayaran mp ON t.metode_pembayaran_id = mp.id
            ORDER BY t.tgl_wkt_transaksi DESC
        ")->fetchAll();
        foreach($transactions as $trx): ?>
        <tr class="border-b border-outline-variant hover:bg-surface-bright transition-colors <?= $trx['status'] == 'Gagal' ? 'bg-error-container/10' : '' ?>">
            <td class="px-4 py-3 font-mono text-on-surface-variant"><?= $trx['trx_code'] ?></td>
            <td class="px-4 py-3 text-on-surface-variant"><?= date('d M, H:i', strtotime($trx['time'])) ?></td>
            <td class="px-4 py-3 font-semibold text-on-surface"><?= htmlspecialchars($trx['visitor_name']) ?></td>
            <td class="px-4 py-3 text-on-surface-variant"><?= htmlspecialchars($trx['ticket_name']) ?> x<?= $trx['qty'] ?></td>
            <td class="px-4 py-3 text-right font-medium text-on-surface"><?= format_currency($trx['amount']) ?></td>
            <td class="px-4 py-3" data-status="<?= $trx['status'] ?>">
                <?php if($trx['status'] == 'Lunas'): ?>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full badge-success text-xs font-semibold">Lunas</span>
                <?php elseif($trx['status'] == 'Menunggu Pembayaran'): ?>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full badge-warning text-xs font-semibold">Pending</span>
                <?php else: ?>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full badge-error text-xs font-semibold">Gagal</span>
                <?php endif; ?>
            </td>
            <td class="px-4 py-3 text-center flex items-center justify-center gap-1">
                <button onclick='editTrx(<?= json_encode($trx) ?>)' class="bg-surface-container-lowest border border-outline-variant text-on-surface px-2 py-1 rounded hover:bg-surface-container-low text-sm">Edit</button>
                <form action="" method="POST" class="inline delete-form">
                    <input type="hidden" name="action" value="delete_trx">
                    <input type="hidden" name="id" value="<?= $trx['id'] ?>">
                    <button type="submit" class="bg-error border border-error text-on-error px-2 py-1 rounded hover:opacity-80 text-sm">Del</button>
                </form>
            </td>
        </tr>
        <?php endforeach;
        exit;
    }
}

// Fetch Data
// Fetch Data
$tickets = $db->query("
    SELECT t.*, 
    CONCAT('TKT-', LPAD(t.id, 3, '0')) as ticket_code,
    (SELECT COALESCE(SUM(kuantitas), 0) FROM Detail_Transaksi WHERE tiket_id = t.id) as sold 
    FROM Tiket t ORDER BY id ASC
")->fetchAll();

$transactions = $db->query("
    SELECT 
        CONCAT('TRX-', LPAD(t.id, 4, '0')) as trx_code,
        t.id, t.tgl_wkt_transaksi as time, t.total as amount,
        sp.nama_status as status, sp.id as status_pembayaran_id,
        p.nama_lengkap as visitor_name, p.id as pengunjung_id,
        dt.kuantitas as qty, tk.nama_tiket as ticket_name, tk.id as ticket_id,
        mp.nama_metode as payment_method, mp.id as metode_pembayaran_id
    FROM Transaksi t 
    LEFT JOIN Detail_Transaksi dt ON t.id = dt.transaksi_id
    LEFT JOIN Tiket tk ON dt.tiket_id = tk.id
    LEFT JOIN Pengunjung p ON t.pengunjung_id = p.id
    LEFT JOIN Status_Pembayaran sp ON t.status_pembayaran_id = sp.id
    LEFT JOIN Metode_Pembayaran mp ON t.metode_pembayaran_id = mp.id
    ORDER BY t.tgl_wkt_transaksi DESC
")->fetchAll();

$total_revenue = $db->query("SELECT SUM(t.total) as val FROM Transaksi t JOIN Status_Pembayaran sp ON t.status_pembayaran_id = sp.id WHERE sp.nama_status = 'Lunas'")->fetch()['val'] ?? 0;
$total_sold = $db->query("SELECT SUM(kuantitas) as val FROM Detail_Transaksi")->fetch()['val'] ?? 0;

$metode_pembayaran = $db->query("SELECT * FROM Metode_Pembayaran")->fetchAll();
$status_pembayaran = $db->query("SELECT * FROM Status_Pembayaran")->fetchAll();
$pengunjung_list = $db->query("SELECT id, nama_lengkap FROM Pengunjung")->fetchAll();

$page_title = "Manajemen Tiket & Transaksi - Chibicon Admin";
$active_menu = "ticketing";
include __DIR__ . '/components/header.php';
?>

<main class="flex-1 overflow-y-auto bg-surface p-container-margin">
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-stack-md">
        <div>
            <h2 class="font-headline-lg text-headline-lg text-on-surface tracking-tight">Manajemen Tiket &amp; Transaksi</h2>
            <p class="font-body-md text-body-md text-on-surface-variant mt-1">Kelola inventaris tiket dan pantau seluruh transaksi penjualan secara real-time.</p>
        </div>
        <!-- Quick Stats -->
        <div class="flex gap-4">
            <div class="bg-surface-container-lowest border border-outline-variant rounded-lg px-4 py-2 flex flex-col shadow-sm">
                <span class="font-label-sm text-label-sm text-on-surface-variant uppercase">Total Revenue</span>
                <span class="font-title-lg text-title-lg text-primary font-bold"><?= format_currency($total_revenue) ?></span>
            </div>
            <div class="bg-surface-container-lowest border border-outline-variant rounded-lg px-4 py-2 flex flex-col shadow-sm">
                <span class="font-label-sm text-label-sm text-on-surface-variant uppercase">Tickets Sold</span>
                <span class="font-title-lg text-title-lg text-on-surface font-bold"><?= number_format($total_sold) ?></span>
            </div>
        </div>
    </div>

    <!-- Tab Navigation -->
    <div class="border-b border-outline-variant mb-section-gap flex gap-6">
        <button class="tab-btn active pb-3 font-title-md text-title-md text-primary border-b-2 border-primary transition-all" id="tab-tiket" onclick="switchTab('tiket')">
            Inventaris Tiket
        </button>
        <button class="tab-btn pb-3 font-title-md text-title-md text-on-surface-variant hover:text-on-surface border-b-2 border-transparent transition-all" id="tab-transaksi" onclick="switchTab('transaksi')">
            Riwayat Transaksi
        </button>
    </div>

    <!-- TAB CONTENT: TIKET -->
    <div class="tab-content block" id="content-tiket">
        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl shadow-sm overflow-hidden flex flex-col">
            <div class="p-4 border-b border-outline-variant flex justify-between items-center bg-surface-bright">
                <div class="relative w-64">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant text-[18px]">search</span>
                    <input id="ticketSearch" class="w-full pl-9 pr-3 py-1.5 text-body-md border border-outline-variant rounded bg-surface-container-lowest focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary" placeholder="Cari tiket..." type="text">
                </div>
                <button onclick="openTiketModal('ticketModal')" class="bg-primary text-on-primary font-label-md px-4 py-2 rounded hover:bg-primary-container transition-colors shadow-sm flex items-center gap-2">
                    <span class="material-symbols-outlined text-[16px]">add</span> Tambah Tiket
                </button>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse" id="ticketTable">
                    <thead>
                        <tr class="bg-surface-container-low text-on-surface-variant font-label-sm text-label-sm uppercase tracking-wider">
                            <th class="px-6 py-4 font-semibold border-b border-outline-variant w-24">ID</th>
                            <th class="px-6 py-4 font-semibold border-b border-outline-variant">Nama Tiket</th>
                            <th class="px-6 py-4 font-semibold border-b border-outline-variant">Harga</th>
                            <th class="px-6 py-4 font-semibold border-b border-outline-variant max-w-xs">Deskripsi</th>
                            <th class="px-6 py-4 font-semibold border-b border-outline-variant text-right">Kuota</th>
                            <th class="px-6 py-4 font-semibold border-b border-outline-variant text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="font-body-md text-body-md text-on-surface" id="tbody-tickets">
                        <?php foreach($tickets as $t): 
                            $pct = $t['kuota'] > 0 ? ($t['sold'] / $t['kuota']) * 100 : 0;
                            $bar_color = $pct > 90 ? 'bg-error' : 'bg-tertiary-container';
                        ?>
                        <tr class="border-b border-outline-variant hover:bg-surface-bright transition-colors group">
                            <td class="px-6 py-4 font-mono text-on-surface-variant"><?= $t['ticket_code'] ?></td>
                            <td class="px-6 py-4 font-semibold text-on-surface"><?= htmlspecialchars($t['nama_tiket']) ?></td>
                            <td class="px-6 py-4 text-on-surface"><?= format_currency($t['harga']) ?></td>
                            <td class="px-6 py-4 text-on-surface-variant truncate max-w-xs"><?= htmlspecialchars($t['deskripsi']) ?></td>
                            <td class="px-6 py-4 text-right">
                                <div class="inline-flex items-center justify-end gap-2 <?= $pct > 90 ? 'text-error font-semibold' : 'text-on-surface' ?>">
                                    <span><?= $t['sold'] ?> / <?= $t['kuota'] ?></span>
                                    <div class="w-16 h-1.5 bg-surface-variant rounded-full overflow-hidden">
                                        <div class="<?= $bar_color ?> h-full" style="width: <?= $pct ?>%"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <button onclick='editTicket(<?= json_encode($t) ?>)' class="text-primary hover:text-primary-container p-1 rounded transition-colors">
                                    <span class="material-symbols-outlined text-[20px]">edit</span>
                                </button>
                                <form action="" method="POST" class="inline delete-form">
                                    <input type="hidden" name="action" value="delete_ticket">
                                    <input type="hidden" name="id" value="<?= $t['id'] ?>">
                                    <button type="submit" class="text-error hover:text-error-container p-1 rounded transition-colors">
                                        <span class="material-symbols-outlined text-[20px]">delete</span>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TAB CONTENT: TRANSAKSI -->
    <div class="tab-content hidden" id="content-transaksi">
        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl shadow-sm overflow-hidden flex flex-col">
            <div class="p-4 border-b border-outline-variant flex justify-between items-center bg-surface-bright">
                <div class="flex gap-3">
                    <div class="relative w-64">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant text-[18px]">search</span>
                        <input id="trxSearch" class="w-full pl-9 pr-3 py-1.5 text-body-md border border-outline-variant rounded bg-surface-container-lowest focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary" placeholder="Cari ID Transaksi..." type="text">
                    </div>
                    <select id="trxFilter" class="py-1.5 px-3 border border-outline-variant rounded bg-surface-container-lowest text-body-md focus:outline-none focus:border-primary">
                        <option>Semua Status</option>
                        <?php foreach($status_pembayaran as $sp): ?>
                        <option><?= htmlspecialchars($sp['nama_status']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button onclick="openTiketModal('trxModal')" class="bg-primary text-on-primary font-label-md px-4 py-2 rounded hover:bg-primary-container transition-colors shadow-sm flex items-center gap-2">
                    <span class="material-symbols-outlined text-[16px]">add</span> Tambah Transaksi
                </button>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse" id="trxTable">
                    <thead>
                        <tr class="bg-surface-container-low text-on-surface-variant font-label-sm text-label-sm uppercase tracking-wider">
                            <th class="px-4 py-4 font-semibold border-b border-outline-variant">ID Transaksi</th>
                            <th class="px-4 py-4 font-semibold border-b border-outline-variant">Waktu</th>
                            <th class="px-4 py-4 font-semibold border-b border-outline-variant">Pengunjung</th>
                            <th class="px-4 py-4 font-semibold border-b border-outline-variant">Tiket</th>
                            <th class="px-4 py-4 font-semibold border-b border-outline-variant text-right">Total</th>
                            <th class="px-4 py-4 font-semibold border-b border-outline-variant">Status</th>
                            <th class="px-4 py-4 font-semibold border-b border-outline-variant text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="font-body-md text-body-md text-on-surface" id="tbody-transactions">
                        <?php foreach($transactions as $trx): ?>
                        <tr class="border-b border-outline-variant hover:bg-surface-bright transition-colors <?= $trx['status'] == 'Gagal' ? 'bg-error-container/10' : '' ?>">
                            <td class="px-4 py-3 font-mono text-on-surface-variant"><?= $trx['trx_code'] ?></td>
                            <td class="px-4 py-3 text-on-surface-variant"><?= date('d M, H:i', strtotime($trx['time'])) ?></td>
                            <td class="px-4 py-3 font-semibold text-on-surface"><?= htmlspecialchars($trx['visitor_name']) ?></td>
                            <td class="px-4 py-3 text-on-surface-variant"><?= htmlspecialchars($trx['ticket_name']) ?> x<?= $trx['qty'] ?></td>
                            <td class="px-4 py-3 text-right font-medium text-on-surface"><?= format_currency($trx['amount']) ?></td>
                            <td class="px-4 py-3" data-status="<?= $trx['status'] ?>">
                                <?php if($trx['status'] == 'Lunas'): ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full badge-success text-xs font-semibold">Lunas</span>
                                <?php elseif($trx['status'] == 'Menunggu Pembayaran'): ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full badge-warning text-xs font-semibold">Pending</span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full badge-error text-xs font-semibold">Gagal</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-center flex items-center justify-center gap-1">
                                <button onclick='editTrx(<?= json_encode($trx) ?>)' class="bg-surface-container-lowest border border-outline-variant text-on-surface px-2 py-1 rounded hover:bg-surface-container-low text-sm">Edit</button>
                                <form action="" method="POST" class="inline delete-form">
                                    <input type="hidden" name="action" value="delete_trx">
                                    <input type="hidden" name="id" value="<?= $trx['id'] ?>">
                                    <button type="submit" class="bg-error border border-error text-on-error px-2 py-1 rounded hover:opacity-80 text-sm">Del</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>
</div> <!-- close layout -->

<!-- Ticket Modal -->
<div id="ticketModal" class="fixed inset-0 z-50 hidden flex items-center justify-center">
    <div class="absolute inset-0 bg-black/50 modal-overlay opacity-0" onclick="closeModal('ticketModal')"></div>
    <div class="relative bg-surface-container-lowest rounded-xl shadow-lg w-full max-w-md mx-4 modal-content scale-95 opacity-0">
        <form action="" method="POST" class="flex flex-col ajax-form" data-target="tickets">
            <div class="px-6 py-4 border-b border-outline-variant flex justify-between items-center">
                <h3 id="tModalTitle" class="font-title-lg font-bold text-on-surface">Tambah Tiket</h3>
                <button type="button" onclick="closeModal('ticketModal')" class="text-on-surface-variant hover:bg-surface-container-low rounded-full p-1"><span class="material-symbols-outlined">close</span></button>
            </div>
            <div class="p-6 flex flex-col gap-4">
                <input type="hidden" name="action" id="tFormAction" value="create_ticket">
                <input type="hidden" name="id" id="tId">
                <div><label class="block font-label-md mb-1 text-on-surface">Nama Tiket</label><input type="text" name="name" id="tName" required class="w-full px-3 py-2 border border-outline-variant rounded-lg bg-surface-container-lowest text-on-surface focus:border-primary focus:outline-none"></div>
                <div><label class="block font-label-md mb-1 text-on-surface">Harga (Rp)</label><input type="number" name="price" id="tPrice" required class="w-full px-3 py-2 border border-outline-variant rounded-lg bg-surface-container-lowest text-on-surface focus:border-primary focus:outline-none"></div>
                <div><label class="block font-label-md mb-1 text-on-surface">Deskripsi</label><input type="text" name="description" id="tDesc" class="w-full px-3 py-2 border border-outline-variant rounded-lg bg-surface-container-lowest text-on-surface focus:border-primary focus:outline-none"></div>
                <div><label class="block font-label-md mb-1 text-on-surface">Kuota Maksimal</label><input type="number" name="quota" id="tQuota" required class="w-full px-3 py-2 border border-outline-variant rounded-lg bg-surface-container-lowest text-on-surface focus:border-primary focus:outline-none"></div>
            </div>
            <div class="px-6 py-4 border-t border-outline-variant bg-surface-bright flex justify-end gap-3 rounded-b-xl">
                <button type="button" onclick="closeModal('ticketModal')" class="px-4 py-2 border border-outline-variant text-on-surface rounded-lg hover:bg-surface-container-low transition-colors">Batal</button>
                <button type="submit" class="px-4 py-2 bg-primary text-on-primary rounded-lg shadow-sm hover:bg-primary-container transition-colors">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- Transaction Modal -->
<div id="trxModal" class="fixed inset-0 z-50 hidden flex items-center justify-center">
    <div class="absolute inset-0 bg-black/50 modal-overlay opacity-0" onclick="closeModal('trxModal')"></div>
    <div class="relative bg-surface-container-lowest rounded-xl shadow-lg w-full max-w-md mx-4 modal-content scale-95 opacity-0">
        <form action="" method="POST" class="flex flex-col ajax-form" data-target="transactions">
            <div class="px-6 py-4 border-b border-outline-variant flex justify-between items-center">
                <h3 id="trxModalTitle" class="font-title-lg font-bold text-on-surface">Tambah Transaksi</h3>
                <button type="button" onclick="closeModal('trxModal')" class="text-on-surface-variant hover:bg-surface-container-low rounded-full p-1"><span class="material-symbols-outlined">close</span></button>
            </div>
            <div class="p-6 flex flex-col gap-4">
                <input type="hidden" name="action" id="trxFormAction" value="create_trx">
                <input type="hidden" name="id" id="trxId">
                <div>
                    <label class="block font-label-md mb-1 text-on-surface">Pengunjung</label>
                    <select name="pengunjung_id" id="trxName" required class="w-full px-3 py-2 border border-outline-variant rounded-lg bg-surface-container-lowest text-on-surface focus:border-primary focus:outline-none">
                        <?php foreach($pengunjung_list as $p): ?>
                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nama_lengkap']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block font-label-md mb-1 text-on-surface">Pilih Tiket</label>
                    <select name="ticket_id" id="trxTicket" required class="w-full px-3 py-2 border border-outline-variant rounded-lg bg-surface-container-lowest text-on-surface focus:border-primary focus:outline-none">
                        <?php foreach($tickets as $t): ?>
                        <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['nama_tiket']) ?> (<?= format_currency($t['harga']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div><label class="block font-label-md mb-1 text-on-surface">Qty (Jumlah)</label><input type="number" name="qty" id="trxQty" value="1" min="1" required class="w-full px-3 py-2 border border-outline-variant rounded-lg bg-surface-container-lowest text-on-surface focus:border-primary focus:outline-none"></div>
                <div>
                    <label class="block font-label-md mb-1 text-on-surface">Metode Pembayaran</label>
                    <select name="metode_pembayaran_id" id="trxPayment" required class="w-full px-3 py-2 border border-outline-variant rounded-lg bg-surface-container-lowest text-on-surface focus:border-primary focus:outline-none">
                        <?php foreach($metode_pembayaran as $mp): ?>
                        <option value="<?= $mp['id'] ?>"><?= htmlspecialchars($mp['nama_metode']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block font-label-md mb-1 text-on-surface">Status</label>
                    <select name="status_pembayaran_id" id="trxStatus" required class="w-full px-3 py-2 border border-outline-variant rounded-lg bg-surface-container-lowest text-on-surface focus:border-primary focus:outline-none">
                        <?php foreach($status_pembayaran as $sp): ?>
                        <option value="<?= $sp['id'] ?>"><?= htmlspecialchars($sp['nama_status']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-outline-variant bg-surface-bright flex justify-end gap-3 rounded-b-xl">
                <button type="button" onclick="closeModal('trxModal')" class="px-4 py-2 border border-outline-variant text-on-surface rounded-lg hover:bg-surface-container-low transition-colors">Batal</button>
                <button type="submit" class="px-4 py-2 bg-primary text-on-primary rounded-lg shadow-sm hover:bg-primary-container transition-colors">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // ── Live search for Tickets tab ─────────────────────────────────────────
    const ticketSearch = document.getElementById('ticketSearch');
    if (ticketSearch) {
        ticketSearch.addEventListener('input', function() {
            const q = this.value.toLowerCase();
            document.querySelectorAll('#ticketTable tbody tr').forEach(row => {
                row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
            });
        });
    }

    // ── Live search + status filter for Transactions tab ────────────────────
    const trxSearch   = document.getElementById('trxSearch');
    const trxFilter   = document.getElementById('trxFilter');

    function filterTrx() {
        const q      = trxSearch ? trxSearch.value.toLowerCase() : '';
        const status = trxFilter ? trxFilter.value : 'Semua Status';
        document.querySelectorAll('#trxTable tbody tr').forEach(row => {
            const matchText   = row.textContent.toLowerCase().includes(q);
            const statusCell  = row.querySelector('[data-status]');
            const matchStatus = status === 'Semua Status' || (statusCell && statusCell.dataset.status === status);
            row.style.display = (matchText && matchStatus) ? '' : 'none';
        });
    }

    if (trxSearch) trxSearch.addEventListener('input', filterTrx);
    if (trxFilter) trxFilter.addEventListener('change', filterTrx);

    // ── Bind AJAX Submits ──────────────────────────────────────────────────
    document.body.addEventListener('submit', function(e) {
        // Handle normal form submits with ajax
        if (e.target.matches('.ajax-form')) {
            e.preventDefault();
            const form = e.target;
            const targetName = form.dataset.target; // tickets or transactions
            ajaxSubmit(form, {
                onSuccess: () => {
                    closeModal(form.closest('.modal-content').parentElement.id);
                    refreshPartial(`tbody-${targetName}`, targetName);
                }
            });
        }
        
        // Handle inline delete forms
        if (e.target.matches('.delete-form')) {
            e.preventDefault();
            if(!confirm('Hapus item ini?')) return;
            const form = e.target;
            const targetName = form.closest('table').id === 'ticketTable' ? 'tickets' : 'transactions';
            ajaxSubmit(form, {
                onSuccess: () => {
                    refreshPartial(`tbody-${targetName}`, targetName);
                }
            });
        }
    });
});

// ── Modal helpers ──────────────────────────────────────────────────────────────
function openTiketModal(id) {
    const modal = document.getElementById(id);
    if (id === 'ticketModal') {
        document.getElementById('tModalTitle').innerText = 'Tambah Tiket';
        document.getElementById('tFormAction').value = 'create_ticket';
        document.getElementById('tId').value = '';
        document.getElementById('tName').value = '';
        document.getElementById('tPrice').value = '';
        document.getElementById('tDesc').value = '';
        document.getElementById('tQuota').value = '';
    } else {
        document.getElementById('trxModalTitle').innerText = 'Tambah Transaksi';
        document.getElementById('trxFormAction').value = 'create_trx';
        document.getElementById('trxId').value = '';
        document.getElementById('trxName').value = '';
        document.getElementById('trxQty').value = '1';
    }
    if (!modal) return;
    modal.classList.remove('hidden');
    void modal.offsetWidth;
    modal.querySelector('.modal-overlay')?.classList.remove('opacity-0');
    modal.querySelector('.modal-content')?.classList.remove('scale-95', 'opacity-0');
}

function editTicket(data) {
    document.getElementById('tModalTitle').innerText = 'Edit Tiket';
    document.getElementById('tFormAction').value = 'edit_ticket';
    document.getElementById('tId').value = data.id;
    document.getElementById('tName').value = data.nama_tiket;
    document.getElementById('tPrice').value = data.harga;
    document.getElementById('tDesc').value = data.deskripsi;
    document.getElementById('tQuota').value = data.kuota;
    const modal = document.getElementById('ticketModal');
    modal.classList.remove('hidden');
    void modal.offsetWidth;
    modal.querySelector('.modal-overlay').classList.remove('opacity-0');
    modal.querySelector('.modal-content').classList.remove('scale-95', 'opacity-0');
}

function editTrx(data) {
    document.getElementById('trxModalTitle').innerText = 'Edit Transaksi';
    document.getElementById('trxFormAction').value = 'edit_trx';
    document.getElementById('trxId').value = data.id;
    document.getElementById('trxName').value = data.pengunjung_id;
    document.getElementById('trxTicket').value = data.ticket_id;
    document.getElementById('trxQty').value = data.qty;
    document.getElementById('trxPayment').value = data.metode_pembayaran_id;
    document.getElementById('trxStatus').value = data.status_pembayaran_id;
    const modal = document.getElementById('trxModal');
    modal.classList.remove('hidden');
    void modal.offsetWidth;
    modal.querySelector('.modal-overlay').classList.remove('opacity-0');
    modal.querySelector('.modal-content').classList.remove('scale-95', 'opacity-0');
}
</script>
</body>
</html>


