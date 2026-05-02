CREATE DATABASE IF NOT EXISTS pizza_online;
USE pizza_online;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    ruolo VARCHAR(20) DEFAULT 'cliente',
    indirizzo VARCHAR(255) NOT NULL
);

CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    prezzo DECIMAL(6,2) NOT NULL,
    disponibile TINYINT DEFAULT 1
);

CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    prodotto VARCHAR(100) NOT NULL,
    quantita INT NOT NULL,
    indirizzo VARCHAR(255) NOT NULL,
    note TEXT,
    stato VARCHAR(20) DEFAULT 'in_attesa',
    data_ora TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Admin: password = admin123
INSERT INTO users (nome, email, password, ruolo, indirizzo) VALUES
('Mario Rossi', 'admin@damario.it', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'Via Roma 1'),
('Luigi Staff', 'staff@damario.it', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff', 'Via Napoli 5');

INSERT INTO products (nome, prezzo) VALUES
('Margherita', 7.50),
('Marinara', 6.00),
('Diavola', 9.00),
('Quattro Stagioni', 10.00),
('Capricciosa', 10.50),
('Coca Cola', 2.00),
('Acqua', 1.50),
('Birra Moretti', 3.00),
('Tiramisu', 4.50);
