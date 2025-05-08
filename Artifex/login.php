<?php
session_start();
$error = "";


if (isset($_SESSION['registration_success'])) {
    echo '<p style="color: green;">' . $_SESSION['registration_success'] . '</p>';
    unset($_SESSION['registration_success']);
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST["email"];
    $password = $_POST["password"];


    if (empty($email) || empty($password)) {
        $error = "Email e password sono obbligatori.";
    } else {
        // Get database connection
        require_once 'db.php';
        $database = new Database();
        $conn = $database->getConnection();


        if ($conn) {
            // Tentativo di login come admin
            $stmt_admin = $conn->prepare("SELECT AdminID, Username, FirstName, LastName FROM Admins WHERE Email = :email AND Password = :password");
            $stmt_admin->bindParam(':email', $email);
            $stmt_admin->bindParam(':password', $password);
            $stmt_admin->execute();


            if ($stmt_admin->rowCount() == 1) {
                $row_admin = $stmt_admin->fetch(PDO::FETCH_ASSOC);
                $_SESSION['user_id'] = $row_admin["AdminID"];
                $_SESSION['username'] = $row_admin["Username"];
                $_SESSION['is_admin'] = true;


                header("Location: admin_dashboard.php"); // Redirect to admin dashboard
                exit();
            } else {
                // Tentativo di login come utente normale
                $stmt_user = $conn->prepare("SELECT UserID, Username, PasswordHash FROM Users WHERE Email = :email");
                $stmt_user->bindParam(':email', $email);
                $stmt_user->execute();


                if ($stmt_user->rowCount() == 1) {
                    $row_user = $stmt_user->fetch(PDO::FETCH_ASSOC);
                    if (password_verify($password, $row_user["PasswordHash"])) {
                        $_SESSION['user_id'] = $row_user["UserID"];
                        $_SESSION['username'] = $row_user["Username"];
                        $_SESSION['is_admin'] = false;


                        header("Location: booking.php"); // Redirect to user area
                        exit();
                    } else {
                        $error = "Password errata.";
                    }
                } else {
                    $error = "Email non trovata.";
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
    <title>Login</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<h1>Login</h1>


<?php if (!empty($error)): ?>
    <p class="error"><?php echo $error; ?></p>
<?php endif; ?>


<form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
    <label for="email">Email:</label>
    <input type="email" id="email" name="email" required>


    <label for="password">Password:</label>
    <input type="password" id="password" name="password" required>


    <button type="submit">Accedi</button>
</form>


<p>Non hai un account? <a href="register.php">Registrati</a></p>
</body>
</html>