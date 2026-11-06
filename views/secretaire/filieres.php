<?php
$pageActuelle = 'filieres'; 
$titrePage = 'Filières'; 
$descriptionPage = 'Gérez les filières de formation'; 
require_once '../../config/db.php';

$message = "";

// Traitement de l'ajout d'une filière
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'ajouter_filiere') {
    $code_filiere = trim($_POST['code_filiere']);
    $nom = trim($_POST['nom']);
    $responsable = trim($_POST['responsable']);
    $description = trim($_POST['description']);

    if (!empty($code_filiere) && !empty($nom)) {
        try {
            // Création automatique de la table filieres si elle n'existe pas
            $pdo->exec("CREATE TABLE IF NOT EXISTS filieres (
                id INT AUTO_INCREMENT PRIMARY KEY,
                code_filiere VARCHAR(50) NOT NULL,
                nom VARCHAR(150) NOT NULL,
                responsable VARCHAR(150) DEFAULT NULL,
                description TEXT DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");

            $stmt = $pdo->prepare("INSERT INTO filieres (code_filiere, nom, responsable, description) VALUES (?, ?, ?, ?)");
            $stmt->execute([$code_filiere, $nom, $responsable, $description]);
            
            header("Location: filieres.php");
            exit();
        } catch (PDOException $e) {
            $message = "Erreur : Ce code de filière existe déjà.";
        }
    } else {
        $message = "Veuillez remplir les champs obligatoires (*).";
    }
}

// Récupération des filières
$filieres = [];
try {
    $filieres = $pdo->query("SELECT * FROM filieres ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $filieres = [];
}

// Données de démonstration conformes à la maquette (si la table est vide)
if (count($filieres) === 0) {
    $filieres = [
        ['code_filiere' => 'INFO',  'nom' => 'Informatique', 'responsable' => 'Dr. Amadou Diallo', 'description' => 'Formation en développement logiciel et réseaux'],
        ['code_filiere' => 'GEST',  'nom' => 'Gestion',      'responsable' => 'Pr. Fatou Seck',     'description' => 'Gestion des entreprises et comptabilité'],
        ['code_filiere' => 'MARK',  'nom' => 'Marketing',    'responsable' => 'Dr. Ibrahima Ndiaye','description' => 'Marketing digital et communication'],
        ['code_filiere' => 'DROIT', 'nom' => 'Droit',        'responsable' => 'Pr. Aïssatou Ba',    'description' => 'Droit des affaires et contentieux'],
    ];
}
$total = count($filieres);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Administration — Filières</title>
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

            <!-- Barre d'outils : Recherche (gauche) + Bouton Ajouter (droite) -->
            <div class="flex items-center justify-between">
                <div class="relative w-80">
                    <input type="text" placeholder="Rechercher une filière..." class="w-full pl-4 pr-10 py-2.5 bg-white border border-gray-200 rounded-lg text-sm text-gray-700 placeholder-gray-400 focus:outline-none focus:border-red-600 transition">
                    <span class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-gray-800 pointer-events-none">
                        <i class="fa-solid fa-magnifying-glass text-sm"></i>
                    </span>
                </div>
                <button onclick="toggleDrawer(true)" class="bg-red-600 text-white px-5 py-2.5 rounded-lg text-sm font-semibold hover:bg-red-700 transition flex items-center space-x-2 shadow-sm">
                    <span>Ajouter</span>
                    <i class="fa-solid fa-plus text-xs"></i>
                </button>
            </div>

            <!-- Carte conteneur principale -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                
                <!-- Sélecteur de vue (Tableau / Cartes) -->
                <div class="flex justify-end px-4 py-3 border-b border-gray-100">
                    <div class="bg-gray-100 rounded-lg p-1 flex text-xs font-medium">
                        <button id="btn-table" type="button" onclick="setView('table')" class="px-3 py-1.5 rounded-md flex items-center space-x-1.5 transition bg-white text-red-600 shadow-sm">
                            <i class="fa-solid fa-table"></i>
                            <span>Tableau</span>
                        </button>
                        <button id="btn-cards" type="button" onclick="setView('cards')" class="px-3 py-1.5 rounded-md flex items-center space-x-1.5 transition text-gray-500 hover:text-gray-800">
                            <i class="fa-solid fa-border-all"></i>
                            <span>Cartes</span>
                        </button>
                    </div>
                </div>

                <!-- VUE TABLEAU -->
                <div id="view-table" class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 text-slate-500 text-[11px] uppercase tracking-wider">
                                <th class="py-4 px-6 font-semibold">Code filière</th>
                                <th class="py-4 px-4 font-semibold">Nom</th>
                                <th class="py-4 px-4 font-semibold">Responsable</th>
                                <th class="py-4 px-4 font-semibold">Description</th>
                                <th class="py-4 px-6 font-semibold text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-sm">
                            <?php foreach ($filieres as $f): ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="py-4 px-6">
                                    <span class="inline-block px-2.5 py-1 bg-red-50 text-red-600 text-[11px] font-bold rounded-full"><?= htmlspecialchars($f['code_filiere']) ?></span>
                                </td>
                                <td class="py-4 px-4 font-bold text-gray-900"><?= htmlspecialchars($f['nom']) ?></td>
                                <td class="py-4 px-4 text-xs text-slate-500"><?= htmlspecialchars($f['responsable'] ?: 'Non assigné') ?></td>
                                <td class="py-4 px-4 text-xs text-slate-500"><?= htmlspecialchars($f['description'] ?: '-') ?></td>
                                <td class="py-4 px-6 text-right space-x-3">
                                    <button class="text-slate-500 hover:text-blue-600 transition" title="Modifier"><i class="fa-solid fa-pen"></i></button>
                                    <button class="text-slate-500 hover:text-red-600 transition" title="Supprimer"><i class="fa-regular fa-trash-can"></i></button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- VUE CARTES -->
                <div id="view-cards" class="hidden p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                        <?php foreach ($filieres as $f): ?>
                        <div class="bg-white border border-gray-100 rounded-xl shadow-md p-4">
                            <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                                <span class="inline-block px-2.5 py-1 bg-red-50 text-red-600 text-[11px] font-bold rounded"><?= htmlspecialchars($f['code_filiere']) ?></span>
                                <div class="flex items-center space-x-3 text-slate-500">
                                    <button class="hover:text-blue-600 transition" title="Modifier"><i class="fa-solid fa-pen text-xs"></i></button>
                                    <button class="hover:text-red-600 transition" title="Supprimer"><i class="fa-regular fa-trash-can text-xs"></i></button>
                                </div>
                            </div>
                            <div class="pt-3 space-y-3">
                                <div>
                                    <p class="text-xs text-slate-500">Nom</p>
                                    <p class="text-sm font-bold text-gray-900"><?= htmlspecialchars($f['nom']) ?></p>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-500">Responsable</p>
                                    <p class="text-xs text-slate-600"><?= htmlspecialchars($f['responsable'] ?: 'Non assigné') ?></p>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-500">Description</p>
                                    <p class="text-xs text-slate-600"><?= htmlspecialchars($f['description'] ?: '-') ?></p>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Pagination -->
                <div class="flex justify-between items-center px-6 py-4 border-t border-gray-100 text-xs text-slate-500">
                    <span>1–<?= $total ?> sur <?= $total ?></span>
                    <div class="flex items-center space-x-3">
                        <span>Page 1 sur 1</span>
                        <div class="flex space-x-1">
                            <button class="w-8 h-8 flex items-center justify-center border border-gray-200 rounded-lg text-gray-300 cursor-not-allowed"><i class="fa-solid fa-chevron-left text-[10px]"></i></button>
                            <button class="w-8 h-8 flex items-center justify-center border border-gray-200 rounded-lg text-gray-300 cursor-not-allowed"><i class="fa-solid fa-chevron-right text-[10px]"></i></button>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </main>

    <!-- DRAWER LATÉRAL : NOUVELLE FILIÈRE -->
    <div id="drawer-overlay" class="fixed inset-0 bg-black/50 z-50 hidden transition-opacity opacity-0" onclick="toggleDrawer(false)"></div>
    
    <div id="drawer" class="fixed top-0 right-0 h-full w-[420px] bg-white shadow-2xl z-50 transform translate-x-full transition-transform duration-300 ease-in-out flex flex-col justify-between overflow-y-auto">
        <div>
            <div class="p-6 border-b border-gray-100 flex justify-between items-center sticky top-0 bg-white z-10">
                <h3 class="font-bold text-base text-gray-800">Nouvelle filière</h3>
                <button onclick="toggleDrawer(false)" class="text-gray-400 hover:text-gray-600 text-xl font-bold">&times;</button>
            </div>

            <form action="filieres.php" method="POST" class="p-6 space-y-4">
                <input type="hidden" name="action" value="ajouter_filiere">
                
                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">CODE*</label>
                    <input type="text" name="code_filiere" required placeholder="INFO" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">NOM*</label>
                    <div class="relative">
                        <input type="text" name="nom" required placeholder="Informatique" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600">
                        <span class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 pointer-events-none">
                            <i class="fa-regular fa-calendar text-xs"></i>
                        </span>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">RESPONSABLE</label>
                    <div class="relative">
                        <input type="text" name="responsable" placeholder="Dr. Nom Prenom" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600">
                        <span class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 pointer-events-none">
                            <i class="fa-regular fa-calendar text-xs"></i>
                        </span>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">DESCRIPTION</label>
                    <textarea name="description" rows="3" placeholder="Description de la filière......" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600"></textarea>
                </div>

                <div class="pt-4 flex items-center justify-end space-x-3 border-t border-gray-100">
                    <button type="button" onclick="toggleDrawer(false)" class="px-4 py-2.5 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition">
                        Annuler
                    </button>
                    <button type="submit" class="px-4 py-2.5 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition shadow-sm">
                        Ajouter la filière
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Scripts -->
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

        // Bascule entre la vue Tableau et la vue Cartes
        function setView(mode) {
            const isTable = mode === 'table';
            document.getElementById('view-table').classList.toggle('hidden', !isTable);
            document.getElementById('view-cards').classList.toggle('hidden', isTable);

            const active = ['bg-white', 'text-red-600', 'shadow-sm'];
            const inactive = ['text-gray-500', 'hover:text-gray-800'];
            const btnTable = document.getElementById('btn-table');
            const btnCards = document.getElementById('btn-cards');

            [[btnTable, isTable], [btnCards, !isTable]].forEach(([btn, on]) => {
                active.forEach(c => btn.classList.toggle(c, on));
                inactive.forEach(c => btn.classList.toggle(c, !on));
            });
        }
    </script>
</body>
</html>