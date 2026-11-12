<?php
session_start();

// Gestion de la déconnexion
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: login.php');
    exit();
}

require_once 'config/db.php';

$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (!empty($login) && !empty($password)) {
        // Adaptation pour accepter les identifiants courts de votre maquette (secretaire / mdiallo)
        $search_email = $login;
        if ($login === 'secretaire') {
            $search_email = 'secretaire@plateforme.com';
        } elseif ($login === 'mdiallo' || $login === 'etudiant') {
            $search_email = 'etudiant@plateforme.com';
        }

        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR nom LIKE ?");
        $stmt->execute([$search_email, "%$login%"]);
        $user = $stmt->fetch();

        // Validation du mot de passe (tolérance pour 1234, 123456 ou hash)
// Vérification sécurisée du mot de passe.
if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
            $_SESSION['nom'] = $user['nom'];
            $_SESSION['role'] = $user['role'];

            if ($user['role'] === 'secretaire') {
                header('Location: views/secretaire/dashboard.php');
                exit();
            } else {
                header('Location: views/user/dashboard.php');
                exit();
            }
        } else {
            $erreur = "Identifiant ou mot de passe incorrect.";
        }
    } else {
        $erreur = "Veuillez remplir tous les champs.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Connexion - ESP 221</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* Mobile : seule la partie droite (formulaire) s'affiche, l'icône est en haut */
        .mobile-logo { display: none; }

        @media (max-width: 768px) {
            body.login-body { padding: 16px; }
            .login-left-panel { display: none; }
            .login-right-panel { flex: none; padding: 32px 24px; }

            .mobile-logo {
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 10px;
                margin-bottom: 24px;
            }
            .mobile-logo-icon {
                width: 68px;
                height: 68px;
                border-radius: 20px;
                background: #e63946;
                color: #fff;
                display: flex;
                justify-content: center;
                align-items: center;
                box-shadow: 0 8px 20px rgba(230, 57, 70, 0.3);
            }
            .mobile-logo-tag {
                font-size: 12px;
                font-weight: 700;
                letter-spacing: 2px;
                color: #e63946;
            }

            .form-header { text-align: center; margin-bottom: 24px; }
            .form-header h2 { font-size: 22px; }
        }
    </style>
</head>
<body class="login-body">
    <div class="login-container-card">
        <!-- Partie Gauche : Bloc Rouge Institutionnel -->
        <div class="login-left-panel">
            <div class="logo-box">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                    <polyline points="9 22 9 12 15 12 15 22"></polyline>
                </svg>
            </div>
            <h2>École Supérieure<br>Professionnelle 221</h2>
            <p class="left-subtitle">Système de Gestion Scolaire</p>
            
            <div class="left-footer-text">
                <p>Plateforme officielle de gestion<br>académique et administrative</p>
                <span class="esp-tag">ESP 221</span>
            </div>
        </div>

        <!-- Partie Droite : Formulaire de Connexion -->
        <div class="login-right-panel">
            <!-- Icône affichée en haut sur mobile (la partie gauche est masquée) -->
            <div class="mobile-logo">
                <div class="mobile-logo-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                        <polyline points="9 22 9 12 15 12 15 22"></polyline>
                    </svg>
                </div>
                <span class="mobile-logo-tag">ESP 221</span>
            </div>

            <div class="form-header">
                <h2>Connexion</h2>
                <p>Accédez à votre espace personnel</p>
            </div>

            <?php if (!empty($erreur)): ?>
                <div class="alert-error"><?php echo $erreur; ?></div>
            <?php endif; ?>

            <form action="login.php" method="POST">
                <div class="form-group">
                    <label for="email">IDENTIFIANT / LOGIN</label>
                    <input type="text" id="email" name="email" required placeholder="secretaire" autocomplete="off">
                </div>

                <div class="form-group">
                    <label for="password">MOT DE PASSE</label>
                    <input type="password" id="password" name="password" required placeholder="........" autocomplete="new-password">
                </div>

                <button type="submit" class="btn-submit">Se connecter &rarr;</button>
            </form>

            <div class="demo-footer">
                <small>Démo — Secrétaire : secretaire / 1234 | Étudiant : mdiallo / 1234</small>
            </div>
        </div>
    </div>
</body>
</html>