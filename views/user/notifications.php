<?php
$pageActuelle = 'notifications';
$titrePage = 'Notifications';
$descriptionPage = 'Suivez vos alertes et informations importantes';
require_once '../../config/db.php';

// Identifiant de l'étudiant connecté
$etudiant_id = 1; 

$notifications = [];

try {
    // Récupération des notifications de l'étudiant
    $stmt = $pdo->prepare("
        SELECT * FROM notifications 
        WHERE etudiant_id = ? OR etudiant_id IS NULL 
        ORDER BY created_at DESC
    ");
    $stmt->execute([$etudiant_id]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Utilisation des données de secours en cas d'absence de table
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Espace Étudiant — Notifications</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 flex h-screen overflow-hidden font-sans">

    <!-- SIDEBAR ÉTUDIANT -->
 <?php include '../../includes/sidebar_etudiant.php'; ?>

    <!-- CONTENU PRINCIPAL -->
    <main class="flex-1 flex flex-col h-screen overflow-hidden">
        
        <!-- HEADER -->
        <header class="bg-white border-b border-gray-200 px-8 py-4 flex justify-between items-center shrink-0">
            <div>
                <h1 class="text-lg font-bold text-gray-900"><?= htmlspecialchars($titrePage) ?></h1>
                <p class="text-xs text-gray-400"><?= htmlspecialchars($descriptionPage) ?></p>
            </div>

            <div class="flex items-center space-x-6">
                <a href="notifications.php" class="relative text-gray-500 hover:text-gray-700 transition">
                    <i class="fa-regular fa-bell text-lg"></i>
                    <span class="absolute -top-1 -right-1 bg-red-600 text-white text-[10px] font-bold w-4 h-4 rounded-full flex items-center justify-center">2</span>
                </a>

                <div class="flex items-center space-x-3 cursor-pointer">
                    <div class="w-9 h-9 rounded-full bg-blue-600 text-white font-bold flex items-center justify-center text-xs shadow-sm">
                        MD
                    </div>
                    <div class="flex flex-col text-left">
                        <span class="text-sm font-bold text-gray-900 leading-tight">Mamadou</span>
                        <span class="text-[11px] text-gray-500 leading-tight">Diallo</span>
                    </div>
                    <i class="fa-solid fa-chevron-down text-xs text-red-600 ml-1"></i>
                </div>
            </div>
        </header>

        <!-- CORPS DE PAGE -->
        <div class="flex-1 p-8 overflow-y-auto space-y-6">
            
            <!-- Barre d'actions haut de liste -->
            <div class="flex justify-between items-center max-w-4xl">
                <span class="text-xs font-bold text-gray-500">2 non lu(s</span>
                <button class="text-xs font-semibold text-red-600 hover:text-red-700 transition">
                    Tout marquer comme l
                </button>
            </div>

            <!-- Liste des notifications -->
            <div class="space-y-4 max-w-4xl">
                <?php if (count($notifications) > 0): ?>
                    <?php foreach ($notifications as $notif): ?>
                        <div class="bg-white border border-gray-200/80 rounded-2xl p-4 shadow-2xs flex items-center justify-between hover:border-gray-300 transition">
                            <div class="flex items-center space-x-4">
                                <div class="w-10 h-10 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center text-base shrink-0">
                                    <i class="fa-solid fa-check"></i>
                                </div>
                                <div>
                                    <h3 class="font-bold text-sm text-gray-900"><?= htmlspecialchars($notif['titre']) ?></h3>
                                    <p class="text-xs text-gray-500 mt-0.5"><?= htmlspecialchars($notif['message']) ?></p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-4 text-gray-400">
                                <span class="text-xs font-medium">Il y a 4 jours</span>
                                <button class="hover:text-red-600 transition p-1">
                                    <i class="fa-regular fa-trash-can text-sm"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- NOTIFICATIONS DE DÉMONSTRATION FIDÈLES À LA MAQUETTE -->
                    
                    <!-- Notif 1 -->
                    <div class="bg-white border border-gray-200/80 rounded-2xl p-4 shadow-2xs flex items-center justify-between hover:border-gray-300 transition">
                        <div class="flex items-center space-x-4">
                            <div class="w-10 h-10 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm shrink-0">
                                <i class="fa-solid fa-check"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-sm text-gray-900">Inscription validé</h3>
                                <p class="text-xs text-gray-500 mt-0.5">Votre inscription pour l'année 2024-2025 a été confirmée</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-6 text-gray-400">
                            <span class="text-xs font-medium">Il y a 4 jour</span>
                            <button class="hover:text-red-600 transition p-1">
                                <i class="fa-regular fa-trash-can text-sm"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Notif 2 -->
                    <div class="bg-white border border-gray-200/80 rounded-2xl p-4 shadow-2xs flex items-center justify-between hover:border-gray-300 transition">
                        <div class="flex items-center space-x-4">
                            <div class="w-10 h-10 rounded-full bg-blue-50 text-blue-500 flex items-center justify-center text-sm shrink-0">
                                <i class="fa-regular fa-bell"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-sm text-gray-900">Nouveau document disponibl</h3>
                                <p class="text-xs text-gray-500 mt-0.5">Le syllabus du cours de Réseaux est maintenant disponible</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-6 text-gray-400">
                            <span class="text-xs font-medium">Il y a 2 jour</span>
                            <button class="hover:text-red-600 transition p-1">
                                <i class="fa-regular fa-trash-can text-sm"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Notif 3 -->
                    <div class="bg-white border border-gray-200/80 rounded-2xl p-4 shadow-2xs flex items-center justify-between hover:border-gray-300 transition">
                        <div class="flex items-center space-x-4">
                            <div class="w-10 h-10 rounded-full bg-amber-50 text-amber-600 flex items-center justify-center text-sm shrink-0">
                                <i class="fa-regular fa-bell"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-sm text-gray-900">Rappel : Paiement des frai</h3>
                                <p class="text-xs text-gray-500 mt-0.5">Le délai de paiement des frais de scolarité expire le 30/09/2024</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-6 text-gray-400">
                            <span class="text-xs font-medium">Il y a 2</span>
                            <button class="hover:text-red-600 transition p-1">
                                <i class="fa-regular fa-trash-can text-sm"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Notif 4 -->
                    <div class="bg-white border border-gray-200/80 rounded-2xl p-4 shadow-2xs flex items-center justify-between hover:border-gray-300 transition">
                        <div class="flex items-center space-x-4">
                            <div class="w-10 h-10 rounded-full bg-blue-50 text-blue-500 flex items-center justify-center text-sm shrink-0">
                                <i class="fa-regular fa-bell"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-sm text-gray-900">Mise à jour emploi du temp</h3>
                                <p class="text-xs text-gray-500 mt-0.5">L'emploi du temps de la semaine prochaine a été mis à jour</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-6 text-gray-400">
                            <span class="text-xs font-medium">Il y a 4 jour</span>
                            <button class="hover:text-red-600 transition p-1">
                                <i class="fa-regular fa-trash-can text-sm"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Notif 5 -->
                    <div class="bg-white border border-gray-200/80 rounded-2xl p-4 shadow-2xs flex items-center justify-between hover:border-gray-300 transition">
                        <div class="flex items-center space-x-4">
                            <div class="w-10 h-10 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm shrink-0">
                                <i class="fa-solid fa-check"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-sm text-gray-900">Résultats publié</h3>
                                <p class="text-xs text-gray-500 mt-0.5">Les résultats du devoir de Mathématiques sont disponibles</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-6 text-gray-400">
                            <span class="text-xs font-medium">Il y a 4 jour</span>
                            <button class="hover:text-red-600 transition p-1">
                                <i class="fa-regular fa-trash-can text-sm"></i>
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </main>

</body>
</html>