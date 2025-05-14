<?php
session_start();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>ARTIFEX</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header>
    <div class="container">
        <h1>ARTIFEX</h1>
        <nav>
            <ul>
                <li><a href="logout.php">Esci</a></li>
                <li><a href="profilo.php">Visualizza il profilo</a></li>
                <li><a href="index.php">Torna alla home</a></li>
                <li><a href="booking.php">Vedi Eventi</a></li>
                <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                    <li><a href="admin_dashboard.php">Admin Dashboard</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</header>
<main>