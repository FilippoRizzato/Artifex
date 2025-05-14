<?php
session_start();

// Cancella tutte le variabili di sessione
$_SESSION = array();

// Se si desidera distruggere completamente la sessione,
// cancellare anche il cookie di sessione.
// Nota: questo distruggerà la sessione, e non solo i dati della sessione!
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Infine, distrugge la sessione.
session_destroy();

// Reindirizza l'utente alla pagina di login o alla homepage
header("Location: login.php"); // Puoi cambiare 'login.php' con la pagina desiderata
exit();
?>
