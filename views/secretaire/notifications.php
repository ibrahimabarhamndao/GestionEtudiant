<?php
$pageActuelle = 'notifications';
$titrePage = 'Notification';
$descriptionPage = 'Organisez les classes par filière et niveau';
require_once '../../config/db.php';

$message = "";

// Traitement de l'ajout d'une notification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'ajouter_notification') {
    $titre = trim($_POST['titre']);
    $texte = trim($_POST['message']);
    $type = trim($_POST['type'] ?? 'info');

    if (!empty($titre) && !empty($texte)) {
        try {
            // Création automatique de la table si elle n'existe pas
            $pdo->exec("CREATE TABLE IF NOT EXISTS notifications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                titre VARCHAR(150) NOT NULL,
                message TEXT NOT NULL,
                type VARCHAR(50) DEFAULT 'info',
                lu TINYINT(1) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");

            $stmt = $pdo->prepare("INSERT INTO notifications (titre, message, type) VALUES (?, ?, ?)");
            $stmt->execute([$titre, $texte, $type]);
            header("Location: notifications.php");
            exit();
        } catch (PDOException $e) {
            $message = "Erreur lors de l'enregistrement de la notification.";
        }
    } else {
        $message = "Veuillez remplir tous les champs obligatoires.";
    }
}

