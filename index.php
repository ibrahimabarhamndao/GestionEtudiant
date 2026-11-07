<?php
session_start();

// Si l'utilisateur est déjà connecté, on l'oriente vers son dashboard
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'secretaire') {
        header('Location: views/secretaire/dashboard.php');
        exit();
    } else {
        header('Location: views/user/dashboard.php');
        exit();
    }
} else {
    // Sinon, on l'envoie vers la page de connexion
    header('Location: login.php');
    exit();
}
?>