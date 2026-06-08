<?php

class PublicService {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function getAvailableTickets() {
        $stmt = $this->db->query("SELECT * FROM Tiket WHERE kuota > 0");
        return $stmt->fetchAll();
    }

    public function getPublicRundown() {
        $stmt = $this->db->query("
            SELECT r.*, lp.nama_lokasi, GROUP_CONCAT(gs.nama_panggung SEPARATOR ', ') as guest_stars
            FROM Rundown r
            LEFT JOIN Lokasi_Panggung lp ON r.lokasi_panggung_id = lp.id
            LEFT JOIN Guest_Star_Rundown gsr ON r.id = gsr.rundown_id
            LEFT JOIN Guest_Star gs ON gsr.guest_star_id = gs.id
            GROUP BY r.id
            ORDER BY r.tgl_wkt_mulai ASC
        ");
        return $stmt->fetchAll();
    }

    public function getGuestStars() {
        $stmt = $this->db->query("
            SELECT gs.*, kt.nama_kategori
            FROM Guest_Star gs
            LEFT JOIN Kategori_Talent kt ON gs.kategori_talent_id = kt.id
        ");
        return $stmt->fetchAll();
    }

    public function getTenantList() {
        $stmt = $this->db->query("
            SELECT t.*, ku.nama_usaha
            FROM Tenant t
            LEFT JOIN Kategori_Usaha ku ON t.kategori_usaha_id = ku.id
        ");
        return $stmt->fetchAll();
    }

    public function registerOrGetVisitor($identity, $name, $email, $phone) {
        // Check if visitor exists by identity number
        $stmt = $this->db->prepare("SELECT id FROM Pengunjung WHERE nomor_identitas = ?");
        $stmt->execute([$identity]);
        $visitor = $stmt->fetch();

        if ($visitor) {
            return $visitor['id'];
        }

        // Otherwise create new
        $stmt = $this->db->prepare("INSERT INTO Pengunjung (nomor_identitas, nama_lengkap, email, telepon) VALUES (?, ?, ?, ?)");
        $stmt->execute([$identity, $name, $email, $phone]);
        return $this->db->lastInsertId();
    }

    public function createPublicTransaction($visitor_id, $ticket_id, $qty, $metode_id = 1) {
        try {
            $this->db->beginTransaction();

            // Get ticket price
            $stmt = $this->db->prepare("SELECT harga FROM Tiket WHERE id = ?");
            $stmt->execute([$ticket_id]);
            $ticket = $stmt->fetch();
            if (!$ticket) throw new Exception("Tiket tidak ditemukan.");

            $total = $ticket['harga'] * $qty;

            // Insert Transaksi (status_pembayaran_id 1 = Menunggu Pembayaran)
            $stmt = $this->db->prepare("INSERT INTO Transaksi (total, metode_pembayaran_id, status_pembayaran_id, pengunjung_id, panitia_id) VALUES (?, ?, 1, ?, NULL)");
            $stmt->execute([$total, $metode_id, $visitor_id]);
            $transaksi_id = $this->db->lastInsertId();

            // Insert Detail_Transaksi
            $stmt = $this->db->prepare("INSERT INTO Detail_Transaksi (kuantitas, subtotal, tiket_id, transaksi_id) VALUES (?, ?, ?, ?)");
            $stmt->execute([$qty, $total, $ticket_id, $transaksi_id]);

            $this->db->commit();
            return $transaksi_id;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }
}
