USE gestion_sim;
CREATE TABLE IF NOT EXISTS sim_stock_lots (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 operator VARCHAR(50) NOT NULL DEFAULT 'Autre',
 received_date DATE NOT NULL,
 quantity_initial INT UNSIGNED NOT NULL,
 quantity_remaining INT UNSIGNED NOT NULL,
 notes TEXT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_stock_lot_remaining (quantity_remaining),
 INDEX idx_stock_lot_date (received_date),
 INDEX idx_stock_lot_operator (operator)
) ENGINE=InnoDB;
