CREATE DATABASE IF NOT EXISTS playroom_planner;
USE playroom_planner;

CREATE TABLE Iscritto (
    id_iscritto INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(50) NOT NULL,
    cognome VARCHAR(50) NOT NULL,
    data_nascita DATE NOT NULL,
    ruolo ENUM('docente', 'allievo', 'tecnico') NOT NULL DEFAULT 'allievo',
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    foto VARCHAR(255) DEFAULT 'default.png'
);

-- Note: The password for these samples is 'password123' (hashed)
INSERT INTO Iscritto (nome, cognome, data_nascita, ruolo, email, password) VALUES 
('Mario', 'Rossi', '1980-05-10', 'docente', 'mario@rossi.it', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('Luca', 'Verdi', '1995-12-20', 'allievo', 'luca@verdi.it', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- [Rest of your tables: Settore, Sala, Dotazione, etc. remain the same as your version]
CREATE TABLE Settore (
    id_settore INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(50) UNIQUE NOT NULL,
    tipo ENUM('musica', 'teatro', 'ballo') NOT NULL,
    id_responsabile INT NOT NULL,
    anni_servizio INT DEFAULT 0,
    data_nomina DATE,
    FOREIGN KEY (id_responsabile) REFERENCES Iscritto(id_iscritto)
);

CREATE TABLE Sala (
    id_sala INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(50) NOT NULL,
    capienza INT NOT NULL,
    id_settore INT NOT NULL,
    FOREIGN KEY (id_settore) REFERENCES Settore(id_settore),
    UNIQUE (nome, id_settore)
);