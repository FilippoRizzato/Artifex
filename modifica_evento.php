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

$event_id = $_GET['id'] ?? null;

if (!$event_id) {
    echo "Errore: ID evento non specificato.";
    exit();
}

// Fetch event details
$stmt = $conn->prepare("SELECT * FROM Events WHERE EventID = ?");
$stmt->execute([$event_id]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    echo "Errore: Evento non trovato.";
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $location = $_POST['location'];
    $capacity = $_POST['capacity'];
    $event_date = $_POST['event_date'];

    $stmt_update = $conn->prepare("
        UPDATE Events 
        SET Name = ?, Description = ?, Location = ?, Capacity = ?, UpdatedAt = CURRENT_TIMESTAMP 
        WHERE EventID = ?
    ");
    $stmt_update->execute([$name, $description, $location, $capacity, $event_id]);

    // Update event date in EventDates table
    $stmt_date_update = $conn->prepare("
        UPDATE EventDates 
        SET EventDateTime = ? 
        WHERE EventID = ?
    ");
    $stmt_date_update->execute([$event_date, $event_id]);

    header("Location: admin_dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<body>
<h1>Modifica Evento</h1>
<form method="post">
    <label for="name">Nome:</label>
    <input type="text" id="name" name="name" value="<?= htmlspecialchars($event['Name']) ?>" required><br>

    <label for="description">Descrizione:</label>
    <textarea id="description" name="description" required><?= htmlspecialchars($event['Description']) ?></textarea><br>

    <label for="location">Luogo:</label>
    <input type="text" id="location" name="location" value="<?= htmlspecialchars($event['Location']) ?>" required><br>

    <label for="capacity">Capacità:</label>
    <input type="number" id="capacity" name="capacity" value="<?= htmlspecialchars($event['Capacity']) ?>" required><br>

    <label for="event_date">Data e Ora:</label>
    <input type="datetime-local" id="event_date" name="event_date" value="<?= date('Y-m-d\TH:i', strtotime($event['EventDateTime'])) ?>" required><br>

    <button type="submit">Salva Modifiche</button>
</form>
</body>
<?= require 'footer.php'; ?>
</html>