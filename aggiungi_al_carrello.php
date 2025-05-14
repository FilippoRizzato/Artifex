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


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['event_dates']) && !empty($_POST['event_dates'])) {
        $event_dates = $_POST['event_dates'];
        error_log(print_r($event_dates, true)); // Debug: Log the event dates
        $user_id = $_SESSION['user_id'];


        if ($conn) {
            // Start transaction to ensure data consistency
            $conn->beginTransaction();


            try {
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
                    $stmt_create_booking = $conn->prepare("INSERT INTO Bookings (UserID, TotalPrice, PaymentStatus) VALUES (:user_id, 0, 'Pending')");
                    $stmt_create_booking->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                    $stmt_create_booking->execute();
                    $booking_id = $conn->lastInsertId();
                }


                $total_booking_price = 0;
                // Loop through selected event dates and add them to the cart
                foreach ($event_dates as $date_id) {
                    // Assuming quantity is always 1 for simplicity
                    $quantity = 1;


                    // Fetch the price from the database
                    $stmt_fetch_price = $conn->prepare("SELECT Price FROM EventDates WHERE DateID = :date_id");
                    $stmt_fetch_price->bindParam(':date_id', $date_id, PDO::PARAM_INT);
                    $stmt_fetch_price->execute();
                    $row_price = $stmt_fetch_price->fetch(PDO::FETCH_ASSOC);
                    $price = $row_price['Price'];
                    $subtotal = $price * $quantity;


                    // Check if the item already exists in the cart
                    $stmt_check_cart = $conn->prepare("SELECT BookingDetailID FROM BookingDetails WHERE BookingID = :booking_id AND DateID = :date_id");
                    $stmt_check_cart->bindParam(':booking_id', $booking_id, PDO::PARAM_INT);
                    $stmt_check_cart->bindParam(':date_id', $date_id, PDO::PARAM_INT);
                    $stmt_check_cart->execute();
                    $existing_item = $stmt_check_cart->fetch(PDO::FETCH_ASSOC);


                    if ($existing_item) {
                        // Update quantity if item exists (not needed in your simplified case)
                        // $new_quantity = $existing_item['Quantity'] + $quantity;
                        // $stmt_update_cart = $conn->prepare("UPDATE BookingDetails SET Quantity = :quantity, Subtotal = :subtotal WHERE BookingDetailID = :booking_detail_id");
                        // $stmt_update_cart->bindParam(':quantity', $new_quantity, PDO::PARAM_INT);
                        // $stmt_update_cart->bindParam(':subtotal', $price * $new_quantity);
                        // $stmt_update_cart->bindParam(':booking_detail_id', $existing_item['BookingDetailID']);
                        // $stmt_update_cart->execute();
                    } else {
                        // Insert new item into the cart
                        $stmt_insert_cart = $conn->prepare("INSERT INTO BookingDetails (BookingID, DateID, Quantity, Subtotal) VALUES (:booking_id, :date_id, :quantity, :subtotal)");
                        $stmt_insert_cart->bindParam(':booking_id', $booking_id, PDO::PARAM_INT);
                        $stmt_insert_cart->bindParam(':date_id', $date_id, PDO::PARAM_INT);
                        $stmt_insert_cart->bindParam(':quantity', $quantity, PDO::PARAM_INT);
                        $stmt_insert_cart->bindParam(':subtotal', $subtotal);
                        $stmt_insert_cart->execute();
                    }


                    $total_booking_price += $subtotal;
                }


                // Update the total price of the booking
                $stmt_update_total = $conn->prepare("UPDATE Bookings SET TotalPrice = :total_price WHERE BookingID = :booking_id");
                $stmt_update_total->bindParam(':total_price', $total_booking_price, PDO::PARAM_INT);
                $stmt_update_total->bindParam(':booking_id', $booking_id, PDO::PARAM_INT);
                $stmt_update_total->execute();


                $conn->commit();
                header("Location: cart.php"); // Redirect to cart page
                exit();
            } catch (Exception $e) {
                $conn->rollBack();
                echo "Errore: " . $e->getMessage();
            }
        } else {
            echo "Errore di connessione al database.";
        }
    } else {
        echo "Nessun evento selezionato.";
    }
}
?>