USE gestion_sim;
CREATE TABLE IF NOT EXISTS sim_stock_affectations (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 seller_id INT UNSIGNED NOT NULL,
 operator VARCHAR(50) NOT NULL,
 quantity_initial INT UNSIGNED NOT NULL,
 quantity_remaining INT UNSIGNED NOT NULL,
 assigned_date DATE NOT NULL,
 notes TEXT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_affect_seller FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE RESTRICT,
 INDEX idx_affect_seller (seller_id), INDEX idx_affect_operator (operator), INDEX idx_affect_remaining (quantity_remaining)
) ENGINE=InnoDB;

ALTER TABLE subscriptions ADD COLUMN stock_allocation_id BIGINT UNSIGNED NULL AFTER seller_id;
ALTER TABLE subscriptions ADD INDEX idx_stock_allocation (stock_allocation_id);
