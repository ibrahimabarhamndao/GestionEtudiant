<?php
$pageActuelle = 'parametres'; 
$titrePage = 'Paramètres'; 
$descriptionPage = 'Configuration de l\'application'; 
require_once '../../config/db.php';

$message = "";
$succes = "";

// Traitement des formulaires de paramètres
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'modifier_infos') {
            // Logique de mise à jour des infos de l'établissement
            $succes = "Informations de l'établissement mises à jour avec succès.";
        } elseif ($_POST['action'] === 'modifier_annee') {
            // Logique de mise à jour de l'année académique
            $succes = "Année académique enregistrée avec succès.";
        } elseif ($_POST['action'] === 'modifier_securite') {
            // Logique de mise à jour du mot de passe
            $succes = "Sécurité du compte mise à jour avec succès.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Administration — Paramètres</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Masque la barre de défilement tout en gardant le scroll actif */
        * {
            scrollbar-width: none;      /* Firefox */
            -ms-overflow-style: none;   /* Ancien Edge / IE */
        }
        *::-webkit-scrollbar {
            display: none;              /* Chrome, Edge, Safari */
        }
    </style>
</head>
<body class="bg-gray-50 flex h-screen overflow-hidden font-sans">

    <!-- Sidebar Unifiée -->
    <?php include '../../includes/sidebar_secretaire.php'; ?>

    <!-- Contenu Principal -->
    <main class="flex-1 flex flex-col h-screen overflow-hidden">
        
        <!-- Header Supérieur Dynamique -->
        <?php include '../../includes/header.php'; ?>

        <!-- Corps de la page -->
        <div class="flex-1 p-8 overflow-y-auto space-y-6 max-w-5xl">
            
            <?php if (!empty($succes)): ?>
                <div class="p-4 bg-emerald-50 border-l-4 border-emerald-500 text-emerald-700 text-sm rounded-lg shadow-sm">
                    <?= htmlspecialchars($succes) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($message)): ?>
                <div class="p-4 bg-red-50 border-l-4 border-red-500 text-red-700 text-sm rounded-lg shadow-sm">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <!-- 1. INFORMATIONS DE L'ÉTABLISSEMENT -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 space-y-6">
                <div>
                    <h3 class="font-bold text-sm text-gray-800">Informations de l'établissement</h3>
                    <p class="text-xs text-gray-400">Modifier les informations générales de l'école</p>
                </div>

                <form action="parametres.php" method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="modifier_infos">
                    
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Nom de l'établissement</label>
                        <input type="text" name="nom_etablissement" value="École Supérieure Professionnelle 221" required class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Adresse</label>
                            <input type="text" name="adresse" value="Dakar, Sénégal" required class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Adresse e-mail de contact</label>
                            <input type="email" name="email" value="contact@esp221.sn" required class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Téléphone</label>
                        <input type="text" name="telephone" value="+221 33 000 00 00" required class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600">
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="bg-red-600 text-white px-5 py-2.5 rounded-lg text-sm font-medium hover:bg-red-700 transition shadow-sm">
                            Enregistrer les modifications
                        </button>
                    </div>
                </form>
            </div>

            <!-- 2. ANNÉE ACADÉMIQUE -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 space-y-6">
                <div>
                    <h3 class="font-bold text-sm text-gray-800">Année académique</h3>
                    <p class="text-xs text-gray-400">Définir la période de l'année scolaire active</p>
                </div>

                <form action="parametres.php" method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="modifier_annee">
                    
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Année en cours</label>
                            <input type="text" name="annee_cours" value="2024-2025" required class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Date de rentrée</label>
                            <input type="date" name="date_rentree" value="2024-10-01" required class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600 bg-white">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Date de clôture</label>
                            <input type="date" name="date_cloture" value="2025-07-31" required class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600 bg-white">
                        </div>
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="bg-red-600 text-white px-5 py-2.5 rounded-lg text-sm font-medium hover:bg-red-700 transition shadow-sm">
                            Économiser
                        </button>
                    </div>
                </form>
            </div>

            <!-- 3. SÉCURITÉ DU COMPTE -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 space-y-6">
                <div>
                    <h3 class="font-bold text-sm text-gray-800">Sécurité du compte</h3>
                    <p class="text-xs text-gray-400">Mettre à jour votre mot de passe administrateur / secrétaire</p>
                </div>

                <form action="parametres.php" method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="modifier_securite">
                    
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Mot de passe actuel</label>
                        <input type="password" name="password_actuel" required placeholder="••••••••" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Nouveau mot de passe</label>
                            <input type="password" name="nouveau_password" required placeholder="••••••••" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Confirmatur</label>
                            <input type="password" name="confirmer_password" required placeholder="••••••••" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600">
                        </div>
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="bg-red-600 text-white px-5 py-2.5 rounded-lg text-sm font-medium hover:bg-red-700 transition shadow-sm flex items-center space-x-2">
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