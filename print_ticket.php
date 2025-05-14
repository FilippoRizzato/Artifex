<?php
// Start session
session_start();

// Include the TCPDF library and database connection
require('vendor/tecnickcom/tcpdf/tcpdf.php');
require('db.php');

// Check if user is logged in
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    die("Error: User not logged in.");
}

// Check if event_id is provided
if (!isset($_POST['event_id']) || empty($_POST['event_id'])) {
    die("Error: Event ID not specified.");
}

$event_id = $_POST['event_id'];

try {
    // Get database connection
    $database = new Database();
    $conn = $database->getConnection();

    // Fetch event details
    $stmt_event = $conn->prepare("SELECT e.Name AS EventName, e.Location, ed.EventDateTime
                                  FROM Events e
                                  JOIN EventDates ed ON e.EventID = ed.EventID
                                  WHERE ed.DateID = :event_id");
    $stmt_event->bindParam(':event_id', $event_id, PDO::PARAM_INT);
    $stmt_event->execute();
    $event = $stmt_event->fetch(PDO::FETCH_ASSOC);

    if (!$event) {
        die("Error: Event not found.");
    }

    // Fetch user details
    $stmt_user = $conn->prepare("SELECT FirstName, LastName FROM Users WHERE UserID = :user_id");
    $stmt_user->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt_user->execute();
    $user = $stmt_user->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        die("Error: User not found.");
    }

    // Assign event and user details to variables
    $name = $user['FirstName'];
    $lastname = $user['LastName'];
    $event_name = $event['EventName'];
    $event_location = $event['Location'];
    $event_datetime = date('d/m/Y H:i', strtotime($event['EventDateTime']));
} catch (PDOException $e) {
    die("Database connection error: " . $e->getMessage());
}

// Create a new PDF document
$pdf = new TCPDF();
$pdf->AddPage();

// Set background color
$pdf->SetFillColor(240, 240, 240);
$pdf->Rect(0, 0, 210, 297, "F");

// Add title
$pdf->SetFont('helvetica', 'B', 20);
$pdf->Cell(0, 10, 'Your Event Ticket', 0, 1, "C");
$pdf->Ln(10);

// Add user details
$pdf->SetFont('helvetica', '', 14);
$pdf->setTextColor(0, 0, 0);
$pdf->Cell(0, 10, "Name: {$name}", 0, 1, "L");
$pdf->Cell(0, 10, "Last Name: {$lastname}", 0, 1, "L");
$pdf->Cell(0, 10, "Event: {$event_name}", 0, 1, "L");
$pdf->Cell(0, 10, "Location: {$event_location}", 0, 1, "L");
$pdf->Cell(0, 10, "Date and Time: {$event_datetime}", 0, 1, "L");

// Add QR code
$qr_content = "Name: {$name} {$lastname}\nEvent: {$event_name}\nLocation: {$event_location}\nDate and Time: {$event_datetime}";
$pdf->write2DBarcode($qr_content, 'QRCODE,L', 10, 100, 50, 50, [], 'N');

// Add logo
$pdf->Image('logo.jpg', $pdf->getPageWidth() - 50, $pdf->getPageHeight() - 60, 30, 30);

// Add footer line
$pdf->Line(10, $pdf->getPageHeight() - 10, $pdf->getPageWidth() - 10, $pdf->getPageHeight() - 10);

// Output the PDF
$pdf->Output("Ticket.pdf", "I");
?>