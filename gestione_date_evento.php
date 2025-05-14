<?php
session_start();
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: login.php");
    exit();
}

require_once 'db.php';
$database = new Database();
$conn = $database->getConnection();

$event_id = $_GET['event_id'] ?? null;

if (!$event_id) {
    echo "Errore: ID evento non specificato.";
    exit();
}

// Fetch event details
$stmt_event = $conn->prepare("SELECT * FROM Events WHERE EventID = ?");
$stmt_event->execute([$event_id]);
$event = $stmt_event->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    echo "Errore: Evento non trovato.";
    exit();
}

// Fetch event dates
$stmt_dates = $conn->prepare("SELECT * FROM EventDates WHERE EventID = ? ORDER BY EventDateTime");
$stmt_dates->execute([$event_id]);
$event_dates = $stmt_dates->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Date Evento</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<h1>Gestione Date per l'Evento: <?= htmlspecialchars($event['Name']) ?></h1>
<h2>Date Esistenti</h2>
<?php if (empty($event_dates)): ?>
    <p>Nessuna data presente per questo evento.</p>
<?php else: ?>
    <table>
        <thead>
        <tr>
            <th>ID Data</th>
            <th>Data e Ora</th>
            <th>Prezzo</th>
            <th>Posti Disponibili</th>
            <th>Azioni</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($event_dates as $date): ?>
            <tr>
                <td><?= $date['DateID'] ?></td>
                <td><?= date('d/m/Y H:i', strtotime($date['EventDateTime'])) ?></td>
                <td>€<?= number_format($date['Price'], 2) ?></td>
                <td><?= $date['AvailableSeats'] ?></td>
                <td>
                    <a href="modifica_data_evento.php?date_id=<?= $date['DateID'] ?>">Modifica</a> |
                    <a href="elimina_data_evento.php?date_id=<?= $date['DateID'] ?>" onclick="return confirm('Sei sicuro di voler eliminare questa data?');">Elimina</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<h2>Aggiungi Nuova Data</h2>
<form action="aggiungi_data_evento.php" method="post">
    <input type="hidden" name="event_id" value="<?= $event_id ?>">
    <label for="event_datetime">Data e Ora:</label>
    <input type="datetime-local" id="event_datetime" name="event_datetime" required><br>
    <label for="price">Prezzo:</label>
    <input type="number" id="price" name="price" step="0.01" required><br>
    <label for="available_seats">Posti Disponibili:</label>
    <input type="number" id="available_seats" name="available_seats" required><br>
    <button type="submit">Aggiungi Data</button>
</form>
</body>
</html>