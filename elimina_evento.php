<?php
session_start();
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: login.php");
    exit();
}

require_once 'db.php';
$database = new Database();
$conn = $database->getConnection();

$id = $_GET['id'];

try {
    // Start a transaction
    $conn->beginTransaction();

    // Delete related rows in the bookingdetails table
    $stmt_booking = $conn->prepare("
        DELETE FROM BookingDetails 
        WHERE DateID IN (SELECT DateID FROM EventDates WHERE EventID = ?)
    ");
    $stmt_booking->execute([$id]);

    // Delete related rows in the eventdates table
    $stmt_dates = $conn->prepare("DELETE FROM EventDates WHERE EventID = ?");
    $stmt_dates->execute([$id]);

    // Delete the event in the events table
    $stmt_event = $conn->prepare("DELETE FROM Events WHERE EventID = ?");
    $stmt_event->execute([$id]);

    // Commit the transaction
    $conn->commit();

    header("Location: admin_dashboard.php");
    exit();
} catch (PDOException $e) {
    // Rollback the transaction in case of an error
    $conn->rollBack();
    echo "Errore: " . $e->getMessage();
}
?>