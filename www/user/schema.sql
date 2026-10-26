CREATE TABLE IF NOT EXISTS roles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nom VARCHAR(50) UNIQUE NOT NULL
);

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nom VARCHAR(100) NOT NULL,
  email VARCHAR(150) UNIQUE NOT NULL,
  mot_de_passe VARCHAR(255) NOT NULL,
  role_id INT NOT NULL,
  FOREIGN KEY (role_id) REFERENCES roles(id)
);

CREATE TABLE IF NOT EXISTS produits (
  id INT AUTO_INCREMENT PRIMARY KEY,
  reference VARCHAR(100) UNIQUE NOT NULL,
  nom VARCHAR(200) NOT NULL,
  quantite INT NOT NULL DEFAULT 0,
  seuil_alerte INT NOT NULL DEFAULT 0,
  prix DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  description TEXT,
  image_path VARCHAR(255)
);

CREATE TABLE IF NOT EXISTS mouvements (
  id INT AUTO_INCREMENT PRIMARY KEY,
  type ENUM('entree','sortie','achat','vente','retour') NOT NULL,
  reference_produit VARCHAR(100) NOT NULL,
  quantite INT NOT NULL,
  date_mouvement DATE NOT NULL,
  responsable VARCHAR(100) NOT NULL,
  prix_unitaire DECIMAL(10,2) NULL,
  fournisseur_id INT NULL,
  client_id INT NULL
);

CREATE TABLE IF NOT EXISTS fournisseurs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nom VARCHAR(150) NOT NULL,
  contact VARCHAR(150)
);

CREATE TABLE IF NOT EXISTS clients (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nom VARCHAR(150) NOT NULL,
  contact VARCHAR(150)
);

INSERT IGNORE INTO roles (nom) VALUES ('administrateur'), ('manager'), ('magasinier'), ('vendeur');
