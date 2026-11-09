
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pageActuelle = 'parametres';
$titrePage = 'Paramètres';
$descriptionPage = 'Configuration de l\'application';

require_once '../../config/db.php';

$message = '';
$succes = '';

$erreurs = [
    'nom_etablissement' => '',
    'adresse' => '',
    'email' => '',
    'telephone' => '',
    'annee_cours' => '',
    'date_rentree' => '',
    'date_cloture' => '',
    'password_actuel' => '',
    'nouveau_password' => '',
    'confirmer_password' => ''
];

function h($valeur) {
    return htmlspecialchars((string)$valeur, ENT_QUOTES, 'UTF-8');
}

function classeChamp($erreur) {
    return $erreur
        ? 'border-red-500 focus:border-red-500'
        : 'border-gray-200 focus:border-red-600';
}

// Valeurs par défaut
$etablissement = [
    'nom_etablissement' => 'École Supérieure Professionnelle 221',
    'adresse' => 'Dakar, Sénégal',
    'email' => 'contact@esp221.sn',
    'telephone' => '+221 33 000 00 00',
    'annee_cours' => '2026-2027',
    'date_rentree' => '',
    'date_cloture' => ''
];

// Création et récupération des paramètres
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS parametres_application (
            id INT PRIMARY KEY,
            nom_etablissement VARCHAR(150) NOT NULL,
            adresse VARCHAR(255) NOT NULL,
            email VARCHAR(190) NOT NULL,
            telephone VARCHAR(30) NOT NULL,
            annee_cours VARCHAR(20) NOT NULL,
            date_rentree DATE NULL,
            date_cloture DATE NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ON UPDATE CURRENT_TIMESTAMP
        )
    ");

    $stmt = $pdo->query("
        SELECT *
        FROM parametres_application
        WHERE id = 1
    ");

    $parametres = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($parametres) {
        $etablissement = array_merge($etablissement, $parametres);
    }

} catch (PDOException $e) {
    $message = "Impossible de charger les paramètres de l'application.";
}

