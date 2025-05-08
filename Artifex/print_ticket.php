<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Include the TCPDF library
require_once('tcpdf/tcpdf.php');
require_once('phpqrcode/qrlib.php'); // Include the PHP QR Code library

// Get database connection
require_once 'db.php';
$database = new Database();
$conn = $database->getConnection();

if (isset($_GET['event'])) {
    $booking_detail_id = $_GET['event'];

    if ($conn) {
        // Fetch ticket details based on the booking detail ID and user ID
        $sql = "SELECT u.FirstName, u.LastName, e.Name AS EventName, e.Location AS EventLocation, ed.EventDateTime
                FROM BookingDetails bd
                JOIN Bookings b ON bd.BookingID = b.BookingID
                JOIN Users u ON b.UserID = u.UserID
                JOIN EventDates ed ON bd.DateID = ed.DateID
                JOIN Events e ON ed.EventID = e.EventID
                WHERE bd.BookingDetailID = :booking_detail_id AND b.UserID = :user_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':booking_detail_id', $booking_detail_id, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() == 1) {
            $ticket_data = $stmt->fetch(PDO::FETCH_ASSOC);
            $visitor_name = $ticket_data['FirstName'] . ' ' . $ticket_data['LastName'];
            $event_name = $ticket_data['EventName'];
            $event_location = $ticket_data['EventLocation'];
            $event_date = date('d/m/Y H:i', strtotime($ticket_data['EventDateTime']));
            $print_date = date('d/m/Y H:i');

            // Generate QR code data
            $qr_data = "Nome: " . $event_name . "\nLuogo: " . $event_location . "\nData: " . $event_date . "\nVisitatore: " . $visitor_name . "\nData Stampa: " . $print_date;
            $qr_file = 'qrcode_' . $booking_detail_id . '.png';
            $qr_path = 'qrcodes/' . $qr_file; // Create a 'qrcodes' directory

            // Generate QR code image
            QRcode::png($qr_data, $qr_path, QR_ECLEVEL_L, 4);

            // Create new PDF document
            $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8',false);

            // Set document information
            $pdf->SetCreator(PDF_CREATOR);
            $pdf->SetAuthor('Your Company Name');
            $pdf->SetTitle('Biglietto Evento');
            $pdf->SetSubject('Biglietto di Partecipazione');
            $pdf->SetKeywords('biglietto, evento');

            // Set default header data
            $pdf->SetHeaderData('', 0, 'Biglietto Evento', '');

            // Set header and footer fonts
            $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
            $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

            // Set default monospaced font
            $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

            // Set margins
            $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
            $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
            $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

            // Set auto page breaks
            $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

            // Set image scale factor
            $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

            // Set some language-dependent strings (optional)
            if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
                require_once(dirname(__FILE__).'/lang/eng.php');
                $pdf->setLanguageArray($l);
            }

            // Set font
            $pdf->SetFont('helvetica', '', 12);

            // Add a page
            $pdf->AddPage();

            // Output the ticket information
            $html = '<h1>Biglietto di Partecipazione</h1>';
            $html .= '<p><strong>Nome e Cognome:</strong> ' . htmlspecialchars($visitor_name) . '</p>';
            $html .= '<p><strong>Evento:</strong> ' . htmlspecialchars($event_name) . '</p>';
            $html .= '<p><strong>Luogo:</strong> ' . htmlspecialchars($event_location) . '</p>';
            $html .= '<p><strong>Data e Ora:</strong> ' . $event_date . '</p>';
            $html .= '<p><strong>Data Stampa:</strong> ' . $print_date . '</p>';
            $html .= '<img src="' . $qr_path . '" alt="QR Code">';

            $pdf->writeHTML($html, true, false, true, false, '');

            // Close and output PDF document
            $pdf->Output('biglietto_' . $booking_detail_id . '.pdf', 'I');

            // Clean up QR code image
            unlink($qr_path);
        } else {
            echo "Biglietto non trovato o non autorizzato.";
        }
    } else {
        echo "Errore di connessione al database.";
    }
} else {
    echo "ID evento non specificato.";
}

?>
