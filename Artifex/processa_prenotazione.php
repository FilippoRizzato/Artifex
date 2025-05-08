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


if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['date_id']) && isset($_GET['price'])) {
    $date_id = $_GET['date_id'];
    $price = $_GET['price'];
    $user_id = $_SESSION['user_id'];


    if ($conn) {
        try {
            // Start transaction
            $conn->beginTransaction();


            // Check if a booking with 'Pending' status exists for the user
            $stmt_check_booking = $conn->prepare("SELECT BookingID FROM Bookings WHERE UserID = :user_id AND PaymentStatus = 'Pending'");
            $stmt_check_booking->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt_check_booking->execute();
            $booking_id = null;


            if ($stmt_check_booking->rowCount() > 0) {
                $row_booking = $stmt_check_booking->fetch(PDO::FETCH_ASSOC);
                $booking_id = $row_booking['BookingID'];
            } else {
                // Create a new booking
                $stmt_create_booking = $conn->prepare("INSERT INTO Bookings (UserID, TotalPrice, PaymentStatus) VALUES (:user_id, :price, 'Pending')");
                $stmt_create_booking->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                $stmt_create_booking->bindParam(':price', $price, PDO::PARAM_INT);
                $stmt_create_booking->execute();
                $booking_id = $conn->lastInsertId();
            }


            // Insert the booking detail
            $stmt_insert_detail = $conn->prepare("INSERT INTO BookingDetails (BookingID, DateID, Quantity, Subtotal) VALUES (:booking_id, :date_id, 1, :price)");
            $stmt_insert_detail->bindParam(':booking_id', $booking_id, PDO::PARAM_INT);
            $stmt_insert_detail->bindParam(':date_id', $date_id, PDO::PARAM_INT);
            $stmt_insert_detail->bindParam(':price', $price, PDO::PARAM_INT);
            $stmt_insert_detail->execute();


            // Update the total price of the booking
            $stmt_update_total = $conn->prepare("UPDATE Bookings SET TotalPrice = :total_price WHERE BookingID = :booking_id");
            $stmt_update_total->bindParam(':total_price', $price, PDO::PARAM_INT);
            $stmt_update_total->bindParam(':booking_id', $booking_id, PDO::PARAM_INT);
            $stmt_update_total->execute();


            $conn->commit();
            header("Location: cart.php"); // Redirect to cart
            exit();
        } catch (Exception $e) {
            $conn->rollBack();
            echo "Errore: " . $e->getMessage();
        }
    } else {
        echo "Errore di connessione al database.";
    }
} else {
    echo "Richiesta non valida.";
}
?>
