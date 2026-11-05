<?php
$pageActuelle = 'parametres';
$titrePage = 'Paramètres';
$descriptionPage = 'Configuration générale de la plateforme et du compte';
require_once '../../config/db.php';

$message = "";

// Traitement de la mise à jour des paramètres
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'modifier_parametres') {
    $nomEcole = trim($_POST['nom_ecole']);
    $anneeAcademique = trim($_POST['annee_academique']);
    $emailContact = trim($_POST['email_contact']);

    if (!empty($nomEcole) && !empty($anneeAcademique)) {
        try {
            // Création automatique de la table parametres si elle n'existe pas
            $pdo->exec("CREATE TABLE IF NOT EXISTS parametres (
                id INT AUTO_INCREMENT PRIMARY KEY,
                cle VARCHAR(100) UNIQUE NOT NULL,
                valeur TEXT NOT NULL
            )");

            // Enregistrement des valeurs par clé
            $stmt = $pdo->prepare("INSERT INTO parametres (cle, valeur) VALUES (?, ?) ON DUPLICATE KEY UPDATE valeur = ?");
            $stmt->execute(['nom_ecole', $nomEcole, $nomEcole]);
            $stmt->execute(['annee_academique', $anneeAcademique, $anneeAcademique]);
            $stmt->execute(['email_contact', $emailContact, $emailContact]);

            $message = "Paramètres mis à jour avec succès !";
        } catch (PDOException $e) {
            $message = "Erreur lors de la mise à jour des paramètres.";
        }
    } else {
        $message = "Veuillez remplir les champs obligatoires.";
    }
}

// Récupération des paramètres existants
$params = [];
try {
    $res = $pdo->query("SELECT * FROM parametres")->fetchAll();
    foreach ($res as $row) {
        $params[$row['cle']] = $row['valeur'];
    }
} catch (PDOException $e) {
    // Tables potentiellement non créées encore
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Administration — Paramètres</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 flex h-screen overflow-hidden">

    <!-- Sidebar Unifiée -->
    <?php include '../../includes/sidebar_secretaire.php'; ?>

    <!-- Contenu Principal -->
    <main class="flex-1 flex flex-col h-screen overflow-hidden">
        
        <!-- Header Supérieur Dynamique -->
        <?php include '../../includes/header.php'; ?>

        <!-- Corps de la page -->
        <div class="flex-1 p-8 overflow-y-auto space-y-6 max-w-4xl">
            
            <?php if (!empty($message)): ?>
                <div class="p-4 bg-emerald-50 border-l-4 border-emerald-500 text-emerald-700 text-sm rounded">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <!-- Formulaire de configuration -->
            <form action="parametres.php" method="POST" class="space-y-6">
                <input type="hidden" name="action" value="modifier_parametres">

                <!-- Section Informations de l'établissement -->
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 space-y-4">
                    <h3 class="font-bold text-base text-gray-800 border-b pb-3">Informations de l'établissement</h3>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Nom de l'école</label>
                            <input type="text" name="nom_ecole" value="<?= htmlspecialchars($params['nom_ecole'] ?? 'ESP 221') ?>" required class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Année académique</label>
                            <input type="text" name="annee_academique" value="<?= htmlspecialchars($params['annee_academique'] ?? '2024-2025') ?>" required class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Email de contact / Support</label>
                        <input type="email" name="email_contact" value="<?= htmlspecialchars($params['email_contact'] ?? 'contact@esp221.sn') ?>" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600">
                    </div>
                </div>

                <!-- Section Sécurité / Compte -->
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 space-y-4">
                    <h3 class="font-bold text-base text-gray-800 border-b pb-3">Sécurité du compte Secrétaire</h3>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Nouveau mot de passe</label>
                            <input type="password" name="nouveau_mdp" placeholder="Laisser vide pour ne pas changer" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Confirmer le mot de passe</label>
                            <input type="password" name="confirmation_mdp" placeholder="Confirmer le mot de passe" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600">
                        </div>
                    </div>
                </div>

                <!-- Bouton de sauvegarde -->
                <div class="flex justify-end">
                    <button type="submit" class="bg-red-600 text-white px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-red-700 transition shadow-sm">
                        Enregistrer les modifications
                    </button>
                </div>
            </form>

        </div>
    </main>

</body>
</html>