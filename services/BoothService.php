<?php
// services/BoothService.php

class BoothService {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // Tenant Methods
    public function createTenant($name, $kategori_usaha_id, $pic, $phone) {
        $stmt = $this->db->prepare("INSERT INTO Tenant (nama_tenant, kategori_usaha_id, nama_pic, telepon_pic) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$name, $kategori_usaha_id, $pic, $phone]);
    }

    public function updateTenant($id, $name, $kategori_usaha_id, $pic, $phone) {
        $stmt = $this->db->prepare("UPDATE Tenant SET nama_tenant=?, kategori_usaha_id=?, nama_pic=?, telepon_pic=? WHERE id=?");
        return $stmt->execute([$name, $kategori_usaha_id, $pic, $phone, $id]);
    }

    public function deleteTenant($id) {
        try {
            $this->db->beginTransaction();
            $this->db->prepare("UPDATE Booth SET tenant_id = NULL WHERE tenant_id = ?")->execute([$id]);
            $this->db->prepare("DELETE FROM Tenant WHERE id = ?")->execute([$id]);
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function getAllTenants() {
        return $this->db->query("
            SELECT t.*, ku.nama_usaha as category, CONCAT('TN-', LPAD(t.id, 3, '0')) as tenant_code
            FROM Tenant t 
            LEFT JOIN Kategori_Usaha ku ON t.kategori_usaha_id = ku.id
            ORDER BY t.id ASC
        ")->fetchAll();
    }

    public function getCategories() {
        return $this->db->query("SELECT * FROM Kategori_Usaha")->fetchAll();
    }

    // Booth Methods
    public function createBooth($id, $name, $price, $lokasi_booth_id, $tenant_id) {
        $stmt = $this->db->prepare("INSERT INTO Booth (id, nama_booth, harga_sewa, lokasi_booth_id, tenant_id) VALUES (?, ?, ?, ?, ?)");
        return $stmt->execute([$id, $name, $price, $lokasi_booth_id, $tenant_id]);
    }

    public function updateBooth($id, $name, $price, $lokasi_booth_id, $tenant_id) {
        $stmt = $this->db->prepare("UPDATE Booth SET nama_booth=?, harga_sewa=?, lokasi_booth_id=?, tenant_id=? WHERE id=?");
        return $stmt->execute([$name, $price, $lokasi_booth_id, $tenant_id, $id]);
    }

    public function deleteBooth($id) {
        $stmt = $this->db->prepare("DELETE FROM Booth WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function getAllBooths() {
        return $this->db->query("
            SELECT b.*, t.nama_tenant as tenant_name, lb.nama_lokasi as location 
            FROM Booth b 
            LEFT JOIN Tenant t ON b.tenant_id = t.id 
            LEFT JOIN Lokasi_Booth lb ON b.lokasi_booth_id = lb.id
            ORDER BY b.id ASC
        ")->fetchAll();
    }

    public function getLocations() {
        return $this->db->query("SELECT * FROM Lokasi_Booth")->fetchAll();
    }
}
