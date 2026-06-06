CREATE DATABASE IF NOT EXISTS tekrin;
USE tekrin;

CREATE TABLE users ( 
    id INT AUTO_INCREMENT PRIMARY KEY, 
    fullname VARCHAR(100) NOT NULL, 
    email VARCHAR(100) NOT NULL UNIQUE, 
    password VARCHAR(255) NOT NULL, 
    role ENUM( 
        'client', 
        'technicien', 
        'artisan', 
        'entreprise' 
    ) NOT NULL, 
    profile_picture VARCHAR(255) DEFAULT NULL, 
    profession VARCHAR(100) DEFAULT NULL,
    company_name VARCHAR(100) DEFAULT NULL,
    region VARCHAR(100) DEFAULT NULL,
    city VARCHAR(100) DEFAULT NULL,
    phone_number VARCHAR(20) DEFAULT NULL,
    whatsapp_number VARCHAR(20) DEFAULT NULL,
    bio TEXT DEFAULT NULL,
    services TEXT DEFAULT NULL,
    onboarding_completed TINYINT(1) DEFAULT 0,
    status ENUM('active', 'pending', 'suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP 
);

CREATE TABLE portfolio (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);