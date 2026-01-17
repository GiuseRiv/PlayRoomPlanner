# Progettazione dell’Applicativo Web  
## Albero delle Funzionalità  
**Play Room Planner**

---

## 1. Introduzione

Il presente documento descrive l’albero delle funzionalità dell’applicativo Web *Play Room Planner*.  
L’albero delle funzionalità rappresenta una vista gerarchica delle operazioni offerte dal sistema, mettendo in evidenza le funzionalità di alto livello e le relative sotto-funzionalità messe a disposizione degli utenti.

L’obiettivo di questa rappresentazione è supportare la fase di progettazione dell’applicativo Web, garantendo coerenza con il dominio applicativo, con il modello dei dati e con le funzionalità richieste dalla specifica di progetto.

---

## 2. Albero delle funzionalità (descrizione testuale)

### 2.1 Gestione dell’accesso e dell’account
- Registrazione utente
- Autenticazione (login e logout)
- Gestione sessione
- Visualizzazione profilo utente
- Modifica profilo utente

### 2.2 Gestione utenti (responsabile di settore)
- Visualizzazione iscritti del settore
- Filtraggio utenti per ruolo
- Visualizzazione informazioni del responsabile di settore

### 2.3 Gestione sale prova
- Visualizzazione sale prova per settore
- Visualizzazione dettagli sala
- Visualizzazione dotazioni della sala

### 2.4 Gestione prenotazioni
- Creazione prenotazione
- Modifica prenotazione
- Cancellazione prenotazione
- Invito partecipanti
  - Invito singoli iscritti
  - Invito per categoria

### 2.5 Gestione inviti e partecipazione
- Visualizzazione inviti
- Accettazione invito
- Rifiuto invito con motivazione
- Rimozione dalla prenotazione

### 2.6 Visualizzazione calendario e impegni
- Visualizzazione impegni settimanali utente
- Visualizzazione prenotazioni settimanali per sala

### 2.7 Controlli applicativi e vincoli
- Controllo capienza sala
- Controllo sovrapposizioni temporali
- Verifica disponibilità sale
- Verifica disponibilità utenti

### 2.8 Statistiche e analisi
- Conteggio prenotazioni per giorno
- Conteggio prenotazioni per sala
- Conteggio prenotazioni organizzate
- Analisi avanzata partecipazione

### 2.9 Servizi applicativi (API) [Non ad alto livello, quindi non nel grafo!]
- Gestione utenti (CRUD)
- Gestione prenotazioni (CRUD)
- Visualizzazione impegni settimanali
- Visualizzazione prenotazioni settimanali

---

## 3. Albero delle funzionalità (rappresentazione grafica)

![albero](albero1.png)