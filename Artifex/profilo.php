<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}


// Get database connection
require_once 'db.php';
$database = new Database();
$conn = $database->getConnection();


$user = [];
$booked_events = [];
$password_change_message = "";


if ($conn) {
    // Fetch user information
    if ($_SESSION['is_admin']) {
        // Fetch admin information
        $stmt = $conn->prepare("SELECT AdminID, Username, Email, FirstName, LastName FROM Admins WHERE AdminID = :user_id");
    } else {
        // Fetch user information
        $stmt = $conn->prepare("SELECT UserID, Username, Email, FirstName, LastName FROM Users WHERE UserID = :user_id");
    }
    $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);


    // Fetch booked and paid events for visitors
    if (!$_SESSION['is_admin']) {
        $sql_bookings = "SELECT bd.BookingDetailID, e.Name AS EventName, ed.EventDateTime
  FROM Bookings b
  JOIN BookingDetails bd ON b.BookingID = bd.BookingID
  JOIN EventDates ed ON bd.DateID = ed.DateID
  JOIN Events e ON ed.EventID = e.EventID
  WHERE b.UserID = :user_id AND b.PaymentStatus = 'Completed'
  ORDER BY ed.EventDateTime";
        $stmt_bookings = $conn->prepare($sql_bookings);
        $stmt_bookings->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
        $stmt_bookings->execute();
        $booked_events = $stmt_bookings->fetchAll(PDO::FETCH_ASSOC);
    }


    // Handle password change
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['change_password'])) {
        $old_password = $_POST["old_password"];
        $new_password = $_POST["new_password"];
        $confirm_new_password = $_POST["confirm_new_password"];


        if ($new_password != $confirm_new_password) {
            $password_change_message = "<p style='color: red;'>Le nuove password non corrispondono.</p>";
        } elseif (strlen($new_password) < 6) {
            $password_change_message = "<p style='color: red;'>La nuova password deve essere lunga almeno 6 caratteri.</p>";
        } else {
            if ($_SESSION['is_admin']) {
                // Verify old password for admin
                $sql_check_password = "SELECT Password FROM Admins WHERE AdminID = :user_id";
                $stmt_check_password = $conn->prepare($sql_check_password);
                $stmt_check_password->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
                $stmt_check_password->execute();
                $row_password = $stmt_check_password->fetch(PDO::FETCH_ASSOC);


                if ($old_password !== $row_password["Password"]) {
                    $password_change_message = "<p style='color: red;'>Vecchia password errata.</p>";
                } else {
                    // Update admin password
                    $sql_update_password = "UPDATE Admins SET Password = :new_password WHERE AdminID = :user_id";
                    $stmt_update_password = $conn->prepare($sql_update_password);
                    $stmt_update_password->bindParam(':new_password', $new_password);
                    $stmt_update_password->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
                    if ($stmt_update_password->execute()) {
                        $password_change_message = "<p style='color: green;'>Password cambiata con successo.</p>";
                    } else {
                        $password_change_message = "<p style='color: red;'>Errore durante il cambio password.</p>";
                    }
                }
            } else {
                // Verify old password for user
                $sql_check_password = "SELECT PasswordHash FROM Users WHERE UserID = :user_id";
                $stmt_check_password = $conn->prepare($sql_check_password);
                $stmt_check_password->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
                $stmt_check_password->execute();
                $row_password = $stmt_check_password->fetch(PDO::FETCH_ASSOC);


                if (!password_verify($old_password, $row_password["PasswordHash"])) {
                    $password_change_message = "<p style='color: red;'>Vecchia password errata.</p>";
                } else {
                    // Hash and update the new password for user
                    $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $sql_update_password = "UPDATE Users SET PasswordHash = :new_password WHERE UserID = :user_id";
                    $stmt_update_password = $conn->prepare($sql_update_password);
                    $stmt_update_password->bindParam(':new_password', $hashed_new_password);
                    $stmt_update_password->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
                    if ($stmt_update_password->execute()) {
                        $password_change_message = "<p style='color: green;'>Password cambiata con successo.</p>";
                    } else {
                        $password_change_message = "<p style='color: red;'>Errore durante il cambio password.</p>";
                    }
                }
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
    <title>Il Tuo Profilo</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { font-family: sans-serif; margin: 20px; }
        h1, h2 { color: #333; }
        .profile-info { margin-bottom: 20px; border: 1px solid #eee; padding: 15px; }
        .profile-info p { margin-bottom: 8px; }
        .form-container { border: 1px solid #eee; padding: 15px; margin-bottom: 20px; }
        label { display: block; margin-bottom: 5px; }
        input[type="password"] { width: 100%; padding: 8px; margin-bottom: 10px; box-sizing: border-box; }
        button { padding: 10px 15px; background-color: #007bff; color: white; border: none; cursor: pointer; }
        .booked-events ul { list-style-type: none; padding: 0; }
        .booked-events li { margin-bottom: 5px; }
    </style>
</head>
<body>
<h1>Il Tuo Profilo</h1>


<div class="profile-info">
    <h2>Informazioni Utente</h2>
    <p><strong>Username:</strong> <?php echo htmlspecialchars($user['Username']); ?></p>
    <p><strong>Email:</strong> <?php echo htmlspecialchars($user['Email']); ?></p>
    <?php if (!empty($user['FirstName']) || !empty($user['LastName'])): ?>
        <p><strong>Nome:</strong> <?php echo htmlspecialchars($user['FirstName']); ?> <?php echo htmlspecialchars($user['LastName']); ?></p>
    <?php endif; ?>
</div>


<?php if (!$_SESSION['is_admin']): ?>
    <div class="booked-events">
        <h2>Eventi Prenotati e Pagati</h2>
        <?php if (empty($booked_events)): ?>
            <p>Non hai ancora prenotato alcun evento.</p>
        <?php else: ?>
            <ul>
                <?php foreach ($booked_events as $event): ?>
                    <li><?php echo htmlspecialchars($event['EventName']); ?> - <?php echo date('d/m/Y H:i', strtotime($event['EventDateTime'])); ?> <a href="stampa_biglietto.php?event=<?php echo $event['BookingDetailID']; ?>" target="_blank">Stampa Biglietto</a></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
<?php endif; ?>


<div class="form-container">
    <h2>Cambia Password</h2>
    <?php echo $password_change_message; ?>
    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
        <label for="old_password">Vecchia Password:</label>
        <input type="password" id="old_password" name="old_password" required>


        <label for="new_password">Nuova Password:</label>
        <input type="password" id="new_password" name="new_password" required>


        <label for="confirm_new_password">Conferma Nuova Password:</label>
        <input type="password" id="confirm_new_password" name="confirm_new_password" required>


        <button type="submit" name="change_password">Cambia Password</button>
    </form>
</div>


<p><a href="<?php echo $_SESSION['is_admin'] ? 'admin_dashboard.php' : 'booking.php'; ?>">Torna alla Dashboard</a></p>
<p><a href="logout.php">Logout</a></p>
</body>
</html>