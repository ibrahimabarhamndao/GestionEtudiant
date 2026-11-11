<?php
$pageActuelle = 'emploi_du_temps';
$titrePage = 'Emploi du temps';
$descriptionPage = 'Consultez le planning hebdomadaire de votre classe';
require_once '../../config/db.php';

// Identifiant de l'étudiant connecté (Exemple: Mamadou Diallo)
$etudiant_id = 1; 

$emploiDuTemps = [];
$classeInfo = ['nom_classe' => 'Informatique L1 Groupe A', 'nom_niveau' => 'Licence 1ère année', 'semaine' => 'Semaine du 23 au 27 septembre 2024'];

try {
    // 1. Récupérer la classe de l'étudiant
    $stmt = $pdo->prepare("
        SELECT c.id as classe_id, c.code_classe, c.nom as nom_classe, n.nom as nom_niveau 
        FROM etudiants e
        JOIN classes c ON e.classe_id = c.id
        JOIN niveaux n ON c.niveau_id = n.id
        WHERE e.id = ?
    ");
    $stmt->execute([$etudiant_id]);
    $resClasse = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($resClasse) {
        $classeInfo['nom_classe'] = $resClasse['nom_classe'];
        $classeInfo['nom_niveau'] = $resClasse['nom_niveau'];

        // 2. Récupérer l'emploi du temps de cette classe
        $stmtEdt = $pdo->prepare("
            SELECT edt.*, m.nom as nom_matiere, p.nom as nom_prof 
            FROM emploi_du_temps edt
            JOIN matieres m ON edt.matiere_id = m.id
            LEFT JOIN professeurs p ON edt.professeur_id = p.id
            WHERE edt.classe_id = ?
            ORDER FIELD(edt.jour, 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'), edt.heure_debut ASC
        ");
        // Note: Si FIELD() pose un souci selon le SGBD, on triera en PHP ou via une requête standard
        $stmtEdt->execute([$resClasse['classe_id']]);
        $emploiDuTemps = $stmtEdt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    // Utilisation des données de secours en cas d'absence de table ou de données
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Espace Étudiant — Emploi du Temps</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 flex h-screen overflow-hidden font-sans">

    <!-- SIDEBAR ÉTUDIANT -->
   <?php include '../../includes/sidebar_etudiant.php'; ?>

    <!-- CONTENU PRINCIPAL -->
    <main class="flex-1 min-w-0 flex flex-col h-screen overflow-hidden">
        
        <!-- HEADER -->
        <header class="bg-white border-b border-gray-200 px-4 sm:px-6 md:px-8 py-3 md:py-4 flex justify-between items-center gap-3 shrink-0">
            <div class="flex items-center gap-3 min-w-0">
                <button type="button" data-sb-toggle aria-label="Ouvrir le menu" aria-expanded="false"
                        class="md:hidden shrink-0 w-10 h-10 flex items-center justify-center rounded-lg text-gray-600 hover:bg-gray-100 transition">
                    <i class="fa-solid fa-bars text-lg"></i>
                </button>
                <div class="min-w-0">
                    <h1 class="text-base md:text-lg font-bold text-gray-900 truncate"><?= htmlspecialchars($titrePage) ?></h1>
                    <p class="text-xs text-gray-400 truncate"><?= htmlspecialchars($descriptionPage) ?></p>
                </div>
            </div>

            <div class="flex items-center gap-4 md:gap-6 shrink-0">
                <a href="notifications.php" class="relative text-gray-500 hover:text-gray-700 transition">
                    <i class="fa-regular fa-bell text-lg"></i>
                    <span class="absolute -top-1 -right-1 bg-red-600 text-white text-[10px] font-bold w-4 h-4 rounded-full flex items-center justify-center">2</span>
                </a>

                <div class="flex items-center space-x-3 cursor-pointer">
                    <div class="w-9 h-9 rounded-full bg-blue-600 text-white font-bold flex items-center justify-center text-xs shadow-sm">
                        MD
                    </div>
                    <div class="hidden sm:flex flex-col text-left">
                        <span class="text-sm font-bold text-gray-900 leading-tight">Mamadou</span>
                        <span class="text-[11px] text-gray-500 leading-tight">Diallo</span>
                    </div>
                    <i class="hidden sm:inline fa-solid fa-chevron-down text-xs text-red-600 ml-1"></i>
                </div>
            </div>
        </header>

        <!-- CORPS DE PAGE -->
        <div class="flex-1 p-4 sm:p-6 md:p-8 overflow-y-auto space-y-6">
            
            <!-- En-tête du planning -->
            <div class="bg-white rounded-2xl border border-gray-200/80 p-6 shadow-2xs space-y-3">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-2">
                    <div class="flex items-center space-x-3">
                        <h2 class="text-lg font-bold text-gray-900"><?= htmlspecialchars($classeInfo['nom_classe']) ?></h2>
                        <span class="text-xs text-gray-400 font-medium"><?= htmlspecialchars($classeInfo['nom_niveau']) ?></span>
                    </div>
                    <span class="text-xs font-semibold text-gray-500 bg-gray-100 px-3 py-1 rounded-lg">
                        <?= htmlspecialchars($classeInfo['semaine']) ?>
                    </span>
                </div>
                <p class="text-xs text-gray-400">
                    Seuls les cours de votre classe sont affichés. Pour toute modification, contactez le secrétariat.
                </p>
            </div>

            <!-- Grille de l'emploi du temps (5 jours) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4">
                
                <!-- LUNDI -->
                <div class="bg-white rounded-2xl border border-gray-200/80 p-4 shadow-2xs flex flex-col space-y-3">
                    <div class="border-b border-gray-100 pb-2.5">
                        <span class="text-xs font-extrabold text-gray-900 tracking-wider">LUNDI</span>
                    </div>
                    <div class="space-y-3">
                        <div class="bg-red-50/60 border border-red-100 rounded-xl p-3.5 space-y-1.5">
                            <span class="text-[11px] font-bold text-red-600 block">08h00</span>
                            <h4 class="font-bold text-sm text-gray-900">Algorithmique</h4>
                            <p class="text-xs text-gray-500">Salle A101</p>
                            <p class="text-xs text-gray-400 font-medium">Dr. Diallo</p>
                        </div>
                        <div class="bg-red-50/60 border border-red-100 rounded-xl p-3.5 space-y-1.5">
                            <span class="text-[11px] font-bold text-red-600 block">10h00</span>
                            <h4 class="font-bold text-sm text-gray-900">Base de données</h4>
                            <p class="text-xs text-gray-500">Salle B203</p>
                            <p class="text-xs text-gray-400 font-medium">Pr. Seck</p>
                        </div>
                        <div class="bg-red-50/60 border border-red-100 rounded-xl p-3.5 space-y-1.5">
                            <span class="text-[11px] font-bold text-red-600 block">14h00</span>
                            <h4 class="font-bold text-sm text-gray-900">Réseaux informatiques</h4>
                            <p class="text-xs text-gray-500">Labo Info</p>
                            <p class="text-xs text-gray-400 font-medium">Dr. Ndiaye</p>
                        </div>
                    </div>
                </div>

                <!-- MARDI -->
                <div class="bg-white rounded-2xl border border-gray-200/80 p-4 shadow-2xs flex flex-col space-y-3">
                    <div class="border-b border-gray-100 pb-2.5">
                        <span class="text-xs font-extrabold text-gray-900 tracking-wider">MARDI</span>
                    </div>
                    <div class="space-y-3">
                        <div class="bg-blue-50/60 border border-blue-100 rounded-xl p-3.5 space-y-1.5">
                            <span class="text-[11px] font-bold text-blue-600 block">08h00</span>
                            <h4 class="font-bold text-sm text-gray-900">Mathématiques</h4>
                            <p class="text-xs text-gray-500">Salle A102</p>
                            <p class="text-xs text-gray-400 font-medium">Pr. Ba</p>
                        </div>
                        <div class="bg-blue-50/60 border border-blue-100 rounded-xl p-3.5 space-y-1.5">
                            <span class="text-[11px] font-bold text-blue-600 block">10h00</span>
                            <h4 class="font-bold text-sm text-gray-900">Programmation Web</h4>
                            <p class="text-xs text-gray-500">Labo Info</p>
                            <p class="text-xs text-gray-400 font-medium">Dr. Diallo</p>
                        </div>
                    </div>
                </div>

                <!-- MERCREDI -->
                <div class="bg-white rounded-2xl border border-gray-200/80 p-4 shadow-2xs flex flex-col space-y-3">
                    <div class="border-b border-gray-100 pb-2.5">
                        <span class="text-xs font-extrabold text-gray-900 tracking-wider">MERCREDI</span>
                    </div>
                    <div class="space-y-3">
                        <div class="bg-emerald-50/60 border border-emerald-100 rounded-xl p-3.5 space-y-1.5">
                            <span class="text-[11px] font-bold text-emerald-600 block">08h00</span>
                            <h4 class="font-bold text-sm text-gray-900">Systèmes d'exploitation</h4>
                            <p class="text-xs text-gray-500">Salle C301</p>
                            <p class="text-xs text-gray-400 font-medium">Dr. Ndiaye</p>
                        </div>
                        <div class="bg-emerald-50/60 border border-emerald-100 rounded-xl p-3.5 space-y-1.5">
                            <span class="text-[11px] font-bold text-emerald-600 block">14h00</span>
                            <h4 class="font-bold text-sm text-gray-900">Anglais Technique</h4>
                            <p class="text-xs text-gray-500">Salle A103</p>
                            <p class="text-xs text-gray-400 font-medium">Pr. Fall</p>
                        </div>
                    </div>
                </div>

                <!-- JEUDI -->
                <div class="bg-white rounded-2xl border border-gray-200/80 p-4 shadow-2xs flex flex-col space-y-3">
                    <div class="border-b border-gray-100 pb-2.5">
                        <span class="text-xs font-extrabold text-gray-900 tracking-wider">JEUDI</span>
                    </div>
                    <div class="space-y-3">
                        <div class="bg-purple-50/60 border border-purple-100 rounded-xl p-3.5 space-y-1.5">
                            <span class="text-[11px] font-bold text-purple-600 block">08h00</span>
                            <h4 class="font-bold text-sm text-gray-900">Sécurité Informatique</h4>
                            <p class="text-xs text-gray-500">Salle B201</p>
                            <p class="text-xs text-gray-400 font-medium">Dr. Diallo</p>
                        </div>
                        <div class="bg-purple-50/60 border border-purple-100 rounded-xl p-3.5 space-y-1.5">
                            <span class="text-[11px] font-bold text-purple-600 block">10h00</span>
                            <h4 class="font-bold text-sm text-gray-900">Gestion de Projet</h4>
                            <p class="text-xs text-gray-500">Salle A101</p>
                            <p class="text-xs text-gray-400 font-medium">Pr. Seck</p>
                        </div>
                        <div class="bg-purple-50/60 border border-purple-100 rounded-xl p-3.5 space-y-1.5">
                            <span class="text-[11px] font-bold text-purple-600 block">14h00</span>
                            <h4 class="font-bold text-sm text-gray-900">TP Réseaux</h4>
                            <p class="text-xs text-gray-500">Labo Réseaux</p>
                            <p class="text-xs text-gray-400 font-medium">Dr. Ndiaye</p>
                        </div>
                    </div>
                </div>

                <!-- VENDREDI -->
                <div class="bg-white rounded-2xl border border-gray-200/80 p-4 shadow-2xs flex flex-col space-y-3">
                    <div class="border-b border-gray-100 pb-2.5">
                        <span class="text-xs font-extrabold text-gray-900 tracking-wider">VENDREDI</span>
                    </div>
                    <div class="space-y-3">
                        <div class="bg-amber-50/60 border border-amber-100 rounded-xl p-3.5 space-y-1.5">
                            <span class="text-[11px] font-bold text-amber-600 block">08h00</span>
                            <h4 class="font-bold text-sm text-gray-900">Architecture des SI</h4>
                            <p class="text-xs text-gray-500">Salle C302</p>
                            <p class="text-xs text-gray-400 font-medium">Pr. Ba</p>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </main>

</body>
</html>