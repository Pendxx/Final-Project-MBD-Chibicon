<?php
// services/PengunjungService.php

class PengunjungService {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function createPengunjung($identity, $name, $email, $phone) {
        $stmt = $this->db->prepare("INSERT INTO Pengunjung (nomor_identitas, nama_lengkap, email, telepon) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$identity, $name, $email, $phone]);
    }

    public function updatePengunjung($id, $identity, $name, $email, $phone) {
        $stmt = $this->db->prepare("UPDATE Pengunjung SET nomor_identitas=?, nama_lengkap=?, email=?, telepon=? WHERE id=?");
        return $stmt->execute([$identity, $name, $email, $phone, $id]);
    }

    public function deletePengunjung($id) {
        try {
            $this->db->beginTransaction();
            $this->db->prepare("UPDATE Transaksi SET pengunjung_id = NULL WHERE pengunjung_id = ?")->execute([$id]);
            $stmt = $this->db->prepare("DELETE FROM Pengunjung WHERE id = ?");
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

    public function getAllPengunjung($search = "", $limit = 10, $offset = 0) {
        $where = "";
        $params = [];
        if ($search) {
            $where = "WHERE nama_lengkap LIKE ? OR nomor_identitas LIKE ? OR email LIKE ?";
            $params = ["%$search%", "%$search%", "%$search%"];
        }
        
        $stmt = $this->db->prepare("SELECT * FROM Pengunjung $where ORDER BY id DESC LIMIT ? OFFSET ?");
        $i = 1;
        foreach ($params as $p) {
            $stmt->bindValue($i++, $p, PDO::PARAM_STR);
        }
        $stmt->bindValue($i++, (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue($i,   (int)$offset,   PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function countPengunjung($search = "") {
        $where = "";
        $params = [];
        if ($search) {
            $where = "WHERE nama_lengkap LIKE ? OR nomor_identitas LIKE ? OR email LIKE ?";
            $params = ["%$search%", "%$search%", "%$search%"];
        }
        $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM Pengunjung $where");
        $stmt->execute($params);
        return (int)$stmt->fetch()['total'];
    }
}
