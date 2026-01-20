CREATE DATABASE IF NOT EXISTS playroom_planner;
USE playroom_planner;

-- 1. CREAZIONE TABELLE

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

CREATE TABLE Settore (
    id_settore INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(50) UNIQUE NOT NULL,
    tipo ENUM('musica', 'teatro', 'ballo') NOT NULL,
    id_responsabile INT,
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

CREATE TABLE Dotazione (
    id_dotazione INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL
);

CREATE TABLE Prenotazione (
    id_prenotazione INT AUTO_INCREMENT PRIMARY KEY,
    data DATE NOT NULL,
    ora_inizio INT NOT NULL CHECK (ora_inizio BETWEEN 9 AND 23),
    durata_ore INT NOT NULL,
    attivita VARCHAR(100),
    stato ENUM('confermata', 'annullata') DEFAULT 'confermata',
    data_creazione TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    id_sala INT NOT NULL,
    id_organizzatore INT NOT NULL,
    FOREIGN KEY (id_sala) REFERENCES Sala(id_sala),
    FOREIGN KEY (id_organizzatore) REFERENCES Iscritto(id_iscritto)
);

CREATE TABLE afferisce (
    id_iscritto INT,
    id_settore INT,
    PRIMARY KEY (id_iscritto, id_settore),
    FOREIGN KEY (id_iscritto) REFERENCES Iscritto(id_iscritto),
    FOREIGN KEY (id_settore) REFERENCES Settore(id_settore)
);

CREATE TABLE contiene (
    id_sala INT,
    id_dotazione INT,
    PRIMARY KEY (id_sala, id_dotazione),
    FOREIGN KEY (id_sala) REFERENCES Sala(id_sala),
    FOREIGN KEY (id_dotazione) REFERENCES Dotazione(id_dotazione)
);

CREATE TABLE invito (
    id_iscritto INT,
    id_prenotazione INT,
    data_invio DATE,
    data_risposta DATETIME,
    stato ENUM('accettato', 'rifiutato', 'pendente') DEFAULT 'pendente',
    motivazione_rifiuto TEXT,
    PRIMARY KEY (id_iscritto, id_prenotazione),
    FOREIGN KEY (id_iscritto) REFERENCES Iscritto(id_iscritto),
    FOREIGN KEY (id_prenotazione) REFERENCES Prenotazione(id_prenotazione)
);


-- 2. POPOLAMENTO DATI

-- ISCRITTI
-- Nota: 
-- ID 3 = Anna Neri (Tecnico)
-- ID 9 = Davide Galli (Tecnico)
-- ID 13 = Lorenzo De Luca (Tecnico)

INSERT INTO Iscritto (nome, cognome, data_nascita, ruolo, email, password) VALUES
('Mario', 'Rossi', '1980-05-10', 'docente', 'mario.rossi@playroom.it', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('Luca', 'Verdi', '1995-12-20', 'allievo', 'luca.verdi@playroom.it', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('Anna', 'Neri', '1990-03-15', 'tecnico', 'anna.neri@playroom.it', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('Paola', 'Bianchi', '1992-07-22', 'allievo', 'paola.bianchi@playroom.it', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('Giovanni', 'Russo', '1975-02-18', 'docente', 'giovanni.russo@playroom.it', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('Elena', 'Ferrari', '1988-11-03', 'docente', 'elena.ferrari@playroom.it', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('Marco', 'Conti', '1999-06-25', 'allievo', 'marco.conti@playroom.it', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('Sara', 'Colombo', '2001-09-12', 'allievo', 'sara.colombo@playroom.it', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('Davide', 'Galli', '1994-01-30', 'tecnico', 'davide.galli@playroom.it', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('Francesca', 'Moretti', '1997-04-19', 'allievo', 'francesca.moretti@playroom.it', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('Alessio', 'Romano', '1985-08-07', 'docente', 'alessio.romano@playroom.it', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('Chiara', 'Fontana', '2000-12-02', 'allievo', 'chiara.fontana@playroom.it', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('Lorenzo', 'De Luca', '1993-03-21', 'tecnico', 'lorenzo.deluca@playroom.it', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');


INSERT INTO Settore (nome, tipo, id_responsabile, anni_servizio, data_nomina) VALUES
('Musica Moderna', 'musica', 1, 7, '2019-01-01'),
('Teatro Sperimentale', 'teatro', 6, 3, '2022-03-10'),
('Musica Classica', 'musica', 5, 7, '2017-09-01'),
('Danza Contemporanea', 'ballo', 11, 4, '2021-06-15');


INSERT INTO afferisce VALUES
(1, 1), (2, 1), 
(4, 2),          
(5, 3),         
(6, 4),         
(7, 3),         
(8, 2),         
(6, 2),         
(2, 3);          



INSERT INTO Sala (nome, capienza, id_settore) VALUES
('Sala prove A', 3, 1),
('Palco Piccolo', 10, 2),
('Sala Pianoforte', 4, 3),
('Sala Orchestra', 15, 3),
('Sala prove B', 6, 1),
('Sala Nera', 12, 2),
('Sala Specchi 1', 8, 4),
('Sala Specchi 2', 10, 4);


INSERT INTO Dotazione (nome) VALUES
('Batteria'), ('Mixer'), ('Specchi'),
('Pianoforte a coda'), ('Impianto luci'), ('Palcoscenico'), ('Amplificatori'), ('Sbarre danza');


INSERT INTO contiene VALUES
(1, 1), (1, 2), (2, 3),
(3, 4), (4, 5), (5, 6), (6, 7),
(7, 3), (7, 8), (8, 3), (8, 8);


INSERT INTO Prenotazione (data, ora_inizio, durata_ore, attivita, stato, id_sala, id_organizzatore) VALUES
('2025-02-01', 10, 2, 'Prove Band', 'confermata', 1, 1),
('2025-02-03', 9, 2, 'Prove orchestra', 'confermata', 3, 5),
('2025-02-03', 14, 3, 'Prove rock', 'confermata', 4, 1),
('2025-02-04', 16, 2, 'Laboratorio teatrale', 'confermata', 5, 3),
('2025-02-05', 10, 2, 'Lezione danza', 'confermata', 7, 7),
('2025-02-06', 18, 2, 'Sound check', 'confermata', 4, 1),
('2025-02-07', 20, 2, 'Prove spettacolo', 'annullata', 6, 3);


INSERT INTO invito (id_iscritto, id_prenotazione, data_invio, data_risposta, stato, motivazione_rifiuto) VALUES
(2, 1, '2025-01-20', NULL, 'accettato', NULL),
(4, 1, '2025-01-20', NULL, 'accettato', NULL),
(6, 2, '2025-01-28', '2025-01-29 18:20:00', 'rifiutato', 'Impegno lavorativo'),
(8, 3, '2025-01-29', '2025-01-30 15:30:00', 'accettato', NULL),
(9, 4, '2025-01-30', NULL, 'pendente', NULL),
(5, 5, '2025-02-01', '2025-02-02 17:45:00', 'accettato', NULL);