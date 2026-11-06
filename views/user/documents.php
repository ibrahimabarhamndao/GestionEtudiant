<?php
$pageActuelle = 'documents';
$titrePage = 'Documents';
$descriptionPage = 'Gestion des documents et ressources';
require_once '../../config/db.php';

// Identifiant de l'étudiant connecté
$etudiant_id = 1; 

$documents = [];

try {
    // Récupération des documents généraux et spécifiques à la classe de l'étudiant
    $stmt = $pdo->prepare("
        SELECT d.* 
        FROM documents d
        LEFT JOIN etudiants e ON e.classe_id = d.classe_id OR d.classe_id IS NULL
        WHERE e.id = ?
        ORDER BY d.created_at DESC
    ");
    $stmt->execute([$etudiant_id]);
    $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Utilisation des données de démo si la table n'existe pas encore
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Espace Étudiant — Documents</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 flex h-screen overflow-hidden font-sans">

    <!-- SIDEBAR ÉTUDIANT -->
    <aside class="w-64 bg-white border-r border-gray-200 flex flex-col justify-between h-screen shrink-0">
        <div>
            <div class="p-6 flex items-center space-x-3 border-b border-gray-100">
                <div class="w-10 h-10 rounded-2xl bg-red-600 flex items-center justify-center text-white font-bold shadow-md">
                    <i class="fa-solid fa-house text-lg"></i>
                </div>
                <div>
                    <h2 class="font-extrabold text-base text-gray-900 leading-tight">ESP 221</h2>
                    <p class="text-xs text-gray-400">Gestion Scolaire</p>
                </div>
            </div>

            <nav class="p-4 space-y-6">
                <div>
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider px-3">MON ESPACE</span>
                    <div class="mt-2 space-y-1">
                        <a href="dashboard.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-sm font-medium text-gray-500 hover:bg-gray-50 hover:text-gray-900 transition">
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
                        <a href="documents.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-sm font-semibold bg-red-50 text-red-600 transition">
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

        <div class="p-4 border-t border-gray-100">
            <a href="../../logout.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-sm font-medium text-red-600 hover:bg-red-50 transition">
                <i class="fa-solid fa-arrow-right-from-bracket"></i>
                <span>Se déconnecter</span>
            </a>
        </div>
    </aside>

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
        <div class="flex-1 p-8 overflow-y-auto">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 max-w-5xl">
                <?php if (count($documents) > 0): ?>
                    <?php foreach ($documents as $doc): 
                        $ext = strtoupper(pathinfo($doc['fichier'], PATHINFO_EXTENSION));
                        $isPdf = $ext === 'PDF';
                        $bgIcon = $isPdf ? 'bg-red-50 text-red-500' : 'bg-blue-50 text-blue-500';
                        $bgBadge = $isPdf ? 'bg-red-50 text-red-500' : 'bg-blue-50 text-blue-500';
                    ?>
                        <div class="bg-white border border-gray-200/80 rounded-2xl p-5 shadow-2xs flex items-center justify-between hover:border-gray-300 transition">
                            <div class="flex items-center space-x-4">
                                <div class="w-12 h-12 rounded-xl <?= $bgIcon ?> flex items-center justify-center text-lg">
                                    <i class="fa-regular fa-file-lines"></i>
                                </div>
                                <div class="space-y-1">
                                    <h3 class="font-bold text-sm text-gray-900"><?= htmlspecialchars($doc['titre']) ?></h3>
                                    <div class="flex items-center space-x-3 text-xs text-gray-400">
                                        <span class="<?= $bgBadge ?> px-2 py-0.5 rounded-md font-bold text-[10px] tracking-wide">
                                            <?= $ext ?>
                                        </span>
                                        <span><?= htmlspecialchars($doc['taille'] ?? '320 ko') ?></span>
                                        <span><?= date('d/m/Y', strtotime($doc['created_at'])) ?></span>
                                    </div>
                                </div>
                            </div>
                            <a href="../../uploads/documents/<?= urlencode($doc['fichier']) ?>" download class="text-gray-400 hover:text-gray-700 p-2 transition">
                                <i class="fa-solid fa-download text-base"></i>
                            </a>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- CARTES DE DÉMONSTRATION FIDÈLES À LA MAQUETTE -->
                    
                    <!-- Doc 1 : Syllabus Algorithmique -->
                    <div class="bg-white border border-gray-200/80 rounded-2xl p-5 shadow-2xs flex items-center justify-between hover:border-gray-300 transition">
                        <div class="flex items-center space-x-4">
                            <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-500 flex items-center justify-center text-lg">
                                <i class="fa-regular fa-file-lines"></i>
                            </div>
                            <div class="space-y-1">
                                <h3 class="font-bold text-sm text-gray-900">Syllabus Algorithmique</h3>
                                <div class="flex items-center space-x-3 text-xs text-gray-400">
                                    <span class="bg-blue-50 text-blue-500 px-2 py-0.5 rounded-md font-bold text-[10px] tracking-wide">DOCX</span>
                                    <span>320 ko</span>
                                    <span>05/09/2024</span>
                                </div>
                            </div>
                        </div>
                        <a href="#" class="text-gray-400 hover:text-gray-700 p-2 transition">
                            <i class="fa-solid fa-download text-base"></i>
                        </a>
                    </div>

                    <!-- Doc 2 : Règlement intérieur -->
                    <div class="bg-white border border-gray-200/80 rounded-2xl p-5 shadow-2xs flex items-center justify-between hover:border-gray-300 transition">
                        <div class="flex items-center space-x-4">
                            <div class="w-12 h-12 rounded-xl bg-red-50 text-red-500 flex items-center justify-center text-lg">
                                <i class="fa-regular fa-file-lines"></i>
                            </div>
                            <div class="space-y-1">
                                <h3 class="font-bold text-sm text-gray-900">Règlement intérieur 2024-2025</h3>
                                <div class="flex items-center space-x-3 text-xs text-gray-400">
                                    <span class="bg-red-50 text-red-500 px-2 py-0.5 rounded-md font-bold text-[10px] tracking-wide">PDF</span>
                                    <span>320 ko</span>
                                    <span>05/09/2024</span>
                                </div>
                            </div>
                        </div>
                        <a href="#" class="text-gray-400 hover:text-gray-700 p-2 transition">
                            <i class="fa-solid fa-download text-base"></i>
                        </a>
                    </div>

                    <!-- Doc 3 : Guide de l'étudiant -->
                    <div class="bg-white border border-gray-200/80 rounded-2xl p-5 shadow-2xs flex items-center justify-between hover:border-gray-300 transition">
                        <div class="flex items-center space-x-4">
                            <div class="w-12 h-12 rounded-xl bg-red-50 text-red-500 flex items-center justify-center text-lg">
                                <i class="fa-regular fa-file-lines"></i>
                            </div>
                            <div class="space-y-1">
                                <h3 class="font-bold text-sm text-gray-900">Guide de l'étudiant</h3>
                                <div class="flex items-center space-x-3 text-xs text-gray-400">
                                    <span class="bg-red-50 text-red-500 px-2 py-0.5 rounded-md font-bold text-[10px] tracking-wide">PDF</span>
                                    <span>320 ko</span>
                                    <span>05/09/2024</span>
                                </div>
                            </div>
                        </div>
                        <a href="#" class="text-gray-400 hover:text-gray-700 p-2 transition">
                            <i class="fa-solid fa-download text-base"></i>
                        </a>
                    </div>

                    <!-- Doc 4 : Formulaire attestation -->
                    <div class="bg-white border border-gray-200/80 rounded-2xl p-5 shadow-2xs flex items-center justify-between hover:border-gray-300 transition">
                        <div class="flex items-center space-x-4">
                            <div class="w-12 h-12 rounded-xl bg-red-50 text-red-500 flex items-center justify-center text-lg">
                                <i class="fa-regular fa-file-lines"></i>
                            </div>
                            <div class="space-y-1">
                                <h3 class="font-bold text-sm text-gray-900">Formulaire attestation de scolarité</h3>
                                <div class="flex items-center space-x-3 text-xs text-gray-400">
                                    <span class="bg-red-50 text-red-500 px-2 py-0.5 rounded-md font-bold text-[10px] tracking-wide">PDF</span>
                                    <span>320 ko</span>
                                    <span>10/02/2025</span>
                                </div>
                            </div>
                        </div>
                        <a href="#" class="text-gray-400 hover:text-gray-700 p-2 transition">
                            <i class="fa-solid fa-download text-base"></i>
                        </a>
                    </div>

                    <!-- Doc 5 : Syllabus Base de données -->
                    <div class="bg-white border border-gray-200/80 rounded-2xl p-5 shadow-2xs flex items-center justify-between hover:border-gray-300 transition">
                        <div class="flex items-center space-x-4">
                            <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-500 flex items-center justify-center text-lg">
                                <i class="fa-regular fa-file-lines"></i>
                            </div>
                            <div class="space-y-1">
                                <h3 class="font-bold text-sm text-gray-900">Syllabus Base de données</h3>
                                <div class="flex items-center space-x-3 text-xs text-gray-400">
                                    <span class="bg-blue-50 text-blue-500 px-2 py-0.5 rounded-md font-bold text-[10px] tracking-wide">DOCX</span>
                                    <span>320 ko</span>
                                    <span>05/09/2024</span>
                                </div>
                            </div>
                        </div>
                        <a href="#" class="text-gray-400 hover:text-gray-700 p-2 transition">
                            <i class="fa-solid fa-download text-base"></i>
                        </a>
                    </div>

                    <!-- Doc 6 : Calendrier académique -->
                    <div class="bg-white border border-gray-200/80 rounded-2xl p-5 shadow-2xs flex items-center justify-between hover:border-gray-300 transition">
                        <div class="flex items-center space-x-4">
                            <div class="w-12 h-12 rounded-xl bg-red-50 text-red-500 flex items-center justify-center text-lg">
                                <i class="fa-regular fa-file-lines"></i>
                            </div>
                            <div class="space-y-1">
                                <h3 class="font-bold text-sm text-gray-900">Calendrier académique 2024-2025</h3>
                                <div class="flex items-center space-x-3 text-xs text-gray-400">
                                    <span class="bg-red-50 text-red-500 px-2 py-0.5 rounded-md font-bold text-[10px] tracking-wide">PDF</span>
                                    <span>320 ko</span>
                                    <span>10/02/2025</span>
                                </div>
                            </div>
                        </div>
                        <a href="#" class="text-gray-400 hover:text-gray-700 p-2 transition">
                            <i class="fa-solid fa-download text-base"></i>
                        </a>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </main>

</body>
</html>