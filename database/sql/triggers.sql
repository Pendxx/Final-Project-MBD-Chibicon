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

    SELECT nama_status INTO old_status_name FROM Status_Pembayaran WHERE id = OLD.status_pembayaran_id;
    SELECT nama_status INTO new_status_name FROM Status_Pembayaran WHERE id = NEW.status_pembayaran_id;

    -- Status changes FROM something else TO 'Lunas': REDUCE quota
    IF (old_status_name IS NULL OR old_status_name != 'Lunas') AND new_status_name = 'Lunas' THEN
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
