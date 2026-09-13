CREATE DATABASE IF NOT EXISTS gestion_sim CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE gestion_sim;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(80) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(150) NOT NULL,
    role ENUM('admin','vendeur') NOT NULL DEFAULT 'vendeur',
    seller_phone VARCHAR(30) NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS subscriptions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    seller_id INT UNSIGNED NOT NULL,
    sim_number VARCHAR(30) NOT NULL,
    operator VARCHAR(50) NOT NULL DEFAULT 'Autre',
    subscription_date DATE NOT NULL,
    first_deposit_reference VARCHAR(100) NULL,
    first_bundle_reference VARCHAR(100) NULL,
    mobile_money_reference VARCHAR(100) NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_sub_seller FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_sim (sim_number),
    INDEX idx_date (subscription_date),
    INDEX idx_seller (seller_id)
) ENGINE=InnoDB;

-- Compte administrateur de démonstration :
-- identifiant : admin
-- mot de passe : admin123
INSERT IGNORE INTO users (username,password_hash,full_name,role)
VALUES ('admin','$2y$12$cpkWjWHGh2w/FpYUa41IIuBJte6cVuz/LT/Xf/tiG1yjrXuJNdJEG','Administrateur','admin');

-- Gestion du stock SIM
CREATE TABLE IF NOT EXISTS sim_stock (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sim_number VARCHAR(30) NOT NULL UNIQUE,
    operator VARCHAR(50) NOT NULL DEFAULT 'Autre',
    received_date DATE NOT NULL,
    seller_id INT UNSIGNED NULL,
    status ENUM('disponible','affectee','souscrite') NOT NULL DEFAULT 'disponible',
    assigned_at DATETIME NULL,
    subscribed_at DATETIME NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_stock_seller FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_stock_status (status),
    INDEX idx_stock_seller (seller_id),
    INDEX idx_stock_date (received_date),
    INDEX idx_stock_operator (operator)
) ENGINE=InnoDB;


-- Mise à jour pour une base existante : ajouter la référence Mobile Money.
-- À exécuter une seule fois si la table subscriptions existe déjà sans cette colonne.
-- ALTER TABLE subscriptions ADD COLUMN mobile_money_reference VARCHAR(100) NULL AFTER first_bundle_reference;

-- Configuration Google Sheets
CREATE TABLE IF NOT EXISTS app_settings (
    setting_key VARCHAR(100) PRIMARY KEY,
    setting_value TEXT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;
INSERT IGNORE INTO app_settings(setting_key,setting_value) VALUES ('google_sheets_webapp_url','');


-- Stock SIM géré par quantité (aucun numéro individuel ni référence de lot)
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

-- Affectation de quantités aux vendeurs
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
    INDEX idx_affect_seller (seller_id),
    INDEX idx_affect_operator (operator),
    INDEX idx_affect_remaining (quantity_remaining)
) ENGINE=InnoDB;

-- Lien entre une souscription et l'affectation consommée
ALTER TABLE subscriptions ADD COLUMN stock_allocation_id BIGINT UNSIGNED NULL AFTER seller_id;
ALTER TABLE subscriptions ADD INDEX idx_stock_allocation (stock_allocation_id);
