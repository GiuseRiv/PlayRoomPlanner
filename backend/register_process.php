<?php
require_once('../common/config.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = $_POST['nome'];
    $cognome = $_POST['cognome'];
    $email = $_POST['email'];
    $ruolo = $_POST['ruolo'];
    $data_nascita = $_POST['data_nascita'];

    try {
        $sql = "INSERT INTO Iscritto (nome, cognome, email, ruolo, data_nascita) 
                VALUES (:nome, :cognome, :email, :ruolo, :data_nascita)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'nome' => $nome,
            'cognome' => $cognome,
            'email' => $email,
            'ruolo' => $ruolo,
            'data_nascita' => $data_nascita
        ]);
        
        // Successo: torna alla root (index.php)
        header("Location: ../index.php?msg=reg_ok");
    } catch (PDOException $e) {
        die("Errore nel salvataggio: " . $e->getMessage());
    }
}
?>