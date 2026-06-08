-- Membuat Database
CREATE DATABASE IF NOT EXISTS chibicon_db;
USE chibicon_db;

-- ==========================================
-- 1. PEMBUATAN TABEL (Sesuai PDM)
-- ==========================================

CREATE TABLE Divisi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_divisi VARCHAR(100) NOT NULL
);

CREATE TABLE Pengunjung (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nomor_identitas VARCHAR(50) NOT NULL,
    nama_lengkap VARCHAR(150) NOT NULL,
    email VARCHAR(100),
    telepon VARCHAR(20),
    tgl_wkt_daftar TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE Metode_Pembayaran (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_metode VARCHAR(50) NOT NULL
);

CREATE TABLE Status_Pembayaran (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_status VARCHAR(50) NOT NULL
);

CREATE TABLE Tiket (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_tiket VARCHAR(100) NOT NULL,
    harga DECIMAL(10,2) NOT NULL,
    deskripsi TEXT,
    kuota INT NOT NULL
);

CREATE TABLE Kategori_Talent (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_kategori VARCHAR(100) NOT NULL
);

CREATE TABLE Lokasi_Panggung (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_lokasi VARCHAR(100) NOT NULL
);

CREATE TABLE Kategori_Usaha (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_usaha VARCHAR(100) NOT NULL
);

CREATE TABLE Lokasi_Booth (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_lokasi VARCHAR(100) NOT NULL
);

CREATE TABLE Panitia (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_lengkap VARCHAR(150) NOT NULL,
    telepon VARCHAR(20),
    divisi_id INT,
    FOREIGN KEY (divisi_id) REFERENCES Divisi(id) ON DELETE SET NULL
);

CREATE TABLE Transaksi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tgl_wkt_transaksi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    total DECIMAL(12,2) NOT NULL,
    metode_pembayaran_id INT,
    status_pembayaran_id INT,
    pengunjung_id INT,
    panitia_id INT,
    FOREIGN KEY (metode_pembayaran_id) REFERENCES Metode_Pembayaran(id),
    FOREIGN KEY (status_pembayaran_id) REFERENCES Status_Pembayaran(id),
    FOREIGN KEY (pengunjung_id) REFERENCES Pengunjung(id),
    FOREIGN KEY (panitia_id) REFERENCES Panitia(id)
);

CREATE TABLE Detail_Transaksi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kuantitas INT NOT NULL,
    subtotal DECIMAL(12,2) NOT NULL,
    tiket_id INT,
    transaksi_id INT,
    FOREIGN KEY (tiket_id) REFERENCES Tiket(id),
    FOREIGN KEY (transaksi_id) REFERENCES Transaksi(id) ON DELETE CASCADE
);

CREATE TABLE Guest_Star (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_panggung VARCHAR(100) NOT NULL,
    agensi VARCHAR(100),
    negara VARCHAR(50),
    kontak_manager VARCHAR(50),
    kategori_talent_id INT,
    FOREIGN KEY (kategori_talent_id) REFERENCES Kategori_Talent(id)
);

CREATE TABLE Rundown (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_kegiatan VARCHAR(150) NOT NULL,
    tgl_wkt_mulai TIMESTAMP,
    tgl_wkt_akhir TIMESTAMP,
    lokasi_panggung_id INT,
    panitia_id INT,
    FOREIGN KEY (lokasi_panggung_id) REFERENCES Lokasi_Panggung(id),
    FOREIGN KEY (panitia_id) REFERENCES Panitia(id)
);

CREATE TABLE Guest_Star_Rundown (
    id INT AUTO_INCREMENT PRIMARY KEY,
    guest_star_id INT,
    rundown_id INT,
    FOREIGN KEY (guest_star_id) REFERENCES Guest_Star(id) ON DELETE CASCADE,
    FOREIGN KEY (rundown_id) REFERENCES Rundown(id) ON DELETE CASCADE
);

CREATE TABLE Tenant (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_tenant VARCHAR(100) NOT NULL,
    nama_pic VARCHAR(150) NOT NULL,
    telepon_pic VARCHAR(20),
    kategori_usaha_id INT,
    panitia_id INT,
    FOREIGN KEY (kategori_usaha_id) REFERENCES Kategori_Usaha(id),
    FOREIGN KEY (panitia_id) REFERENCES Panitia(id)
);

CREATE TABLE Booth (
    id VARCHAR(20) PRIMARY KEY,
    nama_booth VARCHAR(100) NOT NULL,
    harga_sewa DECIMAL(12,2) NOT NULL,
    lokasi_booth_id INT,
    tenant_id INT,
    FOREIGN KEY (lokasi_booth_id) REFERENCES Lokasi_Booth(id),
    FOREIGN KEY (tenant_id) REFERENCES Tenant(id) ON DELETE SET NULL
);

