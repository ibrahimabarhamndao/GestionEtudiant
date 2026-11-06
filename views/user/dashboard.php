<?php
$pageActuelle = 'dashboard';
$titrePage = 'Documents';
$descriptionPage = 'Gestion des documents et ressources';
require_once '../../config/db.php';

// Identifiant de l'étudiant connecté (Exemple: Mamadou Diallo)
$etudiant_id = 1; 

// Initialisation des variables
$etudiant = null;
$classe = null;
$camarades = [];

try {
    // 1. Récupération des informations de l'étudiant et de sa classe
    $stmt = $pdo->prepare("
        SELECT e.*, c.id as classe_id, c.code_classe, c.nom as nom_classe, c.capacite, f.nom as nom_filiere, n.nom as nom_niveau, f.responsable 
        FROM etudiants e
        LEFT JOIN classes c ON e.classe_id = c.id
        LEFT JOIN niveaux n ON c.niveau_id = n.id
        LEFT JOIN filieres f ON n.filiere_id = f.id
        WHERE e.id = ?
    ");
    $stmt->execute([$etudiant_id]);
    $etudiant = $stmt->fetch(PDO::FETCH_ASSOC);

    // 2. Récupération des camarades de la même classe
    if ($etudiant && !empty($etudiant['classe_id'])) {
        $stmtCamarades = $pdo->prepare("
            SELECT id, nom, prenom, sexe, telephone 
            FROM etudiants 
            WHERE classe_id = ? AND id != ? 
            ORDER BY nom ASC
        ");
        $stmtCamarades->execute([$etudiant['classe_id'], $etudiant_id]);
        $camarades = $stmtCamarades->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    // En cas d'erreur de base de données, l'affichage utilisera les valeurs de secours (démo)
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Espace Étudiant — Ma Classe</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 flex h-screen overflow-hidden font-sans">

    <!-- SIDEBAR ÉTUDIANT -->
    <aside class="w-64 bg-white border-r border-gray-200 flex flex-col justify-between h-screen shrink-0">
        <div>
            <!-- Logo -->
            <div class="p-6 flex items-center space-x-3 border-b border-gray-100">
                <div class="w-10 h-10 rounded-2xl bg-red-600 flex items-center justify-center text-white font-bold shadow-md">
                    <i class="fa-solid fa-house text-lg"></i>
                </div>
                <div>
                    <h2 class="font-extrabold text-base text-gray-900 leading-tight">ESP 221</h2>
                    <p class="text-xs text-gray-400">Gestion Scolaire</p>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="p-4 space-y-6">
                <div>
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider px-3">MON ESPACE</span>
                    <div class="mt-2 space-y-1">
                        <a href="dashboard.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-sm font-semibold bg-red-50 text-red-600 transition">
                            <i class="fa-solid fa-border-all text-base"></i>
                            <span>Tableau de bord</span>
                        </a>
                        <a href="notes.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-sm font-medium text-gray-500 hover:bg-gray-50 hover:text-gray-900 transition">
                            <i class="fa-regular fa-square-check text-base"></i>
                            <span>Mes notes</span>
                        </a>
                    </div>
                </div>

                <div>
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider px-3">RESSOURCES</span>
                    <div class="mt-2 space-y-1">
                        <a href="emploi_du_temps.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-sm font-medium text-gray-500 hover:bg-gray-50 hover:text-gray-900 transition">
                            <i class="fa-regular fa-calendar text-base"></i>
                            <span>Emploi du Temps</span>
                        </a>
                        <a href="documents.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-sm font-medium text-gray-500 hover:bg-gray-50 hover:text-gray-900 transition">
                            <i class="fa-regular fa-file-lines text-base"></i>
                            <span>Documents</span>
                        </a>
                        <a href="notifications.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-sm font-medium text-gray-500 hover:bg-gray-50 hover:text-gray-900 transition">
                            <i class="fa-regular fa-bell text-base"></i>
                            <span>Notifications</span>
                        </a>
                    </div>
                </div>
            </nav>
        </div>

        <!-- Déconnexion -->
        <div class="p-4 border-t border-gray-100">
            <a href="../../logout.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-sm font-medium text-red-600 hover:bg-red-50 transition">
                <i class="fa-solid fa-arrow-right-from-bracket"></i>
                <span>Se déconnecter</span>
            </a>
        </div>
    </aside>

    <!-- CONTENU PRINCIPAL -->
    <main class="flex-1 flex flex-col h-screen overflow-hidden">
        
        <!-- HEADER ÉTUDIANT -->
        <header class="bg-white border-b border-gray-200 px-8 py-4 flex justify-between items-center shrink-0">
            <div>
                <h1 class="text-lg font-bold text-gray-900"><?= htmlspecialchars($titrePage) ?></h1>
                <p class="text-xs text-gray-400"><?= htmlspecialchars($descriptionPage) ?></p>
            </div>

            <div class="flex items-center space-x-6">
                <!-- Notifications avec Badge -->
                <a href="notifications.php" class="relative text-gray-500 hover:text-gray-700 transition">
                    <i class="fa-regular fa-bell text-lg"></i>
                    <span class="absolute -top-1 -right-1 bg-red-600 text-white text-[10px] font-bold w-4 h-4 rounded-full flex items-center justify-center">2</span>
                </a>

                <!-- Profil Étudiant (Bleu) -->
                <div class="flex items-center space-x-3 cursor-pointer">
                    <div class="w-9 h-9 rounded-full bg-blue-600 text-white font-bold flex items-center justify-center text-xs shadow-sm">
                        <?= isset($etudiant['prenom']) ? strtoupper(substr($etudiant['prenom'], 0, 1) . substr($etudiant['nom'], 0, 1)) : 'MD' ?>
                    </div>
                    <div class="flex flex-col text-left">
                        <span class="text-sm font-bold text-gray-900 leading-tight">
                            <?= htmlspecialchars(($etudiant['prenom'] ?? 'Mamadou')) ?>
                        </span>
                        <span class="text-[11px] text-gray-500 leading-tight">
                            <?= htmlspecialchars(($etudiant['nom'] ?? 'Diallo')) ?>
                        </span>
                    </div>
                    <i class="fa-solid fa-chevron-down text-xs text-red-600 ml-1"></i>
                </div>
            </div>
        </header>

        <!-- CORPS DE PAGE -->
        <div class="flex-1 p-8 overflow-y-auto space-y-6">
            
            <!-- Carte 1 : Informations de la classe -->
            <div class="bg-white rounded-2xl border border-gray-200/80 p-6 shadow-2xs relative">
                <div class="flex justify-between items-start mb-6">
                    <div>
                        <h2 class="text-xl font-bold text-gray-900">
                            <?= htmlspecialchars($etudiant['nom_classe'] ?? 'Informatique L1 Groupe A') ?>
                        </h2>
                        <p class="text-sm text-gray-400 mt-0.5">
                            <?= htmlspecialchars(($etudiant['nom_filiere'] ?? 'Informatique') . ' — ' . ($etudiant['nom_niveau'] ?? 'Licence 1ère année')) ?>
                        </p>
                    </div>
                    <span class="bg-red-50 text-red-600 font-bold px-3 py-1 rounded-lg text-xs tracking-wide">
                        <?= htmlspecialchars($etudiant['code_classe'] ?? 'INFO-L1-A') ?>
                    </span>
                </div>

                <div class="grid grid-cols-2 gap-8 pt-2">
                    <div>
                        <span class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider">CAPACITÉ</span>
                        <span class="text-sm font-bold text-gray-900 mt-1 block">
                            <?= htmlspecialchars($etudiant['capacite'] ?? '35') ?> places
                        </span>
                    </div>
                    <div>
                        <span class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider">RESPONSABLE</span>
                        <span class="text-sm font-bold text-gray-900 mt-1 block">
                            <?= htmlspecialchars($etudiant['responsable'] ?? 'Dr. Amadou Diallo') ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Carte 2 : Liste des Camarades de classe -->
            <div class="bg-white rounded-2xl border border-gray-200/80 p-6 shadow-2xs space-y-6">
                <div class="flex justify-between items-center border-b border-gray-50 pb-4">
                    <div class="flex items-center space-x-2">
                        <span class="w-1 h-4 bg-red-600 rounded-full"></span>
                        <h3 class="font-bold text-base text-gray-900">Camarades de classe</h3>
                    </div>
                    <span class="text-xs text-gray-400 font-medium">
                        <?= count($camarades) > 0 ? count($camarades) : 2 ?> camarade(s)
                    </span>
                </div>

                <!-- Liste des cartes camarades -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php if (count($camarades) > 0): ?>
                        <?php foreach ($camarades as $c): 
                            $initiales = strtoupper(substr($c['prenom'], 0, 1) . substr($c['nom'], 0, 1));
                            $genreCode = strtolower(trim($c['sexe'])) === 'féminin' || strtolower(trim($c['sexe'])) === 'f' ? 'F' : 'M';
                            $bgAvatar = $genreCode === 'F' ? 'bg-emerald-600' : 'bg-cyan-600';
                        ?>
                            <div class="flex items-center justify-between p-3.5 border border-gray-100 rounded-xl hover:border-gray-200 transition">
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 rounded-full <?= $bgAvatar ?> text-white font-bold text-xs flex items-center justify-center shadow-xs">
                                        <?= $initiales ?>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-sm text-gray-900"><?= htmlspecialchars($c['prenom'] . ' ' . $c['nom']) ?></h4>
                                        <p class="text-xs text-gray-400"><?= htmlspecialchars($c['telephone'] ?? '+221 77 000 00 00') ?></p>
                                    </div>
                                </div>
                                <span class="w-6 h-6 rounded-full bg-gray-100 text-gray-400 text-[10px] font-bold flex items-center justify-center">
                                    <?= $genreCode ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <!-- Camarades de démo conformes à la maquette -->
                        <div class="flex items-center justify-between p-3.5 border border-gray-100 rounded-xl">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 rounded-full bg-emerald-600 text-white font-bold text-xs flex items-center justify-center shadow-xs">
                                    AS
                                </div>
                                <div>
                                    <h4 class="font-bold text-sm text-gray-900">Aminata Sall</h4>
                                    <p class="text-xs text-gray-400">+221 78 234 56 78</p>
                                </div>
                            </div>
                            <span class="w-6 h-6 rounded-full bg-blue-50 text-blue-500 text-[10px] font-bold flex items-center justify-center">
                                F
                            </span>
                        </div>

                        <div class="flex items-center justify-between p-3.5 border border-gray-100 rounded-xl">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 rounded-full bg-cyan-600 text-white font-bold text-xs flex items-center justify-center shadow-xs">
                                    SM
                                </div>
                                <div>
                                    <h4 class="font-bold text-sm text-gray-900">Serigne Mbaye</h4>
                                    <p class="text-xs text-gray-400">+221 77 567 89 01</p>
                                </div>
                            </div>
                            <span class="w-6 h-6 rounded-full bg-gray-100 text-gray-400 text-[10px] font-bold flex items-center justify-center">
                                M
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </main>

</body>
</html>