// Récupération des notifications
$notifications = [];
try {
    $notifications = $pdo->query("SELECT * FROM notifications ORDER BY id DESC")->fetchAll();
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
</head>
<body class="bg-gray-50 flex h-screen overflow-hidden">

    <!-- Sidebar Unifiée -->
    <?php include '../../includes/sidebar_secretaire.php'; ?>

    <!-- Contenu Principal -->
    <main class="flex-1 flex flex-col h-screen overflow-hidden">
        
        <!-- Header Supérieur Dynamique -->
        <?php include '../../includes/header.php'; ?>

        <!-- Corps de la page -->
        <div class="flex-1 p-8 overflow-y-auto space-y-6">
            
            <!-- Compteur non lus et Bouton d'ajout -->
            <div class="flex justify-between items-center">
                <span class="text-sm text-gray-500 font-medium">2 non lu(s)</span>
                <button onclick="toggleDrawer(true)" class="bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-red-700 transition flex items-center space-x-1 shadow-sm">
                    <span>Ajouter un notification</span> <span class="font-bold">+</span>
                </button>
            </div>

            <?php if (!empty($message)): ?>
                <div class="p-4 bg-red-50 border-l-4 border-red-500 text-red-700 text-sm rounded">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <!-- Liste des cartes de notification -->
            <div class="space-y-4">
                <?php if (count($notifications) > 0): ?>
                    <?php foreach ($notifications as $notif): ?>
                    <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-xs flex items-center justify-between hover:shadow-sm transition">
                        <div class="flex items-center space-x-4">
                            <div class="w-10 h-10 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg font-bold">
                                ✓
                            </div>
                            <div>
                                <h4 class="text-sm font-bold text-gray-800"><?= htmlspecialchars($notif['titre']) ?></h4>
                                <p class="text-xs text-gray-500 mt-0.5"><?= htmlspecialchars($notif['message']) ?></p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-6">
                            <span class="text-xs text-gray-400">Récemment</span>
                            <button class="text-gray-400 hover:text-red-600 transition" title="Supprimer">🗑️</button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- Éléments de démonstration conformes à la maquette -->
                    <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-xs flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <div class="w-10 h-10 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg">
                                ✓
                            </div>
                            <div>
                                <h4 class="text-sm font-bold text-gray-800">Inscription validée</h4>
                                <p class="text-xs text-gray-500 mt-0.5">Votre inscription pour l'année 2024-2025 a été confirmée.</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-6">
                            <span class="text-xs text-gray-400">Il y a 4 jours</span>
                            <button class="text-gray-400 hover:text-red-600 transition" title="Supprimer">🗑️</button>
                        </div>
                    </div>

                    <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-xs flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <div class="w-10 h-10 rounded-full bg-blue-50 text-blue-500 flex items-center justify-center text-lg">
                                🔔
                            </div>
                            <div>
                                <h4 class="text-sm font-bold text-gray-800">Nouveau document disponible</h4>
                                <p class="text-xs text-gray-500 mt-0.5">Le syllabus du cours de Réseaux est maintenant disponible.</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-6">
                            <span class="text-xs text-gray-400">Il y a 2 jours</span>
                            <button class="text-gray-400 hover:text-red-600 transition" title="Supprimer">🗑️</button>
                        </div>
                    </div>

                    <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-xs flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <div class="w-10 h-10 rounded-full bg-amber-50 text-amber-500 flex items-center justify-center text-lg">
                                🔔
                            </div>
                            <div>
                                <h4 class="text-sm font-bold text-gray-800">Rappel : Paiement des frais</h4>
                                <p class="text-xs text-gray-500 mt-0.5">Le délai de paiement des frais de scolarité expire le 30/09/2024.</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-6">
                            <span class="text-xs text-gray-400">Il y a 2j</span>
                            <button class="text-gray-400 hover:text-red-600 transition" title="Supprimer">🗑️</button>
                        </div>
                    </div>

                    <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-xs flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <div class="w-10 h-10 rounded-full bg-blue-50 text-blue-500 flex items-center justify-center text-lg">
                                🔔
                            </div>
                            <div>
                                <h4 class="text-sm font-bold text-gray-800">Mise à jour emploi du temps</h4>
                                <p class="text-xs text-gray-500 mt-0.5">L'emploi du temps de la semaine prochaine a été mis à jour.</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-6">
                            <span class="text-xs text-gray-400">Il y a 4 jours</span>
                            <button class="text-gray-400 hover:text-red-600 transition" title="Supprimer">🗑️</button>
                        </div>
                    </div>

                    <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-xs flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <div class="w-10 h-10 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg">
                                ✓
                            </div>
                            <div>
                                <h4 class="text-sm font-bold text-gray-800">Résultats publiés</h4>
                                <p class="text-xs text-gray-500 mt-0.5">Les résultats du devoir de Mathématiques sont disponibles.</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-6">
                            <span class="text-xs text-gray-400">Il y a 4 jours</span>
                            <button class="text-gray-400 hover:text-red-600 transition" title="Supprimer">🗑️</button>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </main>

    <!-- DRAWER D'AJOUT DE NOTIFICATION -->
    <div id="drawer-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden transition-opacity opacity-0" onclick="toggleDrawer(false)"></div>
    
    <div id="drawer" class="fixed top-0 right-0 h-full w-96 bg-white shadow-2xl z-50 transform translate-x-full transition-transform duration-300 ease-in-out flex flex-col justify-between">
        <div>
            <div class="p-6 border-b border-gray-100 flex justify-between items-center">
                <h3 class="font-bold text-lg text-gray-800">Envoyer une notification</h3>
                <button onclick="toggleDrawer(false)" class="text-gray-400 hover:text-gray-600 text-xl font-bold">&times;</button>
            </div>

            <form action="notifications.php" method="POST" class="p-6 space-y-4">
                <input type="hidden" name="action" value="ajouter_notification">
                
                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Titre de l'alerte</label>
                    <input type="text" name="titre" required placeholder="ex: Maintenance système" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Message</label>
                    <textarea name="message" required rows="4" placeholder="Contenu de la notification..." class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600"></textarea>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Type</label>
                    <select name="type" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm bg-white focus:outline-none focus:border-red-600">
                        <option value="info">Information (Bleu)</option>
                        <option value="success">Succès / Validation (Vert)</option>
                        <option value="warning">Alerte / Rappel (Ambre)</option>
                    </select>
                </div>

                <div class="pt-4">
                    <button type="submit" class="w-full bg-red-600 text-white py-2.5 rounded-lg text-sm font-medium hover:bg-red-700 transition">
                        Diffuser la notification
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleDrawer(open) {
            const drawer = document.getElementById('drawer');
            const overlay = document.getElementById('drawer-overlay');
            if (open) {
                overlay.classList.remove('hidden');
                setTimeout(() => overlay.classList.remove('opacity-0'), 10);
                drawer.classList.remove('translate-x-full');
            } else {
                overlay.classList.add('opacity-0');
                drawer.classList.add('translate-x-full');
                setTimeout(() => overlay.classList.add('hidden'), 300);
            }
        }
    </script>
</body>
</html>