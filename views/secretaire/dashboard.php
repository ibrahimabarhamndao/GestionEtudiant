<?php
$pageActuelle = 'dashboard';
require_once '../../config/db.php';

// Récupération des statistiques globales
$totalFilieres = $pdo->query("SELECT COUNT(*) FROM filieres")->fetchColumn();
$totalNiveaux = $pdo->query("SELECT COUNT(*) FROM niveaux")->fetchColumn();
$totalClasses = $pdo->query("SELECT COUNT(*) FROM classes")->fetchColumn();
$totalEtudiants = $pdo->query("SELECT COUNT(*) FROM etudiants")->fetchColumn();

// Répartition par sexe
$totalGarcons = $pdo->query("SELECT COUNT(*) FROM etudiants WHERE sexe = 'Masculin'")->fetchColumn();
$totalFilles = $pdo->query("SELECT COUNT(*) FROM etudiants WHERE sexe = 'Féminin'")->fetchColumn();

// Derniers étudiants inscrits
$derniersEtudiants = $pdo->query("SELECT etudiants.*, classes.code_classe FROM etudiants LEFT JOIN classes ON etudiants.classe_id = classes.id ORDER BY etudiants.id DESC LIMIT 5")->fetchAll();

// Effectifs par filière
$effectifsFilieres = $pdo->query("SELECT filieres.code_filiere, COUNT(etudiants.id) as total FROM filieres LEFT JOIN classes ON filieres.id = classes.filiere_id LEFT JOIN etudiants ON classes.id = etudiants.classe_id GROUP BY filieres.id")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Administration — Tableau de bord</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 flex h-screen overflow-hidden">

    <!-- Sidebar Unifiée -->
    <?php include '../../includes/sidebar_secretaire.php'; ?>

    <!-- Contenu Principal -->
    <main class="flex-1 flex flex-col h-screen overflow-hidden">
        
        <!-- Header Supérieur -->
        <header class="bg-white border-b border-gray-100 px-8 py-4 flex justify-between items-center">
            <div>
                <h2 class="text-xl font-bold text-gray-800">Tableau de bord</h2>
                <p class="text-xs text-gray-500">Vue d'ensemble de l'établissement</p>
            </div>
            <div class="flex items-center space-x-4">
                <div class="relative">
                    <span class="absolute -top-1 -right-1 bg-red-600 text-white text-[10px] w-4 h-4 rounded-full flex items-center justify-center font-bold">2</span>
                    <button class="p-2 text-gray-400 hover:text-gray-600 bg-gray-50 rounded-full">🔔</button>
                </div>
                <div class="flex items-center space-x-2 border-l pl-4 border-gray-200">
                    <div class="w-8 h-8 bg-red-600 text-white rounded-full flex items-center justify-center font-bold text-sm">S</div>
                    <div>
                        <p class="text-xs font-bold text-gray-800">Secrétaire</p>
                        <p class="text-[10px] text-gray-500">Secrétaire</p>
                    </div>
                </div>
            </div>
        </header>

        <!-- Corps de la page -->
        <div class="flex-1 p-8 overflow-y-auto space-y-6">
            
            <!-- Cartes de Statistiques du Haut -->
            <div class="grid grid-cols-4 gap-6">
                <div class="bg-white p-6 rounded-xl border border-gray-100 shadow-sm">
                    <div class="flex items-center space-x-3 mb-2 text-gray-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                        <span class="text-xs font-semibold uppercase tracking-wider text-gray-500">Filières</span>
                    </div>
                    <h3 class="text-3xl font-bold text-gray-800"><?= $totalFilieres ?></h3>
                </div>

                <div class="bg-white p-6 rounded-xl border border-gray-100 shadow-sm">
                    <div class="flex items-center space-x-3 mb-2 text-gray-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                        <span class="text-xs font-semibold uppercase tracking-wider text-gray-500">Niveaux</span>
                    </div>
                    <h3 class="text-3xl font-bold text-gray-800"><?= $totalNiveaux ?></h3>
                </div>

                <div class="bg-white p-6 rounded-xl border border-gray-100 shadow-sm">
                    <div class="flex items-center space-x-3 mb-2 text-gray-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        <span class="text-xs font-semibold uppercase tracking-wider text-gray-500">Classes</span>
                    </div>
                    <h3 class="text-3xl font-bold text-gray-800"><?= $totalClasses ?></h3>
                </div>

                <div class="bg-white p-6 rounded-xl border border-gray-100 shadow-sm">
                    <div class="flex items-center space-x-3 mb-2 text-gray-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                        <span class="text-xs font-semibold uppercase tracking-wider text-gray-500">Étudiants</span>
                    </div>
                    <h3 class="text-3xl font-bold text-gray-800"><?= $totalEtudiants ?></h3>
                </div>
            </div>

            <!-- Cartes Secondaires (Garçons / Filles) -->
            <div class="grid grid-cols-2 gap-6 w-1/2">
                <div class="bg-white p-6 rounded-xl border border-gray-100 shadow-sm flex items-center space-x-4">
                    <div class="bg-gray-100 p-3 rounded-full text-gray-600">👤</div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Garçons</p>
                        <h3 class="text-2xl font-bold text-gray-800"><?= $totalGarcons ?></h3>
                    </div>
                </div>
                <div class="bg-white p-6 rounded-xl border border-gray-100 shadow-sm flex items-center space-x-4">
                    <div class="bg-gray-100 p-3 rounded-full text-gray-600">👤</div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Filles</p>
                        <h3 class="text-2xl font-bold text-gray-800"><?= $totalFilles ?></h3>
                    </div>
                </div>
            </div>

            <!-- Graphiques / Statistiques Visuelles -->
            <div class="grid grid-cols-3 gap-6">
                <!-- Effectifs par Filière -->
                <div class="col-span-2 bg-white p-6 rounded-xl border border-gray-100 shadow-sm">
                    <h3 class="font-bold text-gray-800 text-sm">Effectifs par Filière</h3>
                    <p class="text-xs text-gray-400 mb-6">Année académique 2024-2025</p>
                    
                    <div class="h-48 flex items-end justify-around border-b border-gray-200 pb-2">
                        <?php foreach($effectifsFilieres as $ef): ?>
                        <div class="flex flex-col items-center space-y-2">
                            <div class="w-12 bg-red-600 rounded-t-lg transition-all" style="height: <?= max(($ef['total'] * 35), 4) ?>px;"></div>
                            <span class="text-xs font-bold text-gray-600"><?= htmlspecialchars($ef['code_filiere']) ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Répartition par sexe -->
                <div class="bg-white p-6 rounded-xl border border-gray-100 shadow-sm flex flex-col justify-between">
                    <div>
                        <h3 class="font-bold text-gray-800 text-sm">Répartition par sexe</h3>
                        <p class="text-xs text-gray-400">Répartition globale</p>
                    </div>
                    <div class="flex flex-col items-center justify-center my-auto">
                        <div class="w-32 h-32 rounded-full border-8 border-red-600 border-l-slate-500 flex items-center justify-center shadow-inner">
                            <span class="text-xs font-bold text-gray-600"><?= $totalEtudiants ?> total</span>
                        </div>
                    </div>
                    <div class="flex justify-center space-x-6 text-xs">
                        <div class="flex items-center space-x-2"><span class="w-3 h-3 bg-red-600 rounded-full"></span><span class="text-gray-600">Féminin</span></div>
                        <div class="flex items-center space-x-2"><span class="w-3 h-3 bg-slate-500 rounded-full"></span><span class="text-gray-600">Masculin</span></div>
                    </div>
                </div>
            </div>

            <!-- Remplissage des Classes et Derniers Étudiants -->
            <div class="grid grid-cols-2 gap-6">
                <!-- Remplissage des Classes -->
                <div class="bg-white p-6 rounded-xl border border-gray-100 shadow-sm space-y-4">
                    <h3 class="font-bold text-gray-800 text-sm mb-4">Remplissage des Classes</h3>
                    <?php
                    $classesStats = $pdo->query("SELECT classes.code_classe, COUNT(etudiants.id) as inscrits, classes.capacite FROM classes LEFT JOIN etudiants ON classes.id = etudiants.classe_id GROUP BY classes.id")->fetchAll();
                    foreach($classesStats as $cls):
                        $pourcentage = $cls['capacite'] > 0 ? round(($cls['inscrits'] / $cls['capacite']) * 100) : 0;
                    ?>
                    <div>
                        <div class="flex justify-between text-xs font-semibold text-gray-700 mb-1">
                            <span><?= htmlspecialchars($cls['code_classe']) ?></span>
                            <span><?= $pourcentage ?>%</span>
                        </div>
                        <div class="w-full bg-gray-100 h-2 rounded-full overflow-hidden">
                            <div class="bg-red-600 h-full rounded-full" style="width: <?= $pourcentage ?>%;"></div>
                        </div>
                        <p class="text-[10px] text-gray-400 text-right mt-0.5"><?= $cls['inscrits'] ?>/<?= $cls['capacite'] ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Dernier étudiant -->
                <div class="bg-white p-6 rounded-xl border border-gray-100 shadow-sm">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-bold text-gray-800 text-sm">Dernier étudiant</h3>
                        <span class="bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full text-xs font-medium"><?= $totalEtudiants ?> étudiant</span>
                    </div>
                    <div class="space-y-3">
                        <?php foreach($derniersEtudiants as $de): ?>
                        <div class="flex items-center justify-between p-2 hover:bg-gray-50 rounded-lg transition">
                            <div class="flex items-center space-x-3">
                                <div class="w-9 h-9 bg-red-600 text-white rounded-full flex items-center justify-center font-bold text-xs">
                                    <?= strtoupper(substr($de['prenom'], 0, 1) . substr($de['nom'], 0, 1)) ?>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-gray-800"><?= htmlspecialchars($de['prenom'] . ' ' . $de['nom']) ?></p>
                                    <p class="text-[11px] text-gray-400"><?= htmlspecialchars($de['code_classe'] ?? 'Sans classe') ?></p>
                                </div>
                            </div>
                            <span class="bg-gray-100 text-gray-600 px-2.5 py-1 rounded text-xs font-medium"><?= htmlspecialchars($de['matricule']) ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

        </div>
    </main>

</body>
</html>