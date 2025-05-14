<?php
session_start();
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST["username"];
    $email = $_POST["email"];
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];

    // Basic validation (same as before)
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "Tutti i campi sono obbligatori.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Formato email non valido.";
    } elseif ($password != $confirm_password) {
        $error = "Le password non corrispondono.";
    } else {
        // Get database connection
        require_once 'db.php';
        $database = new Database();
        $conn = $database->getConnection();

        if ($conn) {
            // Check if username or email already exists
            $stmt = $conn->prepare("SELECT UserID FROM Users WHERE Username = :username OR Email = :email");
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':email', $email);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $error = "Username o email già esistenti.";
            } else {
                // Hash the password securely
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // Insert new user into the database
                $stmt = $conn->prepare("INSERT INTO Users (Username, Email, PasswordHash) VALUES (:username, :email, :password)");
                $stmt->bindParam(':username', $username);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':password', $hashed_password);

                if ($stmt->execute()) {
                    // Send welcome email (requires email sending setup)
                    $to = $email;
                    $subject = "Benvenuto nel nostro sito!";
                    $message = "Ciao " . htmlspecialchars($username) . ",\n\nGrazie per esserti registrato al nostro sito!\nEsplora i nostri servizi e prenota i tuoi eventi preferiti.";
                    $headers = "From: webmaster@example.com\r\n"; // Replace with your email

                    mail($to, $subject, $message, $headers);

                    $_SESSION['registration_success'] = "Registrazione avvenuta con successo! Controlla la tua email.";
                    header("Location: login.php"); // Redirect to login page
                    exit();
                } else {
                    $error = "Errore durante la registrazione.";
                }
            }
        } else {
            $error = "Errore di connessione al database.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrazione</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { font-family: sans-serif; margin: 20px; }
        form { max-width: 400px; margin: 20px auto; padding: 20px; border: 1px solid #ccc; }
        label { display: block; margin-bottom: 5px; }
        input[type="text"], input[type="email"], input[type="password"] { width: 100%; padding: 8px; margin-bottom: 10px; box-sizing: border-box; }
        button { padding: 10px 15px; background-color: #007bff; color: white; border: none; cursor: pointer; }
        .error { color: red; margin-bottom: 10px; }
    </style>
</head>
<body>
<h1>Registrazione</h1>

<?php if (!empty($error)): ?>
    <p class="error"><?php echo $error; ?></p>
<?php endif; ?>

<form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
    <label for="username">Username:</label>
    <input type="text" id="username" name="username" required>

    <label for="email">Email:</label>
    <input type="email" id="email" name="email" required>

    <label for="password">Password:</label>
    <input type="password" id="password" name="password" required>

    <label for="confirm_password">Conferma Password:</label>
    <input type="password" id="confirm_password" name="confirm_password" required>

    <button type="submit">Registrati</button>
</form>

<p><a href="login.php">Hai già un account? Accedi qui</a></p>
<p><a href="index.php">Torna alla homepage</a></p>
</body>
</html>