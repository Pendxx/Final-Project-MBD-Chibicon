<?php
// services/PanitiaService.php

class PanitiaService {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function createPanitia($name, $phone, $divisi_id) {
        $stmt = $this->db->prepare("INSERT INTO Panitia (nama_lengkap, telepon, divisi_id) VALUES (?, ?, ?)");
        return $stmt->execute([$name, $phone, $divisi_id]);
    }

    public function updatePanitia($id, $name, $phone, $divisi_id) {
        $stmt = $this->db->prepare("UPDATE Panitia SET nama_lengkap=?, telepon=?, divisi_id=? WHERE id=?");
        return $stmt->execute([$name, $phone, $divisi_id, $id]);
    }

    public function deletePanitia($id) {
        try {
            $this->db->beginTransaction();
            // Set panitia_id to NULL in dependent tables
            $this->db->prepare("UPDATE Transaksi SET panitia_id = NULL WHERE panitia_id = ?")->execute([$id]);
            $this->db->prepare("UPDATE Rundown SET panitia_id = NULL WHERE panitia_id = ?")->execute([$id]);
            $this->db->prepare("UPDATE Tenant SET panitia_id = NULL WHERE panitia_id = ?")->execute([$id]);
            
            $stmt = $this->db->prepare("DELETE FROM Panitia WHERE id = ?");
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

    public function getAllPanitia($search = "") {
        $where = "";
        $params = [];
        if ($search) {
            $where = "WHERE p.nama_lengkap LIKE ?";
            $params = ["%$search%"];
        }
        $stmt = $this->db->prepare("
            SELECT p.*, d.nama_divisi as division, CONCAT('STF-', LPAD(p.id, 4, '0')) as staff_code 
            FROM Panitia p 
            LEFT JOIN Divisi d ON p.divisi_id = d.id 
            $where ORDER BY p.nama_lengkap ASC
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getDivisions() {
        return $this->db->query("
            SELECT d.nama_divisi as division, d.id as divisi_id, COUNT(p.id) as count 
            FROM Divisi d 
            LEFT JOIN Panitia p ON d.id = p.divisi_id 
            GROUP BY d.id
        ")->fetchAll();
    }

    public function getDivisionsSummary() {
        return $this->getDivisions();
    }
}