-- ==========================================
-- 2. INSERT DATA (Diadaptasi dari chibicon_db.sql)
-- ==========================================

-- Data Master (Lookup Tables)
INSERT INTO Divisi (nama_divisi) VALUES ('Ticketing'), ('Security'), ('Guest Handling'), ('Logistics');
INSERT INTO Metode_Pembayaran (nama_metode) VALUES ('QRIS'), ('Tunai'), ('Transfer Bank');
INSERT INTO Status_Pembayaran (nama_status) VALUES ('Menunggu Pembayaran'), ('Lunas'), ('Dibatalkan');
INSERT INTO Kategori_Talent (nama_kategori) VALUES ('VTuber'), ('Cosplayer'), ('Singer');
INSERT INTO Lokasi_Panggung (nama_lokasi) VALUES ('Main Stage'), ('Sakura Stage');
INSERT INTO Kategori_Usaha (nama_usaha) VALUES ('F&B'), ('Merchandise'), ('Service');
INSERT INTO Lokasi_Booth (nama_lokasi) VALUES ('Indoor'), ('Outdoor');

-- Data Pengunjung
INSERT INTO Pengunjung (nomor_identitas, nama_lengkap, email, telepon, tgl_wkt_daftar) VALUES
('3271042908950001', 'Aditya Rahman', 'aditya.r@example.com', '+62 812-3456-7890', '2023-10-12 14:30:00'),
('3174091205980003', 'Budi Perkasa', 'budi.p@example.com', '+62 856-7890-1234', '2023-10-12 11:15:00'),
('3201052311990002', 'Citra Dewi', 'citra.d@example.com', '+62 811-2233-4455', '2023-10-11 09:45:00');

-- Data Panitia
INSERT INTO Panitia (nama_lengkap, telepon, divisi_id) VALUES
('Ahmad Reza', '+62 812-3456-7890', 1),
('Budi Santoso', '+62 856-1122-3344', 2),
('Citra Kirana', '+62 878-9988-7766', 3),
('Eka Saputri', '+62 811-2233-4455', 1);

-- Data Tiket
INSERT INTO Tiket (nama_tiket, harga, deskripsi, kuota) VALUES
('Day 1 Pass', 75000, 'Akses penuh area eksibisi pada hari pertama.', 500),
('Day 2 Pass', 85000, 'Akses penuh area eksibisi dan panggung utama hari kedua.', 500),
('Season Pass VIP', 150000, 'Akses 2 hari penuh + Merchandise eksklusif.', 200);

-- Data Guest Star
INSERT INTO Guest_Star (nama_panggung, agensi, negara, kontak_manager, kategori_talent_id) VALUES
('Kobo Kanaeru', 'Hololive ID', 'Indonesia', 'mana@cover-corp.com', 1),
('Hakken Ryou', 'Independent', 'Malaysia', 'contact@hakken.co', 2),
('LiSA', 'Sony Music', 'Japan', 'mgmt@lxixsxa.com', 3);

-- Data Rundown & Jadwal Guest Star (Many-to-Many)
INSERT INTO Rundown (nama_kegiatan, tgl_wkt_mulai, tgl_wkt_akhir, lokasi_panggung_id, panitia_id) VALUES
('Opening Ceremony', '2023-11-11 10:00:00', '2023-11-11 11:00:00', 1, 3),
('Cosplay Meet & Greet', '2023-11-11 11:30:00', '2023-11-11 13:00:00', 2, 3),
('Anisong Concert Night', '2023-11-11 19:00:00', '2023-11-11 21:00:00', 1, 3);

INSERT INTO Guest_Star_Rundown (guest_star_id, rundown_id) VALUES
(2, 2), -- Hakken di Meet & Greet
(3, 3); -- LiSA di Concert Night

-- Data Tenant & Booth
INSERT INTO Tenant (nama_tenant, nama_pic, telepon_pic, kategori_usaha_id, panitia_id) VALUES
('Takoyaki Master', 'Budi Santoso', '0812-3456-7890', 1, 4),
('Anime Merch ID', 'Ahmad Reza', '0811-2233-4455', 2, 4);

INSERT INTO Booth (id, nama_booth, harga_sewa, lokasi_booth_id, tenant_id) VALUES
('A01', 'Booth A1', 2500000, 1, 1),
('A02', 'Booth A2', 2500000, 1, 2),
('A03', 'Booth A3', 2500000, 1, NULL);

-- Data Transaksi (Header & Detail Keranjang Belanja)
INSERT INTO Transaksi (tgl_wkt_transaksi, total, metode_pembayaran_id, status_pembayaran_id, pengunjung_id, panitia_id) VALUES
('2023-10-24 10:23:00', 150000, 1, 2, 2, 1), -- Transaksi Lunas via QRIS
('2023-10-24 10:45:00', 85000, 3, 1, 3, 4);  -- Transaksi Menunggu via Transfer

