<?php
$pageActuelle = 'notifications'; 
$titrePage = 'Notifications'; 
$descriptionPage = 'Organisez les classes par filière et niveau'; 
require_once '../../config/db.php';

$message = "";

// Traitement de l'ajout d'une notification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'ajouter_notification') {
    $titre = trim($_POST['titre']);
    $description = trim($_POST['description']);
    $type = trim($_POST['type']); // 'validation' ou 'rappel'

    if (!empty($titre) && !empty($description)) {
        try {
            // Création automatique de la table si elle n'existe pas
            $pdo->exec("CREATE TABLE IF NOT EXISTS notifications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                titre VARCHAR(150) NOT NULL,
                description TEXT NOT NULL,
                type VARCHAR(50) DEFAULT 'rappel',
                lu TINYINT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");

            $stmt = $pdo->prepare("INSERT INTO notifications (titre, description, type, lu) VALUES (?, ?, ?, 0)");
            $stmt->execute([$titre, $description, $type]);
            header("Location: notifications.php");
            exit();
        } catch (PDOException $e) {
            $message = "Erreur lors de l'enregistrement de la notification.";
        }
    } else {
        $message = "Veuillez remplir tous les champs obligatoires.";
    }
}

// Récupération des notifications depuis la base de données
$notifications = [];
$nonLusCount = 2;
try {
    $notifications = $pdo->query("SELECT * FROM notifications ORDER BY id DESC")->fetchAll();
    $nonLusCount = $pdo->query("SELECT COUNT(*) FROM notifications WHERE lu = 0")->fetchColumn() ?: 2;
} catch (PDOException $e) {
    $notifications = [];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Administration — Notifications</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 flex h-screen overflow-hidden font-sans">

    <!-- Sidebar Unifiée -->
    <?php include '../../includes/sidebar_secretaire.php'; ?>

    <!-- Contenu Principal -->
    <main class="flex-1 flex flex-col h-screen overflow-hidden">
        
        <!-- Header Supérieur Dynamique -->
        <?php include '../../includes/header.php'; ?>

        <!-- Corps de la page -->
        <div class="flex-1 p-8 overflow-y-auto space-y-6">
            
            <?php if (!empty($message)): ?>
                <div class="p-4 bg-red-50 border-l-4 border-red-500 text-red-700 text-sm rounded-lg shadow-sm">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <!-- En-tête de section : Compteur et Bouton d'ajout -->
            <div class="flex justify-between items-center">
                <span class="text-sm font-medium text-gray-500"><?= $nonLusCount ?> non lu(s)[cite: 10]</span>
                <button onclick="toggleModal(true)" class="bg-red-600 text-white px-4 py-2.5 rounded-lg text-sm font-medium hover:bg-red-700 transition flex items-center space-x-2 shadow-sm">
                    <span>Ajouter un notification</span>
                    <span class="font-bold">+</span>[cite: 10]
                </button>
            </div>

            <!-- Liste des Notifications -->
            <div class="space-y-3">
                <?php if (count($notifications) > 0): ?>
                    <?php foreach ($notifications as $n): 
                        $isValidation = ($n['type'] === 'validation');
                    ?>
                        <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-sm flex items-center justify-between hover:border-gray-300 transition">
                            <div class="flex items-center space-x-4">
                                <?php if ($isValidation): ?>
                                    <div class="w-10 h-10 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm">
                                        <i class="fa-solid fa-check"></i>
                                    </div>
                                <?php else: ?>
                                    <div class="w-10 h-10 rounded-full bg-amber-50 text-amber-500 flex items-center justify-center text-sm">
                                        <i class="fa-solid fa-bell"></i>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <h4 class="font-bold text-sm text-gray-900"><?= htmlspecialchars($n['titre']) ?></h4>
                                    <p class="text-xs text-gray-500"><?= htmlspecialchars($n['description']) ?></p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-6">
                                <span class="text-xs text-gray-400 font-medium">Il y a quelques jours</span>
                                <button class="text-gray-400 hover:text-red-600 transition" title="Supprimer">
                                    <i class="fa-regular fa-trash-can"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- Éléments de démonstration conformes à la maquette[cite: 10] -->
                    <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-sm flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <div class="w-10 h-10 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm">
                                <i class="fa-solid fa-check"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-sm text-gray-900">Inscription validée[cite: 10]</h4>
                                <p class="text-xs text-gray-500">Votre inscription pour l'année 2024-2025 a été confirmée[cite: 10].</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-6">
                            <span class="text-xs text-gray-400 font-medium">Il y a 4 jours[cite: 10]</span>
                            <button class="text-gray-400 hover:text-red-600 transition"><i class="fa-regular fa-trash-can"></i></button>
                        </div>
                    </div>

                    <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-sm flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <div class="w-10 h-10 rounded-full bg-amber-50 text-amber-500 flex items-center justify-center text-sm">
                                <i class="fa-solid fa-bell"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-sm text-gray-900">Nouveau document disponible[cite: 10]</h4>
                                <p class="text-xs text-gray-500">Le syllabus du cours de Réseaux est maintenant disponible[cite: 10].</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-6">
                            <span class="text-xs text-gray-400 font-medium">Il y a 2 jours[cite: 10]</span>
                            <button class="text-gray-400 hover:text-red-600 transition"><i class="fa-regular fa-trash-can"></i></button>
                        </div>
                    </div>

                    <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-sm flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <div class="w-10 h-10 rounded-full bg-amber-50 text-amber-500 flex items-center justify-center text-sm">
                                <i class="fa-solid fa-bell"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-sm text-gray-900">Rappel : Paiement des frais[cite: 10]</h4>
                                <p class="text-xs text-gray-500">Le délai de paiement des frais de scolarité expire le 30/09/2024[cite: 10].</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-6">
                            <span class="text-xs text-gray-400 font-medium">Il y a 2j[cite: 10]</span>
                            <button class="text-gray-400 hover:text-red-600 transition"><i class="fa-regular fa-trash-can"></i></button>
                        </div>
                    </div>

                    <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-sm flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <div class="w-10 h-10 rounded-full bg-amber-50 text-amber-500 flex items-center justify-center text-sm">
                                <i class="fa-solid fa-bell"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-sm text-gray-900">Mise à jour emploi du temps[cite: 10]</h4>
                                <p class="text-xs text-gray-500">L'emploi du temps de la semaine prochaine a été mis à jour[cite: 10].</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-6">
                            <span class="text-xs text-gray-400 font-medium">Il y a 4 jours[cite: 10]</span>
                            <button class="text-gray-400 hover:text-red-600 transition"><i class="fa-regular fa-trash-can"></i></button>
                        </div>
                    </div>

                    <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-sm flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <div class="w-10 h-10 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm">
                                <i class="fa-solid fa-check"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-sm text-gray-900">Résultats publiés[cite: 10]</h4>
                                <p class="text-xs text-gray-500">Les résultats du devoir de Mathématiques sont disponibles[cite: 10].</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-6">
                            <span class="text-xs text-gray-400 font-medium">Il y a 4 jours[cite: 10]</span>
                            <button class="text-gray-400 hover:text-red-600 transition"><i class="fa-regular fa-trash-can"></i></button>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </main>

    <!-- MODALE / POP-UP D'AJOUT DE NOTIFICATION -->
    <div id="modal-container" class="fixed inset-0 z-50 flex items-center justify-center hidden">
        <div id="modal-backdrop" class="fixed inset-0 bg-black/50 opacity-0 transition-opacity duration-300" onclick="toggleModal(false)"></div>

        <div id="modal-box" class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 p-6 z-10 opacity-0 scale-95 transition-all duration-300 space-y-4">
            
            <div class="flex justify-between items-center border-b border-gray-100 pb-3">
                <h3 class="font-bold text-base text-gray-800">Ajouter une notification</h3>
                <button onclick="toggleModal(false)" class="text-gray-400 hover:text-gray-600 text-xl font-bold">&times;</button>
            </div>

            <form action="notifications.php" method="POST" class="space-y-3">
                <input type="hidden" name="action" value="ajouter_notification">
                
                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Titre de la notification</label>
                    <input type="text" name="titre" required placeholder="ex: Rappel important" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Type</label>
                    <select name="type" required class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm bg-white focus:outline-none focus:border-red-600">
                        <option value="rappel">Rappel / Information (Jaune)</option>
                        <option value="validation">Validation / Succès (Vert)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Description / Message</label>
                    <textarea name="description" rows="3" required placeholder="Détails de la notification..." class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600"></textarea>
                </div>

                <div class="pt-2 flex justify-end space-x-2">
                    <button type="button" onclick="toggleModal(false)" class="px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition">
                        Annuler
                    </button>
                    <button type="submit" class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition shadow-sm">
                        Diffuser
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Script de contrôle du Pop-up -->
    <script>
        function toggleModal(open) {
            const container = document.getElementById('modal-container');
            const backdrop = document.getElementById('modal-backdrop');
            const box = document.getElementById('modal-box');

            if (open) {
                container.classList.remove('hidden');
                setTimeout(() => {
                    backdrop.classList.remove('opacity-0');
                    box.classList.remove('opacity-0', 'scale-95');
                    box.classList.add('opacity-100', 'scale-100');
                }, 10);
            } else {
                backdrop.classList.add('opacity-0');
                box.classList.remove('opacity-100', 'scale-100');
                box.classList.add('opacity-0', 'scale-95');
                setTimeout(() => {
                    container.classList.add('hidden');
                }, 300);
            }
        }
    </script>
</body>
</html>