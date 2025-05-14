<?php
session_start();
require 'header.php';
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: login.php");
    exit();
}

require_once 'db.php';
$database = new Database();
$conn = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = $_POST['first_name'] ?? null;
    $lastName = $_POST['last_name'] ?? null;
    $bio = $_POST['bio'] ?? null;

    if ($firstName && $lastName && $bio) {
        try {
            $stmt = $conn->prepare("INSERT INTO Guides (FirstName, LastName, Bio) VALUES (?, ?, ?)");
            $stmt->execute([$firstName, $lastName, $bio]);

            header("Location: admin_dashboard.php");
            exit();
        } catch (PDOException $e) {
            echo "Errore: " . $e->getMessage();
        }
    } else {
        echo "Errore: Tutti i campi sono obbligatori.";
    }
}
?>

<!DOCTYPE html>
<html lang="it">

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aggiungi Guida</title>
    <h1>Aggiungi Nuova Guida</h1>
    <form method="post">
        <label>Nome:</label>
        <input type="text" name="first_name" required><br>
        <label>Cognome:</label>
        <input type="text" name="last_name" required><br>
        <label>Biografia:</label>
        <textarea name="bio" required></textarea><br>
        <button type="submit">Aggiungi Guida</button>
    </form>
    <p><a href="gestione_guide.php">Torna alla Gestione Guide</a></p>
    </body>
    <?= require 'footer.php'; ?>
</html>