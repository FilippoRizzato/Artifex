<?php
session_start();
require 'header.php';
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: login.php");
    exit();
}

// Get database connection
require_once 'db.php';
$database = new Database();
$conn = $database->getConnection();

$events = [];
$event_dates = [];
$guides = [];

if ($conn) {
    // Fetch all events
    $stmt_events = $conn->prepare("
    SELECT Events.*, CONCAT(Guides.FirstName, ' ', Guides.LastName) AS GuideName
    FROM Events
    LEFT JOIN Guides ON Events.GuideID = Guides.GuideID
");
    $stmt_events->execute();
    $events = $stmt_events->fetchAll(PDO::FETCH_ASSOC);

    // Fetch all event dates
    $stmt_dates = $conn->prepare("SELECT ed.DateID, e.Name AS EventName, ed.EventDateTime, ed.Price, ed.AvailableSeats
                                 FROM EventDates ed
                                 JOIN Events e ON ed.EventID = e.EventID
                                 ORDER BY ed.EventDateTime");
    $stmt_dates->execute();
    $event_dates = $stmt_dates->fetchAll(PDO::FETCH_ASSOC);

    // Fetch all guides
    $stmt_guides = $conn->prepare("SELECT * FROM Guides");
    $stmt_guides->execute();
    $guides = $stmt_guides->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Dashboard Amministratore</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { font-family: sans-serif; margin: 20px; }
        h1, h2 { color: #333; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .admin-nav a { margin-right: 15px; text-decoration: none; color: blue; }
        .section { margin-bottom: 30px; border: 1px solid #eee; padding: 15px; }
    </style>
</head>
<body>
<h1>Dashboard Amministratore</h1>



<div class="section">
    <h2>Elenco Eventi</h2>
    <?php if (empty($events)): ?>
        <p>Nessun evento presente.</p>
    <?php else: ?>
        <table>
            <thead>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>Descrizione</th>
                <th>Luogo</th>
                <th>Guida ID</th>
                <th>Capacità</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($events as $event): ?>
                <tr>
                    <td><?php echo $event['EventID']; ?></td>
                    <td><?php echo htmlspecialchars($event['Name']); ?></td>
                    <td><?php echo htmlspecialchars(substr($event['Description'], 0, 50)) . '...'; ?></td>
                    <td><?php echo htmlspecialchars($event['Location']); ?></td>
                    <td><?php echo htmlspecialchars($event['GuideName']); ?></td>
                    <td><?php echo $event['Capacity']; ?></td>
                    <td>
                        <a href="modifica_evento.php?id=<?php echo $event['EventID']; ?>">Modifica</a> |
                        <a href="elimina_evento.php?id=<?php echo $event['EventID']; ?>" onclick="return confirm('Sei sicuro di voler eliminare questo evento?');">Elimina</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <p><a href="booking.php">Aggiungi Nuovo Evento</a></p>
    <?php endif; ?>
</div>

<div class="section">
    <h2>Elenco Date Eventi</h2>
    <?php if (empty($event_dates)): ?>
        <p>Nessuna data evento presente.</p>
    <?php else: ?>
        <table>
            <thead>
            <tr>
                <th>ID Data</th>
                <th>Evento</th>
                <th>Data e Ora</th>
                <th>Prezzo</th>
                <th>Posti Disponibili</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($event_dates as $date): ?>
                <tr>
                    <td><?php echo $date['DateID']; ?></td>
                    <td><?php echo htmlspecialchars($date['EventName']); ?></td>
                    <td><?php echo date('d/m/Y H:i', strtotime($date['EventDateTime'])); ?></td>
                    <td>€<?php echo number_format($date['Price'], 2); ?></td>
                    <td><?php echo $date['AvailableSeats']; ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

    <?php endif; ?>
</div>

<div class="section">
    <h2>Elenco Guide</h2>
    <?php if (empty($guides)): ?>
        <p>Nessuna guida presente.</p>
    <?php else: ?>
        <table>
            <thead>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>Cognome</th>
                <th>Biografia</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($guides as $guide): ?>
                <tr>
                    <td><?php echo $guide['GuideID']; ?></td>
                    <td><?php echo htmlspecialchars($guide['FirstName']); ?></td>
                    <td><?php echo htmlspecialchars($guide['LastName']); ?></td>
                    <td><?php echo htmlspecialchars(substr($guide['Bio'], 0, 50)) . '...'; ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <p><a href="aggiungi_guida.php">Aggiungi Nuova Guida</a></p>
    <?php endif; ?>
</div>

</body>
<?= require 'footer.php'; ?>
</html>