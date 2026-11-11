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
        <div class="flex-1 p-4 sm:p-6 md:p-8 overflow-y-auto">
            
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