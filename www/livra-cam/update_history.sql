-- Mise à jour pour l'historique des colis
-- À exécuter dans phpMyAdmin (onglet SQL)

CREATE TABLE IF NOT EXISTS historique_colis (
    id INT AUTO_INCREMENT PRIMARY KEY,
    colis_id INT NOT NULL,
    statut VARCHAR(50) NOT NULL,
    description TEXT,
    date_heure TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (colis_id) REFERENCES colis(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Optionnel : Ajouter des données initiales pour les colis existants
INSERT INTO historique_colis (colis_id, statut, description, date_heure)
SELECT id, statut, 'Statut initial importé', created_at FROM colis;
