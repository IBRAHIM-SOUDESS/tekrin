-- Base de données pour LIVRA-CAM
-- Création de la base de données
CREATE DATABASE IF NOT EXISTS livra_cam_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE livra_cam_db;

-- Création de la table 'colis'
CREATE TABLE IF NOT EXISTS colis (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tracking_code VARCHAR(20) NOT NULL UNIQUE,
    expediteur VARCHAR(100) NOT NULL,
    destinataire VARCHAR(100) NOT NULL,
    ville_depart VARCHAR(100) NOT NULL,
    ville_arrivee VARCHAR(100) NOT NULL,
    poids DECIMAL(10, 2) NOT NULL,
    type_colis VARCHAR(50) NOT NULL,
    statut ENUM('Collecté', 'En transit', 'Arrivé au dépôt', 'Livré') DEFAULT 'Collecté',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Index pour accélérer la recherche par code de suivi
CREATE INDEX idx_tracking_code ON colis(tracking_code);

-- Création de la table 'admins' pour la sécurité
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insertion d'un admin par défaut (Username: admin, Password: password123)
-- Le mot de passe est haché avec BCRYPT pour la sécurité
INSERT INTO admins (username, password) VALUES 
('admin', '$2y$10$kr1hCUvyEZViVOABzZhWjOa9OLOIbItmKA7xDbqplDrCgadbiuYCa')
ON DUPLICATE KEY UPDATE username=username;
