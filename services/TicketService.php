<?php
// services/TicketService.php

class TicketService {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Create a new ticket.
     * 
     * @param string $name
     * @param float $price
     * @param string $description
     * @param int $quota
     * @return bool
     */
    public function createTicket($name, $price, $description, $quota) {
        $stmt = $this->db->prepare("INSERT INTO Tiket (nama_tiket, harga, deskripsi, kuota) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$name, $price, $description, $quota]);
    }

    /**
     * Update an existing ticket.
     * 
     * @param int $id
     * @param string $name
     * @param float $price
     * @param string $description
     * @param int $quota
     * @return bool
     */
    public function updateTicket($id, $name, $price, $description, $quota) {
        $stmt = $this->db->prepare("UPDATE Tiket SET nama_tiket=?, harga=?, deskripsi=?, kuota=? WHERE id=?");
        return $stmt->execute([$name, $price, $description, $quota, $id]);
    }

    /**
     * Delete a ticket.
     * 
     * @param int $id
     * @return bool
     */
    public function deleteTicket($id) {
        try {
            $this->db->beginTransaction();
            $this->db->prepare("UPDATE Detail_Transaksi SET tiket_id = NULL WHERE tiket_id = ?")->execute([$id]);
            $stmt = $this->db->prepare("DELETE FROM Tiket WHERE id = ?");
            $res = $stmt->execute([$id]);
            $this->db->commit();
            return $res;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return false;
        }
    }

    /**
     * Fetch all tickets with extra info.
     * 
     * @return array
     */
    public function getAllTickets() {
        return $this->db->query("
            SELECT t.*, 
            CONCAT('TKT-', LPAD(t.id, 3, '0')) as ticket_code,
            (SELECT COALESCE(SUM(kuantitas), 0) FROM Detail_Transaksi WHERE tiket_id = t.id) as sold 
            FROM Tiket t ORDER BY id ASC
        ")->fetchAll();
    }

    /**
     * Create a new transaction with multiple tickets.
     * 
     * @param int $pengunjung_id
     * @param array $items Array of ['ticket_id' => X, 'qty' => Y]
     * @param int $metode_id
     * @param int $status_id
     * @param int $panitia_id
     * @return bool
     */
    public function createTransaction($pengunjung_id, $items, $metode_id, $status_id, $panitia_id) {
        try {
            $this->db->beginTransaction();

            $total = 0;
            // Calculate total first
            foreach ($items as $item) {
                $stmt = $this->db->prepare("SELECT harga FROM Tiket WHERE id = ?");
                $stmt->execute([$item['ticket_id']]);
                $ticket = $stmt->fetch();
                if ($ticket) {
                    $total += $ticket['harga'] * $item['qty'];
                }
            }

            // Insert Transaksi
            $stmt = $this->db->prepare("INSERT INTO Transaksi (total, metode_pembayaran_id, status_pembayaran_id, pengunjung_id, panitia_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$total, $metode_id, $status_id, $pengunjung_id, $panitia_id]);
            $transaksi_id = $this->db->lastInsertId();

            // Insert Detail_Transaksi
            foreach ($items as $item) {
                $stmt = $this->db->prepare("SELECT harga FROM Tiket WHERE id = ?");
                $stmt->execute([$item['ticket_id']]);
                $ticket = $stmt->fetch();
                $subtotal = $ticket['harga'] * $item['qty'];

                $stmt = $this->db->prepare("INSERT INTO Detail_Transaksi (kuantitas, subtotal, tiket_id, transaksi_id) VALUES (?, ?, ?, ?)");
                $stmt->execute([$item['qty'], $subtotal, $item['ticket_id'], $transaksi_id]);
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Update an existing transaction and its details.
     * 
     * @param int $id
     * @param int $pengunjung_id
     * @param array $items
     * @param int $metode_id
     * @param int $status_id
     * @return bool
     */
    public function updateTransaction($id, $pengunjung_id, $items, $metode_id, $status_id) {
        try {
            $this->db->beginTransaction();

            $total = 0;
            foreach ($items as $item) {
                $stmt = $this->db->prepare("SELECT harga FROM Tiket WHERE id = ?");
                $stmt->execute([$item['ticket_id']]);
                $ticket = $stmt->fetch();
                if ($ticket) {
                    $total += $ticket['harga'] * $item['qty'];
                }
            }

            // Update Transaksi
            $stmt = $this->db->prepare("UPDATE Transaksi SET total=?, metode_pembayaran_id=?, status_pembayaran_id=?, pengunjung_id=? WHERE id=?");
            $stmt->execute([$total, $metode_id, $status_id, $pengunjung_id, $id]);

            // Delete old details
            $stmt = $this->db->prepare("DELETE FROM Detail_Transaksi WHERE transaksi_id = ?");
            $stmt->execute([$id]);

            // Insert new details
            foreach ($items as $item) {
                $stmt = $this->db->prepare("SELECT harga FROM Tiket WHERE id = ?");
                $stmt->execute([$item['ticket_id']]);
                $ticket = $stmt->fetch();
                $subtotal = $ticket['harga'] * $item['qty'];

                $stmt = $this->db->prepare("INSERT INTO Detail_Transaksi (kuantitas, subtotal, tiket_id, transaksi_id) VALUES (?, ?, ?, ?)");
                $stmt->execute([$item['qty'], $subtotal, $item['ticket_id'], $id]);
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Delete a transaction.
     * 
     * @param int $id
     * @return bool
     */
    public function deleteTransaction($id) {
        $stmt = $this->db->prepare("DELETE FROM Transaksi WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
