<?php
$pageActuelle = 'dashboard'; 
$titrePage = 'Tableau de bord'; 
$descriptionPage = 'Vue d\'ensemble de l\'établissement'; 
require_once '../../config/db.php';

// --- RÉCUPÉRATION DES STATISTIQUES DEPUIS LA BD ---
$totalFilieres = 0;
$totalNiveaux = 0;
$totalClasses = 0;
$totalEtudiants = 0;
$totalGarcons = 0;
$totalFilles = 0;
$bdDisponible = true;

try {
    $totalFilieres = $pdo->query("SELECT COUNT(*) FROM filieres")->fetchColumn() ?: 0;
    $totalNiveaux = $pdo->query("SELECT COUNT(*) FROM niveaux")->fetchColumn() ?: 0;
    $totalClasses = $pdo->query("SELECT COUNT(*) FROM classes")->fetchColumn() ?: 0;
    $totalEtudiants = $pdo->query("SELECT COUNT(*) FROM etudiants")->fetchColumn() ?: 0;

    // Le formulaire d'ajout enregistre « Masculin » / « Féminin » : on accepte aussi les anciennes valeurs
    $totalGarcons = $pdo->query("SELECT COUNT(*) FROM etudiants WHERE sexe IN ('Masculin', 'M', 'Garcon', 'Garçon')")->fetchColumn() ?: 0;
    $totalFilles = $pdo->query("SELECT COUNT(*) FROM etudiants WHERE sexe IN ('Féminin', 'Feminin', 'F', 'Fille')")->fetchColumn() ?: 0;
} catch (PDOException $e) {
    $bdDisponible = false;
}

