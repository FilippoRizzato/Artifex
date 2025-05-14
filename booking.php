<?php
session_start();
require 'header.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}


// Get database connection
require_once 'db.php';
$database = new Database();
$conn = $database->getConnection();


$events = [];
$error = "";


if ($conn) {
    // Fetch all available events with their dates
    $sql = "SELECT e.EventID, e.Name AS EventName, e.Description, ed.DateID, ed.EventDateTime, ed.Price
  FROM Events e
  JOIN EventDates ed ON e.EventID = ed.EventID
  WHERE ed.AvailableSeats > 0
  ORDER BY ed.EventDateTime";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);


    foreach ($result as $row) {
        $events[$row['EventID']]['name'] = $row['EventName'];
        $events[$row['EventID']]['description'] = $row['Description'];
        $events[$row['EventID']]['dates'][] = [
            'DateID' => $row['DateID'],
            'DateTime' => $row['EventDateTime'],
            'Price' => $row['Price']
        ];
    }


    // Handle event creation (ADMIN ONLY)
    if ($_SESSION['is_admin'] && $_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_event'])) {
        $name = $_POST['name'];
        $description = $_POST['description'];
        $location = $_POST['location'];
        $guide_id = $_POST['guide_id']; // Assuming you have a guide selection
        $capacity = $_POST['capacity'];
        $event_date_time = $_POST['event_date_time'];
        $price = $_POST['price'];


        // Validation (basic - expand as needed)
        if (empty($name) || empty($description) || empty($location) || empty($event_date_time) || empty($price) || empty($capacity)) {
            $error = "Tutti i campi sono richiesti.";
        } else {
            try {
                $conn->beginTransaction();


                // Create the event
                $stmt_create_event = $conn->prepare("INSERT INTO Events (Name, Description, Location, GuideID, Capacity) VALUES (:name, :description, :location, :guide_id, :capacity)");
                $stmt_create_event->bindParam(':name', $name);
                $stmt_create_event->bindParam(':description', $description);
                $stmt_create_event->bindParam(':location', $location);
                $stmt_create_event->bindParam(':guide_id', $guide_id);
                $stmt_create_event->bindParam(':capacity', $capacity);
                $stmt_create_event->execute();
                $event_id = $conn->lastInsertId();


                // Create the event date
                $stmt_create_date = $conn->prepare("INSERT INTO EventDates (EventID, EventDateTime, Price, AvailableSeats) VALUES (:event_id, :event_date_time, :price, :capacity)");
                $stmt_create_date->bindParam(':event_id', $event_id);
                $stmt_create_date->bindParam(':event_date_time', $event_date_time);
                $stmt_create_date->bindParam(':price', $price);
                $stmt_create_date->bindParam(':capacity', $capacity);
                $stmt_create_date->execute();


                $conn->commit();
                header("Location: booking.php"); // Refresh to show new event
                exit();
            } catch (Exception $e) {
                $conn->rollBack();
                $error = "Errore durante la creazione dell'evento: " . $e->getMessage();
            }
        }
    }


}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prenota Eventi</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { font-family: sans-serif; margin: 20px; }
        h1 { color: #333; }
        .event { border: 1px solid #ccc; padding: 15px; margin-bottom: 15px; }
        .event h2 { margin-top: 0; }
        .event-date { margin-bottom: 10px; }
        .cart-link { display: block; margin-top: 20px; text-align: right; }
        .cart-link a { text-decoration: none; color: green; font-weight: bold; }
        .admin-form { border: 1px solid #eee; padding: 15px; margin-bottom: 20px; }
        .admin-form label { display: block; margin-bottom: 5px; }
        .admin-form input, .admin-form textarea, .admin-form select { width: 100%; padding: 8px; margin-bottom: 10px; box-sizing: border-box; }
        .admin-form button { padding: 10px 15px; background-color: #007bff; color: white; border: none; cursor: pointer; }
        .error { color: red; }
    </style>
</head>
<body>
<h1>Prenota i Tuoi Eventi</h1>


<?php if (!empty($error)): ?>
    <p class="error"><?php echo $error; ?></p>
<?php endif; ?>


<?php if ($_SESSION['is_admin']): ?>
    <div class="admin-form">
        <h2>Crea Nuovo Evento</h2>
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <label for="name">Nome Evento:</label>
            <input type="text" id="name" name="name" required>


            <label for="description">Descrizione:</label>
            <textarea id="description" name="description" rows="4" required></textarea>


            <label for="location">Luogo:</label>
            <input type="text" id="location" name="location" required>


            <label for="guide_id">Guida:</label>
            <select id="guide_id" name="guide_id">
                <?php
                // Fetch guides from the database (replace with your actual query)
                $stmt_guides = $conn->prepare("SELECT GuideID, FirstName, LastName FROM Guides");
                $stmt_guides->execute();
                $guides = $stmt_guides->fetchAll(PDO::FETCH_ASSOC);
                foreach ($guides as $guide) {
                    echo "<option value='" . $guide['GuideID'] . "'>" . htmlspecialchars($guide['FirstName'] . " " . $guide['LastName']) . "</option>";
                }
                ?>
            </select>


            <label for="capacity">Capacità:</label>
            <input type="number" id="capacity" name="capacity" required>


            <label for="event_date_time">Data e Ora:</label>
            <input type="datetime-local" id="event_date_time" name="event_date_time" required>


            <label for="price">Prezzo:</label>
            <input type="number" id="price" name="price" step="0.01" required>


            <button type="submit" name="create_event">Crea Evento</button>
        </form>
    </div>
<?php endif; ?>


<?php if (empty($events)): ?>
    <p>Non ci sono eventi disponibili al momento.</p>
<?php else: ?>
    <?php foreach ($events as $event): ?>
        <div class="event">
            <h2><?php echo htmlspecialchars($event['name']); ?></h2>
            <p><?php echo htmlspecialchars($event['description']); ?></p>
            <?php if (!empty($event['dates'])): ?>
                <form method="post" action="aggiungi_al_carrello.php">
                    <?php foreach ($event['dates'] as $date): ?>
                        <div class="event-date">
                            <label>
                                <input type="checkbox" name="event_dates[]" value="<?php echo $date['DateID']; ?>">
                                <?php echo date('d/m/Y H:i', strtotime($date['DateTime'])); ?> - €<?php echo number_format($date['Price'], 2); ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                    <button type="submit">Aggiungi al Carrello</button>
                </form>
            <?php else: ?>
                <p>Non ci sono date disponibili per questo evento.</p>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>


<div class="cart-link">
    <a href="cart.php">Visualizza il Carrello</a>
</div>

</body>
<?= require 'footer.php'; ?>
</html>