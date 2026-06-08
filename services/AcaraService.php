<?php
// services/AcaraService.php

class AcaraService {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // Guest Star Methods
    public function createGuestStar($stage_name, $agency, $country, $kategori_talent_id, $manager) {
        $stmt = $this->db->prepare("INSERT INTO Guest_Star (nama_panggung, agensi, negara, kategori_talent_id, kontak_manager) VALUES (?, ?, ?, ?, ?)");
        return $stmt->execute([$stage_name, $agency, $country, $kategori_talent_id, $manager]);
    }

    public function updateGuestStar($id, $stage_name, $agency, $country, $kategori_talent_id, $manager) {
        $stmt = $this->db->prepare("UPDATE Guest_Star SET nama_panggung=?, agensi=?, negara=?, kategori_talent_id=?, kontak_manager=? WHERE id=?");
        return $stmt->execute([$stage_name, $agency, $country, $kategori_talent_id, $manager, $id]);
    }

    public function deleteGuestStar($id) {
        $stmt = $this->db->prepare("DELETE FROM Guest_Star WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function getAllGuestStars() {
        return $this->db->query("
            SELECT gs.*, kt.nama_kategori as category, CONCAT('GS-', LPAD(gs.id, 3, '0')) as gs_code 
            FROM Guest_Star gs 
            LEFT JOIN Kategori_Talent kt ON gs.kategori_talent_id = kt.id 
            ORDER BY gs.id ASC
        ")->fetchAll();
    }

    public function getPanitia() {
        return $this->db->query("SELECT id, nama_lengkap FROM Panitia ORDER BY nama_lengkap ASC")->fetchAll();
    }

    // Rundown Methods
    public function createRundown($activity, $start_time, $end_time, $lokasi_panggung_id, $gs_ids = [], $panitia_id = 1) {
        try {
            $this->db->beginTransaction();
            $stmt = $this->db->prepare("INSERT INTO Rundown (nama_kegiatan, tgl_wkt_mulai, tgl_wkt_akhir, lokasi_panggung_id, panitia_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$activity, $start_time, $end_time, $lokasi_panggung_id, $panitia_id]);
            $rd_id = $this->db->lastInsertId();
            
            if (!empty($gs_ids)) {
                $stmt_gs = $this->db->prepare("INSERT INTO Guest_Star_Rundown (guest_star_id, rundown_id) VALUES (?, ?)");
                foreach ((array)$gs_ids as $gs_id) {
                    $stmt_gs->execute([$gs_id, $rd_id]);
                }
            }
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function updateRundown($id, $activity, $start_time, $end_time, $lokasi_panggung_id, $gs_ids = [], $panitia_id = 1) {
        try {
            $this->db->beginTransaction();
            $stmt = $this->db->prepare("UPDATE Rundown SET nama_kegiatan=?, tgl_wkt_mulai=?, tgl_wkt_akhir=?, lokasi_panggung_id=?, panitia_id=? WHERE id=?");
            $stmt->execute([$activity, $start_time, $end_time, $lokasi_panggung_id, $panitia_id, $id]);
            
            $this->db->prepare("DELETE FROM Guest_Star_Rundown WHERE rundown_id=?")->execute([$id]);
            if (!empty($gs_ids)) {
                $stmt_gs = $this->db->prepare("INSERT INTO Guest_Star_Rundown (guest_star_id, rundown_id) VALUES (?, ?)");
                foreach ((array)$gs_ids as $gs_id) {
                    $stmt_gs->execute([$gs_id, $id]);
                }
            }
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function deleteRundown($id) {
        $stmt = $this->db->prepare("DELETE FROM Rundown WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function getAllRundowns() {
        return $this->db->query("
            SELECT r.*, 
            GROUP_CONCAT(gs.nama_panggung SEPARATOR ', ') as gs_name, 
            lp.nama_lokasi as location, 
            GROUP_CONCAT(gs.id SEPARATOR ',') as guest_star_id, 
            p.nama_lengkap as panitia_name, 
            p.id as panitia_id
            FROM Rundown r 
            LEFT JOIN Guest_Star_Rundown gsr ON r.id = gsr.rundown_id 
            LEFT JOIN Guest_Star gs ON gsr.guest_star_id = gs.id 
            LEFT JOIN Lokasi_Panggung lp ON r.lokasi_panggung_id = lp.id
            LEFT JOIN Panitia p ON r.panitia_id = p.id
            GROUP BY r.id, lp.nama_lokasi, p.nama_lengkap, p.id
            ORDER BY r.tgl_wkt_mulai ASC
        ")->fetchAll();
    }

    public function getLokasiPanggung() {
        return $this->db->query("SELECT * FROM Lokasi_Panggung")->fetchAll();
    }

    public function getKategoriTalent() {
        return $this->db->query("SELECT * FROM Kategori_Talent")->fetchAll();
    }
}