// Effectifs par filière
$effectifsFilieres = [];
try {
    $stmt = $pdo->query("SELECT f.nom, f.code_filiere, COUNT(e.id) as count 
                         FROM filieres f 
                         LEFT JOIN niveaux n ON n.filiere_id = f.id 
                         LEFT JOIN classes c ON c.niveau_id = n.id 
                         LEFT JOIN etudiants e ON e.classe_id = c.id 
                         GROUP BY f.id, f.nom, f.code_filiere");
    $effectifsFilieres = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $effectifsFilieres = [];
}

// Remplissage des classes
$classesRemplissage = [];
try {
    $stmt = $pdo->query("SELECT c.code_classe, c.nom, c.capacite, COUNT(e.id) as inscrits 
                         FROM classes c 
                         LEFT JOIN etudiants e ON e.classe_id = c.id 
                         GROUP BY c.id, c.code_classe, c.nom, c.capacite");
    $classesRemplissage = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $classesRemplissage = [];
}

// Derniers étudiants inscrits
$derniersEtudiants = [];
try {
    $stmt = $pdo->query("SELECT e.*, c.code_classe FROM etudiants e LEFT JOIN classes c ON e.classe_id = c.id ORDER BY e.id DESC LIMIT 5");
    $derniersEtudiants = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $derniersEtudiants = [];
}

// --- DONNÉES DE DÉMONSTRATION conformes à la maquette (si la base est vide ou indisponible) ---
if (!$bdDisponible || ($totalFilieres == 0 && $totalClasses == 0 && $totalEtudiants == 0)) {
    $totalFilieres = 6; $totalNiveaux = 5; $totalClasses = 5; $totalEtudiants = 6;
    $totalGarcons = 3;  $totalFilles = 3;
    $effectifsFilieres = [
        ['code_filiere' => 'INFO',  'count' => 4],
        ['code_filiere' => 'GEST',  'count' => 1],
        ['code_filiere' => 'MARK',  'count' => 1],
        ['code_filiere' => 'DROIT', 'count' => 0],
    ];
    $classesRemplissage = [
        ['code_classe' => 'INFO-L1-A', 'capacite' => 35, 'inscrits' => 3],
        ['code_classe' => 'INFO-L2-A', 'capacite' => 30, 'inscrits' => 1],
        ['code_classe' => 'GEST-L1-A', 'capacite' => 40, 'inscrits' => 1],
        ['code_classe' => 'MARK-L1-A', 'capacite' => 30, 'inscrits' => 1],
    ];
    $derniersEtudiants = [
        ['prenom' => 'Rokhaya', 'nom' => 'Cissé',  'code_classe' => 'MARK-L1-A', 'matricule' => 'ESP221-2024-006'],
        ['prenom' => 'Omar',    'nom' => 'Diallo', 'code_classe' => 'MARK-L1-A', 'matricule' => 'ESP221-2024-005'],
        ['prenom' => 'Omar',    'nom' => 'Ndao',   'code_classe' => 'MARK-L1-A', 'matricule' => 'ESP221-2024-004'],
        ['prenom' => 'Amadou',  'nom' => 'Fall',   'code_classe' => 'MARK-L1-A', 'matricule' => 'ESP221-2024-003'],
        ['prenom' => 'Modou',   'nom' => 'Dia',    'code_classe' => 'MARK-L1-A', 'matricule' => 'ESP221-2024-002'],
    ];
}

// --- Axe du graphique à barres (graduations dynamiques) ---
$maxEffectif = count($effectifsFilieres) > 0 ? max(array_column($effectifsFilieres, 'count')) : 0;
$maxEffectif = max(1, (int)$maxEffectif);
$pas = (int)ceil($maxEffectif / 4);
$axeMax = (int)(ceil($maxEffectif / $pas) * $pas);
$graduations = range(0, $axeMax, $pas);

// --- Répartition par sexe (donut) ---
$totalSexe = $totalGarcons + $totalFilles;
$pctFilles = $totalSexe > 0 ? round(($totalFilles / $totalSexe) * 100, 2) : 50;

// Couleurs des avatars
$couleursAvatar = ['bg-purple-600', 'bg-amber-600', 'bg-cyan-600', 'bg-blue-600', 'bg-red-600'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Administration — Tableau de bord</title>
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
            
            <!-- 1. Cartes statistiques -->
            <div class="grid grid-cols-4 gap-6">
                <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm space-y-3">
                    <div class="flex items-center space-x-2.5 text-slate-500">
                        <i class="fa-solid fa-book-open text-sm"></i>
                        <span class="text-base text-gray-800">Filières</span>
                    </div>
                    <p class="text-xl font-bold text-gray-900"><?= $totalFilieres ?></p>
                </div>

                <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm space-y-3">
                    <div class="flex items-center space-x-2.5 text-slate-500">
                        <i class="fa-solid fa-chart-line text-sm"></i>
                        <span class="text-base text-gray-800">Niveaux</span>
                    </div>
                    <p class="text-xl font-bold text-gray-900"><?= $totalNiveaux ?></p>
                </div>

                <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm space-y-3">
                    <div class="flex items-center space-x-2.5 text-slate-500">
                        <i class="fa-solid fa-users text-sm"></i>
                        <span class="text-base text-gray-800">Classes</span>
                    </div>
                    <p class="text-xl font-bold text-gray-900"><?= $totalClasses ?></p>
                </div>

                <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm space-y-3">
                    <div class="flex items-center space-x-2.5 text-slate-500">
                        <i class="fa-regular fa-user text-sm"></i>
                        <span class="text-base text-gray-800">Étudiants</span>
                    </div>
                    <p class="text-xl font-bold text-gray-900"><?= $totalEtudiants ?></p>
                </div>

                <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm space-y-3">
                    <div class="flex items-center space-x-2.5 text-slate-500">
                        <i class="fa-solid fa-user text-sm"></i>
                        <span class="text-base text-gray-800">Garçons</span>
                    </div>
                    <p class="text-xl font-bold text-gray-900"><?= $totalGarcons ?></p>
                </div>

                <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm space-y-3">
                    <div class="flex items-center space-x-2.5 text-slate-500">
                        <i class="fa-solid fa-user text-sm"></i>
                        <span class="text-base text-gray-800">Filles</span>
                    </div>
                    <p class="text-xl font-bold text-gray-900"><?= $totalFilles ?></p>
                </div>
            </div>

            <!-- 2. Graphiques -->
            <div class="grid grid-cols-3 gap-6">
                <!-- Effectifs par filière -->
                <div class="col-span-2 bg-white p-6 rounded-xl border border-gray-200 shadow-sm space-y-6">
                    <div>
                        <h3 class="font-bold text-sm text-gray-900">Effectifs par Filière</h3>
                        <p class="text-xs text-slate-500">Année académique 2024-2025</p>
                    </div>

                    <div>
                        <div class="flex">
                            <!-- Graduations de l'axe vertical -->
                            <div class="relative w-8 h-44">
                                <?php foreach ($graduations as $g): ?>
                                    <span class="absolute right-2 text-xs text-slate-500 translate-y-1/2" style="bottom: <?= ($g / $axeMax) * 100 ?>%;"><?= $g ?></span>
                                <?php endforeach; ?>
                            </div>

                            <!-- Zone des barres -->
                            <div class="relative flex-1 h-44">
                                <?php foreach ($graduations as $g): ?>
                                    <div class="absolute inset-x-0 border-t border-gray-200" style="bottom: <?= ($g / $axeMax) * 100 ?>%;"></div>
                                <?php endforeach; ?>

                                <div class="relative z-10 flex h-full">
                                    <?php foreach ($effectifsFilieres as $ef): ?>
                                        <div class="flex-1 flex flex-col items-center justify-end h-full" title="<?= (int)$ef['count'] ?> étudiant(s)">
                                            <div class="w-7 bg-red-600" style="height: <?= ($ef['count'] / $axeMax) * 100 ?>%;"></div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Libellés des filières -->
                        <div class="flex pl-8 pt-2">
                            <?php foreach ($effectifsFilieres as $ef): ?>
                                <span class="flex-1 text-center text-sm text-slate-500"><?= htmlspecialchars($ef['code_filiere'] ?? $ef['nom']) ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Répartition par sexe -->
                <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm flex flex-col justify-between">
                    <div>
                        <h3 class="font-bold text-sm text-gray-900">Répartition par sexe</h3>
                        <p class="text-xs text-slate-500">Répartition globale</p>
                    </div>

                    <div class="flex items-center justify-center py-4">
                        <div class="w-36 h-36 rounded-full flex items-center justify-center"
                             style="background: conic-gradient(from 270deg, #dc2626 0 <?= $pctFilles ?>%, #64748b <?= $pctFilles ?>% 100%);">
                            <div class="w-24 h-24 rounded-full bg-white"></div>
                        </div>
                    </div>

                    <div class="flex justify-center space-x-6 text-[11px] text-slate-500">
                        <div class="flex items-center space-x-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-red-600"></span>
                            <span>Féminin</span>
                        </div>
                        <div class="flex items-center space-x-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-slate-500"></span>
                            <span>Masculin</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. Remplissage des classes & Derniers étudiants -->
            <div class="grid grid-cols-2 gap-6">
                <!-- Remplissage des classes -->
                <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm space-y-4">
                    <h3 class="font-bold text-sm text-gray-900">Remplissage des Classes</h3>

                    <div class="space-y-4">
                        <?php foreach ($classesRemplissage as $classe):
                            $cap = $classe['capacite'] > 0 ? $classe['capacite'] : 30;
                            $inscr = (int)$classe['inscrits'];
                            $pct = round(($inscr / $cap) * 100);
                        ?>
                            <div class="space-y-1.5">
                                <div class="flex justify-between text-[11px]">
                                    <span class="font-bold text-slate-600"><?= htmlspecialchars($classe['code_classe'] ?? $classe['nom']) ?></span>
                                    <span class="font-bold text-slate-600"><?= $pct ?>%</span>
                                </div>
                                <div class="w-full bg-slate-200 h-1.5 rounded-full overflow-hidden">
                                    <div class="bg-red-600 h-full rounded-full" style="width: <?= min(100, $pct) ?>%;"></div>
                                </div>
                                <p class="text-right text-[11px] text-slate-500"><?= $inscr ?>/<?= $cap ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Derniers étudiants -->
                <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm space-y-4">
                    <div class="flex justify-between items-center">
                        <h3 class="font-bold text-sm text-gray-900">Derniers étudiants</h3>
                        <span class="text-[11px] bg-gray-100 text-slate-600 px-3 py-1 rounded-full"><?= $totalEtudiants ?> étudiant(s)</span>
                    </div>

                    <div class="space-y-3">
                        <?php foreach ($derniersEtudiants as $i => $etudiant):
                            $nomComplet = trim(($etudiant['prenom'] ?? '') . ' ' . ($etudiant['nom'] ?? 'Étudiant'));
                            $initiales = mb_strtoupper(mb_substr($etudiant['prenom'] ?? 'E', 0, 1) . mb_substr($etudiant['nom'] ?? 'T', 0, 1));
                        ?>
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <div class="w-8 h-8 rounded-full <?= $couleursAvatar[$i % count($couleursAvatar)] ?> text-white font-bold text-[11px] flex items-center justify-center"><?= htmlspecialchars($initiales) ?></div>
                                    <div>
                                        <h4 class="font-bold text-xs text-gray-900"><?= htmlspecialchars($nomComplet) ?></h4>
                                        <p class="text-[11px] text-slate-500"><?= htmlspecialchars($etudiant['code_classe'] ?? 'Non assigné') ?></p>
                                    </div>
                                </div>
                                <span class="text-[11px] text-slate-500 bg-gray-100 px-3 py-1 rounded-full"><?= htmlspecialchars($etudiant['matricule'] ?? '') ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

        </div>
    </main>

</body>
</html>