INSERT INTO Detail_Transaksi (kuantitas, subtotal, tiket_id, transaksi_id) VALUES
(2, 150000, 1, 1), -- Beli 2 Tiket Day 1 untuk Transaksi 1
(1, 85000, 2, 2);  -- Beli 1 Tiket Day 2 untuk Transaksi 2

-- ==========================================
-- 3. SQL TRIGGERS (Phase 2 - Data Integrity)
-- ==========================================

DELIMITER //

-- Trigger to prevent overselling: check if quota is sufficient BEFORE adding a detail
CREATE TRIGGER tr_check_kuota_before_insert
BEFORE INSERT ON Detail_Transaksi
FOR EACH ROW
BEGIN
    DECLARE current_kuota INT;
    
    SELECT kuota INTO current_kuota 
    FROM Tiket 
    WHERE id = NEW.tiket_id;
    
    IF current_kuota < NEW.kuantitas THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Kuota tiket tidak mencukupi untuk pesanan ini';
    END IF;
END //

-- Trigger to decrement quota AFTER a detail is inserted into a 'Lunas' transaction
CREATE TRIGGER tr_update_kuota_after_insert_detail
AFTER INSERT ON Detail_Transaksi
FOR EACH ROW
BEGIN
    DECLARE trans_status_name VARCHAR(50);
    
    SELECT sp.nama_status INTO trans_status_name
    FROM Transaksi t
    JOIN Status_Pembayaran sp ON t.status_pembayaran_id = sp.id
    WHERE t.id = NEW.transaksi_id;
    
    -- Check if Transaksi is 'Lunas'
    IF trans_status_name = 'Lunas' THEN
        UPDATE Tiket 
        SET kuota = kuota - NEW.kuantitas 
        WHERE id = NEW.tiket_id;
    END IF;
END //

-- Trigger to handle quota changes when Transaksi status changes
CREATE TRIGGER tr_update_kuota_after_update_transaksi
AFTER UPDATE ON Transaksi
FOR EACH ROW
BEGIN
    DECLARE old_status_name VARCHAR(50);
    DECLARE new_status_name VARCHAR(50);
    DECLARE insufficient_quota INT DEFAULT 0;

    SELECT nama_status INTO old_status_name FROM Status_Pembayaran WHERE id = OLD.status_pembayaran_id;
    SELECT nama_status INTO new_status_name FROM Status_Pembayaran WHERE id = NEW.status_pembayaran_id;

    -- Status changes FROM something else TO 'Lunas': REDUCE quota
    IF (old_status_name IS NULL OR old_status_name != 'Lunas') AND new_status_name = 'Lunas' THEN
        -- Check if ALL items have enough quota first
        SELECT COUNT(*) INTO insufficient_quota
        FROM Detail_Transaksi dt
        JOIN Tiket t ON dt.tiket_id = t.id
        WHERE dt.transaksi_id = NEW.id AND t.kuota < dt.kuantitas;
        
        IF insufficient_quota > 0 THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Gagal melunasi: Salah satu tiket dalam pesanan ini sudah habis (kuota tidak mencukupi).';
        END IF;

        UPDATE Tiket t
        JOIN Detail_Transaksi dt ON t.id = dt.tiket_id
        SET t.kuota = t.kuota - dt.kuantitas
        WHERE dt.transaksi_id = NEW.id;
        
    -- Status changes FROM 'Lunas' TO something else (e.g., 'Dibatalkan'): RETURN quota
    ELSEIF old_status_name = 'Lunas' AND (new_status_name IS NULL OR new_status_name != 'Lunas') THEN
        UPDATE Tiket t
        JOIN Detail_Transaksi dt ON t.id = dt.tiket_id
        SET t.kuota = t.kuota + dt.kuantitas
        WHERE dt.transaksi_id = NEW.id;
    END IF;
END //

-- Trigger to return quota if a detail is deleted from a 'Lunas' transaction
CREATE TRIGGER tr_return_kuota_after_delete_detail
AFTER DELETE ON Detail_Transaksi
FOR EACH ROW
BEGIN
    DECLARE trans_status_name VARCHAR(50);
    
    -- We can still access the Transaksi record to check its status
    SELECT sp.nama_status INTO trans_status_name
    FROM Transaksi t
    JOIN Status_Pembayaran sp ON t.status_pembayaran_id = sp.id
    WHERE t.id = OLD.transaksi_id;
    
    IF trans_status_name = 'Lunas' THEN
        UPDATE Tiket 
        SET kuota = kuota + OLD.kuantitas 
        WHERE id = OLD.tiket_id;
    END IF;
END //

DELIMITER ;
