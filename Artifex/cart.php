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


$cart_items = [];
$total_price = 0;
if ($conn) {
    // Fetch items in the user's cart (bookings with 'Pending' status)
    $sql = "SELECT bd.BookingDetailID, e.Name AS EventName, ed.EventDateTime, ed.Price
 FROM Bookings b
 JOIN BookingDetails bd ON b.BookingID = bd.BookingID
 JOIN EventDates ed ON bd.DateID = ed.DateID
 JOIN Events e ON ed.EventID = e.EventID
 WHERE b.UserID = :user_id AND b.PaymentStatus = 'Pending'";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->execute();
    $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);


    error_log(print_r($cart_items, true)); // Debug: Log the cart items


    foreach ($cart_items as $item) {
        $total_price += $item['Price'];
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Il Tuo Carrello</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { font-family: sans-serif; margin: 20px; }
        h1 { color: #333; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .total { font-weight: bold; }
        .empty-cart { font-style: italic; color: #777; }
        .checkout-button { padding: 10px 20px; background-color: #5cb85c; color: white; border: none; cursor: pointer; font-size: 1em; }
    </style>
</head>
<body>
<h1>Il Tuo Carrello</h1>


<?php if (empty($cart_items)): ?>
    <p class="empty-cart">Il tuo carrello è vuoto.</p>
<?php else: ?>
    <table>
        <thead>
        <tr>
            <th>Evento</th>
            <th>Data e Ora</th>
            <th>Prezzo</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($cart_items as $item): ?>
            <tr>
                <td><?php echo htmlspecialchars($item['EventName']); ?></td>
                <td><?php echo date('d/m/Y H:i', strtotime($item['EventDateTime'])); ?></td>
                <td>€<?php echo number_format($item['Price'], 2); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
        <tr>
            <td colspan="2" class="total">Totale:</td>
            <td class="total">€<?php echo number_format($total_price, 2); ?></td>
        </tr>
        </tfoot>
    </table>
    <button class="checkout-button">Procedi al Pagamento</button>
<?php endif; ?>


<p><a href="booking.php">Torna alla pagina di prenotazione</a></p>
<p><a href="profilo.php">Il tuo Profilo</a></p>
<p><a href="logout.php">Logout</a></p>
</body>
</html>