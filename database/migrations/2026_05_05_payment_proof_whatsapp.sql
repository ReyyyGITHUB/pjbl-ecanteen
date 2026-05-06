ALTER TABLE order_pesanan
  MODIFY COLUMN status_pesanan ENUM('diproses','siap_diambil','ditolak') NOT NULL;

ALTER TABLE payment
  MODIFY COLUMN metode_pembayaran ENUM('cash','qris') NOT NULL,
  MODIFY COLUMN status_pembayaran ENUM('menunggu_konfirmasi','pembayaran_dikonfirmasi','pembayaran_ditolak') NOT NULL,
  MODIFY COLUMN bukti_pembayaran VARCHAR(255) NOT NULL;

DROP PROCEDURE IF EXISTS add_ecanteen_column_if_missing;
DROP PROCEDURE IF EXISTS add_ecanteen_index_if_missing;

DELIMITER //

CREATE PROCEDURE add_ecanteen_column_if_missing(
  IN p_table_name VARCHAR(64),
  IN p_column_name VARCHAR(64),
  IN p_alter_sql TEXT
)
BEGIN
  IF NOT EXISTS (
    SELECT 1
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = p_table_name
      AND COLUMN_NAME = p_column_name
  ) THEN
    SET @ecanteen_alter_sql = p_alter_sql;
    PREPARE ecanteen_stmt FROM @ecanteen_alter_sql;
    EXECUTE ecanteen_stmt;
    DEALLOCATE PREPARE ecanteen_stmt;
  END IF;
END//

CREATE PROCEDURE add_ecanteen_index_if_missing(
  IN p_table_name VARCHAR(64),
  IN p_index_name VARCHAR(64),
  IN p_alter_sql TEXT
)
BEGIN
  IF NOT EXISTS (
    SELECT 1
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = p_table_name
      AND INDEX_NAME = p_index_name
  ) THEN
    SET @ecanteen_alter_sql = p_alter_sql;
    PREPARE ecanteen_stmt FROM @ecanteen_alter_sql;
    EXECUTE ecanteen_stmt;
    DEALLOCATE PREPARE ecanteen_stmt;
  END IF;
END//

DELIMITER ;

CALL add_ecanteen_column_if_missing(
  'order_pesanan',
  'kode_pesanan',
  'ALTER TABLE order_pesanan ADD COLUMN kode_pesanan VARCHAR(32) NULL AFTER id_order_pesanan'
);

CALL add_ecanteen_column_if_missing(
  'order_pesanan',
  'waktu_pengambilan',
  'ALTER TABLE order_pesanan ADD COLUMN waktu_pengambilan VARCHAR(80) NULL AFTER status_pesanan'
);

CALL add_ecanteen_column_if_missing(
  'order_pesanan',
  'catatan',
  'ALTER TABLE order_pesanan ADD COLUMN catatan TEXT NULL AFTER waktu_pengambilan'
);

CALL add_ecanteen_column_if_missing(
  'order_pesanan',
  'created_at',
  'ALTER TABLE order_pesanan ADD COLUMN created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER catatan'
);

CALL add_ecanteen_column_if_missing(
  'payment',
  'kode_pesanan',
  'ALTER TABLE payment ADD COLUMN kode_pesanan VARCHAR(32) NULL AFTER id_order_pesanan'
);

CALL add_ecanteen_column_if_missing(
  'payment',
  'bukti_original_name',
  'ALTER TABLE payment ADD COLUMN bukti_original_name VARCHAR(255) NULL AFTER bukti_pembayaran'
);

CALL add_ecanteen_column_if_missing(
  'payment',
  'bukti_mime_type',
  'ALTER TABLE payment ADD COLUMN bukti_mime_type VARCHAR(100) NULL AFTER bukti_original_name'
);

CALL add_ecanteen_column_if_missing(
  'payment',
  'bukti_file_size',
  'ALTER TABLE payment ADD COLUMN bukti_file_size INT NULL AFTER bukti_mime_type'
);

CALL add_ecanteen_column_if_missing(
  'payment',
  'wa_status',
  'ALTER TABLE payment ADD COLUMN wa_status ENUM(''pending'',''sent'',''failed'') NOT NULL DEFAULT ''pending'' AFTER bukti_file_size'
);

CALL add_ecanteen_column_if_missing(
  'payment',
  'wa_error',
  'ALTER TABLE payment ADD COLUMN wa_error TEXT NULL AFTER wa_status'
);

CALL add_ecanteen_column_if_missing(
  'payment',
  'wa_sent_at',
  'ALTER TABLE payment ADD COLUMN wa_sent_at DATETIME NULL AFTER wa_error'
);

CALL add_ecanteen_column_if_missing(
  'payment',
  'buyer_wa_status',
  'ALTER TABLE payment ADD COLUMN buyer_wa_status ENUM(''pending'',''sent'',''failed'') NOT NULL DEFAULT ''pending'' AFTER wa_sent_at'
);

CALL add_ecanteen_column_if_missing(
  'payment',
  'buyer_wa_error',
  'ALTER TABLE payment ADD COLUMN buyer_wa_error TEXT NULL AFTER buyer_wa_status'
);

CALL add_ecanteen_column_if_missing(
  'payment',
  'buyer_wa_sent_at',
  'ALTER TABLE payment ADD COLUMN buyer_wa_sent_at DATETIME NULL AFTER buyer_wa_error'
);

CALL add_ecanteen_index_if_missing(
  'order_pesanan',
  'idx_order_kode_pesanan',
  'ALTER TABLE order_pesanan ADD INDEX idx_order_kode_pesanan (kode_pesanan)'
);

CALL add_ecanteen_index_if_missing(
  'payment',
  'idx_payment_kode_pesanan',
  'ALTER TABLE payment ADD INDEX idx_payment_kode_pesanan (kode_pesanan)'
);

DROP PROCEDURE IF EXISTS add_ecanteen_column_if_missing;
DROP PROCEDURE IF EXISTS add_ecanteen_index_if_missing;
