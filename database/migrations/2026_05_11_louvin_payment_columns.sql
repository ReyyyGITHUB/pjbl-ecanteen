ALTER TABLE payment
  ADD COLUMN louvin_transaction_id VARCHAR(100) NULL AFTER buyer_wa_sent_at,
  ADD COLUMN louvin_order_id VARCHAR(100) NULL AFTER louvin_transaction_id,
  ADD COLUMN louvin_status VARCHAR(30) NULL AFTER louvin_order_id,
  ADD COLUMN louvin_fee INT NOT NULL DEFAULT 0 AFTER louvin_status,
  ADD COLUMN louvin_net_amount INT NOT NULL DEFAULT 0 AFTER louvin_fee,
  ADD COLUMN louvin_payment_type VARCHAR(40) NULL AFTER louvin_net_amount,
  ADD COLUMN louvin_expired_at VARCHAR(40) NULL AFTER louvin_payment_type,
  ADD COLUMN louvin_raw_response MEDIUMTEXT NULL AFTER louvin_expired_at;
