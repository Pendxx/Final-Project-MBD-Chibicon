<?php
// purchase.php - Handle public ticket purchasing
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/csrf.php';
require_once __DIR__ . '/services/PublicService.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate();

    $publicService = new PublicService($db);

    try {
        $name = trim($_POST['nama_lengkap'] ?? '');
        $identity = trim($_POST['nomor_identitas'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['telepon'] ?? '');
        $ticket_id = $_POST['ticket_id'] ?? null;
        $qty = (int)($_POST['qty'] ?? 1);
        $metode_id = $_POST['metode_pembayaran_id'] ?? 1;

        if (empty($name) || empty($identity) || empty($email) || empty($ticket_id) || $qty < 1) {
            throw new Exception("Mohon lengkapi seluruh data formulir.");
        }

        // 1. Register or get visitor ID
        $visitor_id = $publicService->registerOrGetVisitor($identity, $name, $email, $phone);

        // 2. Create transaction
        $transaksi_id = $publicService->createPublicTransaction($visitor_id, $ticket_id, $qty, $metode_id);

        $_SESSION['purchase_success'] = [
            'trx_id' => $transaksi_id,
            'name' => $name,
            'amount' => $_POST['amount_raw'] ?? 0
        ];

        header('Location: index.php?status=success');
        exit;

    } catch (Exception $e) {
        $_SESSION['purchase_error'] = $e->getMessage();
        header('Location: index.php?status=error#tickets');
        exit;
    }
} else {
    header('Location: index.php');
    exit;
}
