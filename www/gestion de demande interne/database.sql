-- Script SQL COMPLET et FINAL pour KAMAL Support Interne
-- Ce fichier crée la base de données et les tables avec TOUTES les fonctionnalités ITSM
-- (Priorité, Pièces jointes, Champs dynamiques, Notes de résolution, Audit)

-- Création de la base de données
CREATE DATABASE IF NOT EXISTS gestion_tickets;
USE gestion_tickets;

-- Suppression des tables si elles existent (pour réinitialiser proprement)
DROP TABLE IF EXISTS logs_audit;
DROP TABLE IF EXISTS demandes;

-- Création de la table 'demandes' (Structure ITSM complète)
CREATE TABLE demandes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom_employe VARCHAR(100) NOT NULL,
    service VARCHAR(100) NOT NULL,
    objet VARCHAR(255) NOT NULL,
    categorie VARCHAR(100) NOT NULL,
    urgence ENUM('basse', 'moyenne', 'haute') NOT NULL, -- Urgence brute saisie
    message TEXT NOT NULL,
    piece_jointe VARCHAR(255) DEFAULT NULL,             -- Nom du fichier uploadé
    num_serie VARCHAR(100) DEFAULT NULL,                -- Pour matériel
    type_equipement VARCHAR(100) DEFAULT NULL,          -- PC, Imprimante, etc.
    nom_logiciel VARCHAR(100) DEFAULT NULL,             -- Nom de l'appli métier
    impact ENUM('utilisateur', 'département', 'entreprise') DEFAULT 'utilisateur',
    priorite_calculee ENUM('basse', 'moyenne', 'haute', 'critique') DEFAULT 'basse',
    technicien_assigne VARCHAR(100) DEFAULT NULL,       -- Technicien en charge
    note_resolution TEXT DEFAULT NULL,                  -- Solution saisie par l'admin
    statut ENUM('en cours', 'terminé', 'annulé') DEFAULT 'en cours',
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_cloture TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table des logs d'audit (Sécurité et Traçabilité)
CREATE TABLE IF NOT EXISTS logs_audit (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT,
    action VARCHAR(255) NOT NULL,
    date_action TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    details TEXT,
    FOREIGN KEY (ticket_id) REFERENCES demandes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
