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
-- Tabella Settore
CREATE TABLE Settore (
id_settore INT AUTO_INCREMENT PRIMARY KEY,
nome VARCHAR(50) UNIQUE NOT NULL,
tipo ENUM('musica', 'teatro', 'ballo') NOT NULL,
id_responsabile INT NOT NULL,
anni_servizio INT DEFAULT 0,
data_nomina DATE,
FOREIGN KEY (id_responsabile) REFERENCES Iscritto(id_iscritto)
);

-- Tabella Sala
CREATE TABLE Sala (
id_sala INT AUTO_INCREMENT PRIMARY KEY,
nome VARCHAR(50) NOT NULL,
capienza INT NOT NULL,
id_settore INT NOT NULL,
FOREIGN KEY (id_settore) REFERENCES Settore(id_settore),
UNIQUE (nome, id_settore) -- Nome unico nel settore [cite: 17]
);

-- Tabella Dotazione
CREATE TABLE Dotazione (
id_dotazione INT AUTO_INCREMENT PRIMARY KEY,
nome VARCHAR(100) NOT NULL
);

-- Tabella Prenotazione
CREATE TABLE Prenotazione (
id_prenotazione INT AUTO_INCREMENT PRIMARY KEY,
data DATE NOT NULL,
ora_inizio INT NOT NULL CHECK (ora_inizio BETWEEN 9 AND 23), -- Ore intere 9-23 [cite: 19]
durata_ore INT NOT NULL,
attivita VARCHAR(100),
stato ENUM('confermata', 'annullata') DEFAULT 'confermata',
data_creazione TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
id_sala INT NOT NULL,
id_organizzatore INT NOT NULL,
FOREIGN KEY (id_sala) REFERENCES Sala(id_sala),
FOREIGN KEY (id_organizzatore) REFERENCES Iscritto(id_iscritto)
);

-- Relazione N:M Iscritto-Settore (Afferisce) [cite: 14]
CREATE TABLE afferisce (
id_iscritto INT,
id_settore INT,
PRIMARY KEY (id_iscritto, id_settore),
FOREIGN KEY (id_iscritto) REFERENCES Iscritto(id_iscritto),
FOREIGN KEY (id_settore) REFERENCES Settore(id_settore)
);

-- Relazione N:M Sala-Dotazione (Contiene) [cite: 17]
CREATE TABLE contiene (
id_sala INT,
id_dotazione INT,
PRIMARY KEY (id_sala, id_dotazione),
FOREIGN KEY (id_sala) REFERENCES Sala(id_sala),
FOREIGN KEY (id_dotazione) REFERENCES Dotazione(id_dotazione)
);

-- Relazione N:M Iscritto-Prenotazione (Invito) [cite: 19, 21]
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

-- ======================================================
-- POPOLAMENTO DATI DI ESEMPIO
-- ======================================================
INSERT INTO Iscritto (nome, cognome, data_nascita, ruolo, email) VALUES
('Mario', 'Rossi', '1980-05-10', 'docente', 'mario@rossi.it'),
('Luca', 'Verdi', '1995-12-20', 'allievo', 'luca@verdi.it'),
('Anna', 'Neri', '1990-03-15', 'tecnico', 'anna@neri.it'),
('Paola', 'Bianchi', '1992-07-22', 'allievo', 'paola@bianchi.it');

INSERT INTO Settore (nome, tipo, id_responsabile, anni_servizio, data_nomina) VALUES
('Musica Moderna', 'musica', 1, 5, '2019-01-01'),
('Teatro Sperimentale', 'teatro', 3, 2, '2022-03-10');

INSERT INTO afferisce VALUES (1, 1), (2, 1), (3, 2), (4, 2);

INSERT INTO Sala (nome, capienza, id_settore) VALUES
('Sala prove A', 3, 1),
('Palco Piccolo', 10, 2);

INSERT INTO Dotazione (nome) VALUES ('Batteria'), ('Mixer'), ('Specchi');
INSERT INTO contiene VALUES (1, 1), (1, 2), (2, 3);

INSERT INTO Prenotazione (data, ora_inizio, durata_ore, attivita, id_sala, id_organizzatore) VALUES
('2025-02-01', 10, 2, 'Prove Band', 1, 1);

INSERT INTO invito (id_iscritto, id_prenotazione, stato) VALUES
(2, 1, 'accettato'), (4, 1, 'accettato');)