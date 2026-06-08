<?php
// check_ticket.php - Fetch visitor tickets via AJAX
require_once __DIR__ . '/config/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identity = trim($_POST['nomor_identitas'] ?? '');

    if (empty($identity)) {
        echo json_encode(['success' => false, 'message' => 'Identity number is required.']);
        exit;
    }

    try {
        $stmt = $db->prepare("
            SELECT 
                CONCAT('TRX-', LPAD(t.id, 4, '0')) as trx_code,
                t.total as amount,
                sp.nama_status as status,
                mp.nama_metode as payment_method,
                t.tgl_wkt_transaksi as time,
                GROUP_CONCAT(CONCAT(tk.nama_tiket, ' (', dt.kuantitas, ')') SEPARATOR ', ') as items
            FROM Transaksi t
            JOIN Pengunjung p ON t.pengunjung_id = p.id
            JOIN Status_Pembayaran sp ON t.status_pembayaran_id = sp.id
            JOIN Metode_Pembayaran mp ON t.metode_pembayaran_id = mp.id
            JOIN Detail_Transaksi dt ON t.id = dt.transaksi_id
            JOIN Tiket tk ON dt.tiket_id = tk.id
            WHERE p.nomor_identitas = ?
            GROUP BY t.id
            ORDER BY t.tgl_wkt_transaksi DESC
        ");
        $stmt->execute([$identity]);
        $tickets = $stmt->fetchAll();

        echo json_encode(['success' => true, 'data' => $tickets]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
