<?php
$pageActuelle = 'notes';
$titrePage = 'Mes notes';
$descriptionPage = 'Consultez vos résultats académiques et moyennes';
require_once '../../config/db.php';

// Identifiant de l'étudiant connecté (Exemple: Mamadou Diallo)
$etudiant_id = 1; 

$notes = [];
$stats = ['moyenne_generale' => 14.86, 'total_matieres' => 6, 'meilleure_note' => '17.5/20'];

try {
    // Récupération des notes de l'étudiant depuis la base de données
    $stmt = $pdo->prepare("
        SELECT n.*, mat.nom as nom_matiere, mat.coefficient, p.nom as nom_prof 
        FROM notes n
        JOIN matieres mat ON n.matiere_id = mat.id
        LEFT JOIN professeurs p ON mat.professeur_id = p.id
        WHERE n.etudiant_id = ?
    ");
    $stmt->execute([$etudiant_id]);
    $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculs dynamiques si des notes existent
    if (count($notes) > 0) {
        $totalPoints = 0;
        $totalCoeff = 0;
        $maxNote = 0;

        foreach ($notes as $note) {
            $totalPoints += ($note['valeur'] * $note['coefficient']);
            $totalCoeff += $note['coefficient'];
            if ($note['valeur'] > $maxNote) {
                $maxNote = $note['valeur'];
            }
        }

        if ($totalCoeff > 0) {
            $stats['moyenne_generale'] = round($totalPoints / $totalCoeff, 2);
        }
        $stats['total_matieres'] = count($notes);
        $stats['meilleure_note'] = $maxNote . '/20';
    }
} catch (PDOException $e) {
    // Utilisation des valeurs par défaut de la maquette en cas de table vide
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Espace Étudiant — Mes Notes</title>
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
                        <a href="notes.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-sm font-semibold bg-red-50 text-red-600 transition">
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
        <div class="flex-1 p-8 overflow-y-auto space-y-6">
            
            <!-- Statistiques / Cartes du Haut -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Carte 1 : Moyenne générale -->
                <div class="bg-red-600 rounded-2xl p-6 text-white shadow-sm flex flex-col justify-between">
                    <div class="text-3xl font-extrabold tracking-tight"><?= $stats['moyenne_generale'] ?></div>
                    <div class="text-xs font-medium text-red-100 uppercase tracking-wider mt-2">Moyenne / 20</div>
                </div>

                <!-- Carte 2 : Total matières -->
                <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-2xs flex flex-col justify-between">
                    <div class="text-3xl font-extrabold text-gray-900"><?= $stats['total_matieres'] ?></div>
                    <div class="text-xs font-medium text-gray-400 uppercase tracking-wider mt-2">Moyenne / 20</div>
                </div>

                <!-- Carte 3 : Meilleure note -->
                <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-2xs flex flex-col justify-between">
                    <div class="text-3xl font-extrabold text-emerald-600"><?= $stats['meilleure_note'] ?></div>
                    <div class="text-xs font-medium text-gray-400 uppercase tracking-wider mt-2">Meilleure note</div>
                </div>
            </div>

            <!-- Conteneur Tableau des Notes -->
            <div class="bg-white rounded-2xl border border-gray-200/80 p-6 shadow-2xs space-y-6">
                
                <div class="flex justify-end">
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-1 flex text-xs font-medium">
                        <button class="px-3 py-1.5 bg-red-50 text-red-600 rounded-md shadow-2xs flex items-center space-x-1.5">
                            <i class="fa-solid fa-table"></i>
                            <span>Tableau</span>
                        </button>
                        <button class="px-3 py-1.5 text-gray-500 hover:text-gray-800 rounded-md flex items-center space-x-1.5 transition">
                            <i class="fa-solid fa-border-all"></i>
                            <span>Cartes</span>
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="text-gray-400 text-[11px] uppercase tracking-wider border-b border-gray-100">
                                <th class="py-3 px-4 font-semibold">MATIÈRE</th>
                                <th class="py-3 px-4 font-semibold">ENSEIGNANT</th>
                                <th class="py-3 px-4 font-semibold">COEF.</th>
                                <th class="py-3 px-4 font-semibold">NOTE /20</th>
                                <th class="py-3 px-4 font-semibold">MENTION</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-xs text-gray-600">
                            <?php if (count($notes) > 0): ?>
                                <?php foreach ($notes as $n): 
                                    $val = $n['valeur'];
                                    // Attribution dynamique des mentions selon la note
                                    if ($val >= 16) { $mention = 'Très bien'; $badgeColor = 'bg-emerald-50 text-emerald-600'; }
                                    elseif ($val >= 14) { $mention = 'Bien'; $badgeColor = 'bg-blue-50 text-blue-600'; }
                                    elseif ($val >= 12) { $mention = 'Assez bien'; $badgeColor = 'bg-amber-50 text-amber-600'; }
                                    else { $mention = 'Passable'; $badgeColor = 'bg-gray-100 text-gray-500'; }
                                ?>
                                <tr class="hover:bg-gray-50/80 transition">
                                    <td class="py-4 px-4 font-bold text-gray-900"><?= htmlspecialchars($n['nom_matiere']) ?></td>
                                    <td class="py-4 px-4 text-gray-500"><?= htmlspecialchars($n['nom_prof'] ?? 'Dr. Diallo') ?></td>
                                    <td class="py-4 px-4 text-gray-600 font-medium"><?= htmlspecialchars($n['coefficient']) ?></td>
                                    <td class="py-4 px-4 font-extrabold text-gray-900"><?= htmlspecialchars($val) ?></td>
                                    <td class="py-4 px-4">
                                        <span class="<?= $badgeColor ?> px-2.5 py-1 rounded-md font-medium text-[11px]">
                                            <?= $mention ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <!-- Données de démonstration fidèles à la maquette -->
                                <tr class="hover:bg-gray-50/80 transition">
                                    <td class="py-4 px-4 font-bold text-gray-900">Algorithmique</td>
                                    <td class="py-4 px-4 text-gray-500">Dr. Diallo</td>
                                    <td class="py-4 px-4 text-gray-600 font-medium">3</td>
                                    <td class="py-4 px-4 font-extrabold text-gray-900">14.5</td>
                                    <td class="py-4 px-4"><span class="bg-blue-50 text-blue-600 px-2.5 py-1 rounded-md font-medium text-[11px]">Bien</span></td>
                                </tr>
                                <tr class="hover:bg-gray-50/80 transition">
                                    <td class="py-4 px-4 font-bold text-gray-900">Base de données</td>
                                    <td class="py-4 px-4 text-gray-500">Dr. Diallo</td>
                                    <td class="py-4 px-4 text-gray-600 font-medium">3</td>
                                    <td class="py-4 px-4 font-extrabold text-gray-900">16.0</td>
                                    <td class="py-4 px-4"><span class="bg-emerald-50 text-emerald-600 px-2.5 py-1 rounded-md font-medium text-[11px]">Très bien</span></td>
                                </tr>
                                <tr class="hover:bg-gray-50/80 transition">
                                    <td class="py-4 px-4 font-bold text-gray-900">Réseaux informatiques</td>
                                    <td class="py-4 px-4 text-gray-500">Dr. Diallo</td>
                                    <td class="py-4 px-4 text-gray-600 font-medium">2</td>
                                    <td class="py-4 px-4 font-extrabold text-gray-900">13.0</td>
                                    <td class="py-4 px-4"><span class="bg-amber-50 text-amber-600 px-2.5 py-1 rounded-md font-medium text-[11px]">Assez bien</span></td>
                                </tr>
                                <tr class="hover:bg-gray-50/80 transition">
                                    <td class="py-4 px-4 font-bold text-gray-900">Mathématiques</td>
                                    <td class="py-4 px-4 text-gray-500">Dr. Diallo</td>
                                    <td class="py-4 px-4 text-gray-600 font-medium">2</td>
                                    <td class="py-4 px-4 font-extrabold text-gray-900">11.5</td>
                                    <td class="py-4 px-4"><span class="bg-gray-100 text-gray-500 px-2.5 py-1 rounded-md font-medium text-[11px]">Passable</span></td>
                                </tr>
                                <tr class="hover:bg-gray-50/80 transition">
                                    <td class="py-4 px-4 font-bold text-gray-900">Programmation Web</td>
                                    <td class="py-4 px-4 text-gray-500">Dr. Diallo</td>
                                    <td class="py-4 px-4 text-gray-600 font-medium">1</td>
                                    <td class="py-4 px-4 font-extrabold text-gray-900">17.5</td>
                                    <td class="py-4 px-4"><span class="bg-emerald-50 text-emerald-600 px-2.5 py-1 rounded-md font-medium text-[11px]">Très bien</span></td>
                                </tr>
                                <tr class="hover:bg-gray-50/80 transition">
                                    <td class="py-4 px-4 font-bold text-gray-900">Anglais Technique</td>
                                    <td class="py-4 px-4 text-gray-500">Dr. Diallo</td>
                                    <td class="py-4 px-4 text-gray-600 font-medium">3</td>
                                    <td class="py-4 px-4 font-extrabold text-gray-900">15.0</td>
                                    <td class="py-4 px-4"><span class="bg-blue-50 text-blue-600 px-2.5 py-1 rounded-md font-medium text-[11px]">Bien</span></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            </div>

        </div>
    </main>

</body>
</html>