// Traitement des formulaires
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    $action = $_POST['action'];

    // ==========================================
    // 1. INFORMATIONS DE L'ÉTABLISSEMENT
    // ==========================================
    if ($action === 'modifier_infos') {

        $nom = trim($_POST['nom_etablissement'] ?? '');
        $adresse = trim($_POST['adresse'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $telephone = trim($_POST['telephone'] ?? '');

        if ($nom === '') {
            $erreurs['nom_etablissement'] = "Le nom de l'établissement est obligatoire.";
        } elseif (mb_strlen($nom) < 2 || mb_strlen($nom) > 150) {
            $erreurs['nom_etablissement'] = "Le nom doit contenir entre 2 et 150 caractères.";
        }

        if ($adresse === '') {
            $erreurs['adresse'] = "L'adresse est obligatoire.";
        } elseif (mb_strlen($adresse) < 3 || mb_strlen($adresse) > 255) {
            $erreurs['adresse'] = "L'adresse doit contenir entre 3 et 255 caractères.";
        }

        if ($email === '') {
            $erreurs['email'] = "L'adresse e-mail est obligatoire.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erreurs['email'] = "Veuillez saisir une adresse e-mail valide.";
        }

        if ($telephone === '') {
            $erreurs['telephone'] = "Le numéro de téléphone est obligatoire.";
        } elseif (!preg_match('/^[0-9+\s().-]{8,30}$/', $telephone)) {
            $erreurs['telephone'] = "Veuillez saisir un numéro de téléphone valide.";
        }

        if (!array_filter($erreurs)) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO parametres_application
                    (id, nom_etablissement, adresse, email, telephone, annee_cours)
                    VALUES (1, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                    nom_etablissement = VALUES(nom_etablissement),
                    adresse = VALUES(adresse),
                    email = VALUES(email),
                    telephone = VALUES(telephone)
                ");

                $stmt->execute([
                    $nom,
                    $adresse,
                    $email,
                    $telephone,
                    $etablissement['annee_cours']
                ]);

                $succes = "Les informations de l'établissement ont été mises à jour avec succès.";

                $etablissement['nom_etablissement'] = $nom;
                $etablissement['adresse'] = $adresse;
                $etablissement['email'] = $email;
                $etablissement['telephone'] = $telephone;

            } catch (PDOException $e) {
                $message = "Une erreur est survenue lors de l'enregistrement des informations.";
            }
        } else {
            $etablissement['nom_etablissement'] = $nom;
            $etablissement['adresse'] = $adresse;
            $etablissement['email'] = $email;
            $etablissement['telephone'] = $telephone;
        }
    }

    // ==========================================
    // 2. ANNÉE ACADÉMIQUE
    // ==========================================
    elseif ($action === 'modifier_annee') {

        $annee = trim($_POST['annee_cours'] ?? '');
        $dateRentree = trim($_POST['date_rentree'] ?? '');
        $dateCloture = trim($_POST['date_cloture'] ?? '');

        if ($annee === '') {
            $erreurs['annee_cours'] = "L'année académique est obligatoire.";
        } elseif (!preg_match('/^\d{4}-\d{4}$/', $annee)) {
            $erreurs['annee_cours'] = "Format attendu : 2026-2027.";
        } else {
            [$debutAnnee, $finAnnee] = array_map('intval', explode('-', $annee));

            if ($finAnnee !== $debutAnnee + 1) {
                $erreurs['annee_cours'] = "Les deux années doivent se suivre.";
            }
        }

        if ($dateRentree === '') {
            $erreurs['date_rentree'] = "La date de rentrée est obligatoire.";
        } else {
            $date = DateTime::createFromFormat('!Y-m-d', $dateRentree);

            if (!$date || $date->format('Y-m-d') !== $dateRentree) {
                $erreurs['date_rentree'] = "Veuillez saisir une date valide.";
            }
        }

        if ($dateCloture === '') {
            $erreurs['date_cloture'] = "La date de clôture est obligatoire.";
        } else {
            $date = DateTime::createFromFormat('!Y-m-d', $dateCloture);

            if (!$date || $date->format('Y-m-d') !== $dateCloture) {
                $erreurs['date_cloture'] = "Veuillez saisir une date valide.";
            }
        }

        if (
            empty($erreurs['date_rentree']) &&
            empty($erreurs['date_cloture']) &&
            $dateCloture <= $dateRentree
        ) {
            $erreurs['date_cloture'] = "La date de clôture doit être après la date de rentrée.";
        }

        if (!array_filter($erreurs)) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO parametres_application
                    (id, nom_etablissement, adresse, email, telephone, annee_cours, date_rentree, date_cloture)
                    VALUES (1, ?, ?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                    annee_cours = VALUES(annee_cours),
                    date_rentree = VALUES(date_rentree),
                    date_cloture = VALUES(date_cloture)
                ");

                $stmt->execute([
                    $etablissement['nom_etablissement'],
                    $etablissement['adresse'],
                    $etablissement['email'],
                    $etablissement['telephone'],
                    $annee,
                    $dateRentree,
                    $dateCloture
                ]);

                $succes = "L'année académique a été enregistrée avec succès.";

                $etablissement['annee_cours'] = $annee;
                $etablissement['date_rentree'] = $dateRentree;
                $etablissement['date_cloture'] = $dateCloture;

            } catch (PDOException $e) {
                $message = "Une erreur est survenue lors de l'enregistrement de l'année académique.";
            }
        } else {
            $etablissement['annee_cours'] = $annee;
            $etablissement['date_rentree'] = $dateRentree;
            $etablissement['date_cloture'] = $dateCloture;
        }
    }

    // ==========================================
    // 3. SÉCURITÉ DU COMPTE
    // ==========================================
    elseif ($action === 'modifier_securite') {

        $passwordActuel = $_POST['password_actuel'] ?? '';
        $nouveauPassword = $_POST['nouveau_password'] ?? '';
        $confirmerPassword = $_POST['confirmer_password'] ?? '';

        if ($passwordActuel === '') {
            $erreurs['password_actuel'] = "Veuillez saisir votre mot de passe actuel.";
        }

        if ($nouveauPassword === '') {
            $erreurs['nouveau_password'] = "Veuillez saisir un nouveau mot de passe.";
        } elseif (strlen($nouveauPassword) < 8) {
            $erreurs['nouveau_password'] = "Le mot de passe doit contenir au moins 8 caractères.";
        } elseif (
            !preg_match('/[A-Z]/', $nouveauPassword) ||
            !preg_match('/[a-z]/', $nouveauPassword) ||
            !preg_match('/[0-9]/', $nouveauPassword)
        ) {
            $erreurs['nouveau_password'] = "Utilisez au moins une majuscule, une minuscule et un chiffre.";
        }

        if ($confirmerPassword === '') {
            $erreurs['confirmer_password'] = "Veuillez confirmer le nouveau mot de passe.";
        } elseif ($nouveauPassword !== $confirmerPassword) {
            $erreurs['confirmer_password'] = "Les deux mots de passe ne correspondent pas.";
        }

        if (!isset($_SESSION['user_id']) || !is_numeric($_SESSION['user_id'])) {
            $message = "Votre session a expiré. Veuillez vous reconnecter.";
        } elseif (!array_filter($erreurs)) {
            try {
                $stmt = $pdo->prepare("
                    SELECT password
                    FROM users
                    WHERE id = ?
                ");

                $stmt->execute([$_SESSION['user_id']]);
                $utilisateur = $stmt->fetch(PDO::FETCH_ASSOC);

                if (
                    !$utilisateur ||
                    !password_verify($passwordActuel, $utilisateur['password'])
                ) {
                    $erreurs['password_actuel'] = "Le mot de passe actuel est incorrect.";
                } else {
                    $nouveauHash = password_hash($nouveauPassword, PASSWORD_DEFAULT);

                    $stmt = $pdo->prepare("
                        UPDATE users
                        SET password = ?
                        WHERE id = ?
                    ");

                    $stmt->execute([
                        $nouveauHash,
                        $_SESSION['user_id']
                    ]);

                    $succes = "Votre mot de passe a été modifié avec succès.";
                }

            } catch (PDOException $e) {
                $message = "Une erreur est survenue lors de la modification du mot de passe.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration — Paramètres</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        * {
            scrollbar-width: none;
            -ms-overflow-style: none;
        }

        *::-webkit-scrollbar {
            display: none;
        }
    </style>
</head>

<body class="bg-gray-50 flex h-screen overflow-hidden font-sans">

    <!-- Sidebar -->
    <?php include '../../includes/sidebar_secretaire.php'; ?>

    <!-- Contenu principal -->
    <main class="flex-1 flex flex-col h-screen overflow-hidden">

        <!-- Header -->
        <?php include '../../includes/header.php'; ?>

        <!-- Corps de la page -->
        <div class="flex-1 p-8 overflow-y-auto space-y-6 max-w-5xl">

            <!-- Message de succès : entièrement vert, sans bordure -->
            <?php if (!empty($succes)): ?>
                <div class="p-4 bg-emerald-100 text-emerald-800 text-sm rounded-lg shadow-sm flex items-center gap-3">
                    <i class="fa-solid fa-circle-check text-emerald-700"></i>
                    <?= h($succes) ?>
                </div>
            <?php endif; ?>

            <!-- Message d'erreur général -->
            <?php if (!empty($message)): ?>
                <div class="p-4 bg-red-100 text-red-800 text-sm rounded-lg shadow-sm flex items-center gap-3">
                    <i class="fa-solid fa-circle-exclamation text-red-700"></i>
                    <?= h($message) ?>
                </div>
            <?php endif; ?>

            <!-- 1. INFORMATIONS DE L'ÉTABLISSEMENT -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 space-y-6">

                <div>
                    <h3 class="font-bold text-sm text-gray-800">
                        Informations de l'établissement
                    </h3>
                    <p class="text-xs text-gray-400">
                        Modifier les informations générales de l'école
                    </p>
                </div>

                <form action="parametres.php" method="POST" class="space-y-4" novalidate>

                    <input type="hidden" name="action" value="modifier_infos">

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">
                            Nom de l'établissement
                        </label>

                        <input
                            type="text"
                            name="nom_etablissement"
                            value="<?= h($etablissement['nom_etablissement']) ?>"
                            class="w-full px-3 py-2.5 border rounded-lg text-sm focus:outline-none <?= classeChamp($erreurs['nom_etablissement']) ?>"
                        >

                        <?php if ($erreurs['nom_etablissement']): ?>
                            <p class="text-red-600 text-xs mt-1">
                                <?= h($erreurs['nom_etablissement']) ?>
                            </p>
                        <?php endif; ?>
                    </div>

                    <div class="grid grid-cols-2 gap-4">

                        <div>
                            <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">
                                Adresse
                            </label>

                            <input
                                type="text"
                                name="adresse"
                                value="<?= h($etablissement['adresse']) ?>"
                                class="w-full px-3 py-2.5 border rounded-lg text-sm focus:outline-none <?= classeChamp($erreurs['adresse']) ?>"
                            >

                            <?php if ($erreurs['adresse']): ?>
                                <p class="text-red-600 text-xs mt-1">
                                    <?= h($erreurs['adresse']) ?>
                                </p>
                            <?php endif; ?>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">
                                Adresse e-mail de contact
                            </label>

                            <input
                                type="email"
                                name="email"
                                value="<?= h($etablissement['email']) ?>"
                                class="w-full px-3 py-2.5 border rounded-lg text-sm focus:outline-none <?= classeChamp($erreurs['email']) ?>"
                            >

                            <?php if ($erreurs['email']): ?>
                                <p class="text-red-600 text-xs mt-1">
                                    <?= h($erreurs['email']) ?>
                                </p>
                            <?php endif; ?>
                        </div>

                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">
                            Téléphone
                        </label>

                        <input
                            type="text"
                            name="telephone"
                            value="<?= h($etablissement['telephone']) ?>"
                            class="w-full px-3 py-2.5 border rounded-lg text-sm focus:outline-none <?= classeChamp($erreurs['telephone']) ?>"
                        >

                        <?php if ($erreurs['telephone']): ?>
                            <p class="text-red-600 text-xs mt-1">
                                <?= h($erreurs['telephone']) ?>
                            </p>
                        <?php endif; ?>
                    </div>

                    <div class="pt-2">
                        <button
                            type="submit"
                            class="bg-red-600 text-white px-5 py-2.5 rounded-lg text-sm font-medium hover:bg-red-700 transition shadow-sm"
                        >
                            Enregistrer les modifications
                        </button>
                    </div>

                </form>
            </div>

            <!-- 2. ANNÉE ACADÉMIQUE -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 space-y-6">

                <div>
                    <h3 class="font-bold text-sm text-gray-800">
                        Année académique
                    </h3>
                    <p class="text-xs text-gray-400">
                        Définir la période de l'année scolaire active
                    </p>
                </div>

                <form action="parametres.php" method="POST" class="space-y-4" novalidate>

                    <input type="hidden" name="action" value="modifier_annee">

                    <div class="grid grid-cols-3 gap-4">

                        <div>
                            <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">
                                Année en cours
                            </label>

                            <input
                                type="text"
                                name="annee_cours"
                                value="<?= h($etablissement['annee_cours']) ?>"
                                placeholder="2026-2027"
                                class="w-full px-3 py-2.5 border rounded-lg text-sm focus:outline-none <?= classeChamp($erreurs['annee_cours']) ?>"
                            >

                            <?php if ($erreurs['annee_cours']): ?>
                                <p class="text-red-600 text-xs mt-1">
                                    <?= h($erreurs['annee_cours']) ?>
                                </p>
                            <?php endif; ?>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">
                                Date de rentrée
                            </label>

                            <input
                                type="date"
                                name="date_rentree"
                                value="<?= h($etablissement['date_rentree']) ?>"
                                class="w-full px-3 py-2.5 border rounded-lg text-sm focus:outline-none bg-white <?= classeChamp($erreurs['date_rentree']) ?>"
                            >

                            <?php if ($erreurs['date_rentree']): ?>
                                <p class="text-red-600 text-xs mt-1">
                                    <?= h($erreurs['date_rentree']) ?>
                                </p>
                            <?php endif; ?>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">
                                Date de clôture
                            </label>

                            <input
                                type="date"
                                name="date_cloture"
                                value="<?= h($etablissement['date_cloture']) ?>"
                                class="w-full px-3 py-2.5 border rounded-lg text-sm focus:outline-none bg-white <?= classeChamp($erreurs['date_cloture']) ?>"
                            >

                            <?php if ($erreurs['date_cloture']): ?>
                                <p class="text-red-600 text-xs mt-1">
                                    <?= h($erreurs['date_cloture']) ?>
                                </p>
                            <?php endif; ?>
                        </div>

                    </div>

                    <div class="pt-2">
                        <button
                            type="submit"
                            class="bg-red-600 text-white px-5 py-2.5 rounded-lg text-sm font-medium hover:bg-red-700 transition shadow-sm"
                        >
                            Enregistrer
                        </button>
                    </div>

                </form>
            </div>

            <!-- 3. SÉCURITÉ DU COMPTE -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 space-y-6">

                <div>
                    <h3 class="font-bold text-sm text-gray-800">
                        Sécurité du compte
                    </h3>
                    <p class="text-xs text-gray-400">
                        Mettre à jour votre mot de passe administrateur / secrétaire
                    </p>
                </div>

                <form action="parametres.php" method="POST" class="space-y-4" novalidate>

                    <input type="hidden" name="action" value="modifier_securite">

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">
                            Mot de passe actuel
                        </label>

                        <input
                            type="password"
                            name="password_actuel"
                            placeholder="Saisissez votre mot de passe actuel"
                            class="w-full px-3 py-2.5 border rounded-lg text-sm focus:outline-none <?= classeChamp($erreurs['password_actuel']) ?>"
                        >

                        <?php if ($erreurs['password_actuel']): ?>
                            <p class="text-red-600 text-xs mt-1">
                                <?= h($erreurs['password_actuel']) ?>
                            </p>
                        <?php endif; ?>
                    </div>

                    <div class="grid grid-cols-2 gap-4">

                        <div>
                            <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">
                                Nouveau mot de passe
                            </label>

                            <input
                                type="password"
                                name="nouveau_password"
                                placeholder="8 caractères minimum"
                                class="w-full px-3 py-2.5 border rounded-lg text-sm focus:outline-none <?= classeChamp($erreurs['nouveau_password']) ?>"
                            >

                            <?php if ($erreurs['nouveau_password']): ?>
                                <p class="text-red-600 text-xs mt-1">
                                    <?= h($erreurs['nouveau_password']) ?>
                                </p>
                            <?php endif; ?>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">
                                Confirmation
                            </label>

                            <input
                                type="password"
                                name="confirmer_password"
                                placeholder="Confirmez le mot de passe"
                                class="w-full px-3 py-2.5 border rounded-lg text-sm focus:outline-none <?= classeChamp($erreurs['confirmer_password']) ?>"
                            >

                            <?php if ($erreurs['confirmer_password']): ?>
                                <p class="text-red-600 text-xs mt-1">
                                    <?= h($erreurs['confirmer_password']) ?>
                                </p>
                            <?php endif; ?>
                        </div>

                    </div>

                    <div class="pt-2">
                        <button
                            type="submit"
                            class="bg-red-600 text-white px-5 py-2.5 rounded-lg text-sm font-medium hover:bg-red-700 transition shadow-sm flex items-center space-x-2"
                        >
                            <i class="fa-solid fa-key text-xs"></i>
                            <span>Mettre à jour</span>
                        </button>
                    </div>

                </form>
            </div>

        </div>
    </main>

</body>
</html>
