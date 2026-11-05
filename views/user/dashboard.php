<?php
session_start();

// Vérifier si l'utilisateur est connecté ET s'il est bien un étudiant (user)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header('Location: ../../login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Tableau de bord - Étudiant</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <div style="padding: 20px; max-width: 800px; margin: auto;">
        <h1>Espace Étudiant</h1>
        <p>Bienvenue sur votre tableau de bord, <strong><?php echo htmlspecialchars($_SESSION['nom']); ?></strong> !</p>
        <p><a href="../../login.php?logout=1" style="color: red;">Se déconnecter</a></p>

        <hr style="margin: 20px 0;">

        <div style="background: #f9f9f9; padding: 20px; border-radius: 5px;">
            <h2>Mes Informations</h2>
            <p><strong>Nom :</strong> <?php echo htmlspecialchars($_SESSION['nom']); ?></p>
            <p><strong>Rôle :</strong> Étudiant</p>
            <p><strong>Statut :</strong> Compte actif</p>
        </div>

        <div style="margin-top: 20px; background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 5px;">
            <h2>Documents et Notes de service</h2>
            <p>Aucun document disponible pour le moment. Le secrétariat mettra bientôt vos documents en ligne ici.</p>
        </div>
    </div>
</body>
</html>