<?php
$pageActuelle = 'niveaux'; 
$titrePage = 'Niveaux'; 
$descriptionPage = 'Niveaux d\'études de l\'établissement'; 
require_once '../../config/db.php';

// Gestion de l'ajout d'un niveau
$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'ajouter') {
    $code_niveau = trim($_POST['code_niveau']);
    $libelle = trim($_POST['libelle']);
    $filiere_id = trim($_POST['filiere_id']);

    if (!empty($code_niveau) && !empty($libelle) && !empty($filiere_id)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO niveaux (code_niveau, libelle, filiere_id) VALUES (?, ?, ?)");
            $stmt->execute([$code_niveau, $libelle, $filiere_id]);
            header("Location: niveaux.php");
            exit();
        } catch (PDOException $e) {
            $message = "Erreur : Ce code niveau existe déjà ou la filière est invalide.";
        }
    } else {
        $message = "Veuillez remplir tous les champs obligatoires.";
    }
}

// Récupération des niveaux avec jointure
$niveaux = [];
try {
    $query = "SELECT niveaux.*, filieres.nom AS nom_filiere, filieres.code_filiere 
              FROM niveaux 
              JOIN filieres ON niveaux.filiere_id = filieres.id 
              ORDER BY niveaux.id DESC";
    $niveaux = $pdo->query($query)->fetchAll();
} catch (PDOException $e) {
    $niveaux = [];
}

// Récupération des filières pour le select
$filieres = [];
try {
    $filieres = $pdo->query("SELECT * FROM filieres ORDER BY nom ASC")->fetchAll();
} catch (PDOException $e) {
    $filieres = [];
}

// Données de démonstration conformes à la maquette (si la table est vide)
if (count($niveaux) === 0) {
    $niveaux = [
        ['code_niveau' => 'INFO-L1',  'libelle' => 'Licence 1ère année', 'nom_filiere' => 'Informatique'],
        ['code_niveau' => 'INFO-L2',  'libelle' => 'Licence 2ème année', 'nom_filiere' => 'Informatique'],
        ['code_niveau' => 'INFO-L3',  'libelle' => 'Licence 3ème année', 'nom_filiere' => 'Informatique'],
        ['code_niveau' => 'DROIT-M1', 'libelle' => 'Master 1ère année',  'nom_filiere' => 'Droit'],
        ['code_niveau' => 'DROIT-M2', 'libelle' => 'Master 2ème année',  'nom_filiere' => 'Droit'],
        ['code_niveau' => 'GEST-L1',  'libelle' => 'Licence 1ère année', 'nom_filiere' => 'Gestion'],
        ['code_niveau' => 'MARK-L1',  'libelle' => 'Licence 1ère année', 'nom_filiere' => 'Marketing'],
    ];
}
$total = count($niveaux);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Administration — Niveaux</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 flex h-screen overflow-hidden font-sans">

    <!-- Inclusion de la Sidebar unifiée -->
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
                    <input type="text" placeholder="Rechercher un niveau..." class="w-full pl-4 pr-10 py-2.5 bg-white border border-gray-200 rounded-lg text-sm text-gray-700 placeholder-gray-400 focus:outline-none focus:border-red-600 transition">
                    <span class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-gray-800 pointer-events-none">
                        <i class="fa-solid fa-magnifying-glass text-sm"></i>
                    </span>
                </div>
                <button onclick="toggleModal(true)" class="bg-red-600 text-white px-5 py-2.5 rounded-lg text-sm font-semibold hover:bg-red-700 transition flex items-center space-x-2 shadow-sm">
                    <span>Ajouter un niveau</span>
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
                    <table class="w-full text-center border-collapse">
                        <thead>
                            <tr class="bg-gray-200 text-slate-500 text-[11px] uppercase tracking-wider">
                                <th class="py-4 px-6 font-semibold">Code niveau</th>
                                <th class="py-4 px-4 font-semibold">Libellé</th>
                                <th class="py-4 px-4 font-semibold">Filière</th>
                                <th class="py-4 px-6 font-semibold">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-sm">
                            <?php foreach ($niveaux as $n): ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="py-4 px-6">
                                    <span class="inline-block bg-gray-100 text-gray-600 px-2.5 py-1 rounded-full text-[11px] font-medium"><?= htmlspecialchars($n['code_niveau']) ?></span>
                                </td>
                                <td class="py-4 px-4 font-bold text-gray-900"><?= htmlspecialchars($n['libelle']) ?></td>
                                <td class="py-4 px-4">
                                    <span class="inline-block bg-blue-50 text-blue-700 px-2.5 py-1 rounded-full text-[11px] font-medium"><?= htmlspecialchars($n['nom_filiere']) ?></span>
                                </td>
                                <td class="py-4 px-6 text-center space-x-3">
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
                        <?php foreach ($niveaux as $n): ?>
                        <div class="bg-white border border-gray-100 rounded-xl shadow-md p-4">
                            <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                                <span class="inline-block bg-gray-100 text-gray-600 px-2.5 py-1 rounded text-[11px] font-medium"><?= htmlspecialchars($n['code_niveau']) ?></span>
                                <div class="flex items-center space-x-3 text-slate-500">
                                    <button class="hover:text-blue-600 transition" title="Modifier"><i class="fa-solid fa-pen text-xs"></i></button>
                                    <button class="hover:text-red-600 transition" title="Supprimer"><i class="fa-regular fa-trash-can text-xs"></i></button>
                                </div>
                            </div>
                            <div class="pt-3 space-y-3">
                                <div>
                                    <p class="text-xs text-slate-500">Libellé</p>
                                    <p class="text-sm font-bold text-gray-900"><?= htmlspecialchars($n['libelle']) ?></p>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-500 mb-1">Filière</p>
                                    <span class="inline-block bg-blue-50 text-blue-700 px-2.5 py-1 rounded-full text-[11px] font-medium"><?= htmlspecialchars($n['nom_filiere']) ?></span>
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

    <!-- MODALE / POP-UP D'AJOUT DE NIVEAU -->
    <div id="modal-container" class="fixed inset-0 z-50 flex items-center justify-center hidden">
        <div id="modal-backdrop" class="fixed inset-0 bg-black/50 opacity-0 transition-opacity duration-300" onclick="toggleModal(false)"></div>

        <div id="modal-box" class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 p-6 z-10 opacity-0 scale-95 transition-all duration-300 space-y-4">
            
            <div class="flex justify-between items-center border-b border-gray-100 pb-3">
                <h3 class="font-bold text-base text-gray-800">Ajouter un niveau</h3>
                <button onclick="toggleModal(false)" class="text-gray-400 hover:text-gray-600 text-xl font-bold">&times;</button>
            </div>

            <form action="niveaux.php" method="POST" class="space-y-4">
                <input type="hidden" name="action" value="ajouter">
                
                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Filière</label>
                    <select name="filiere_id"  class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600 bg-white">
                        <option value="">Sélectionner une filière</option>
                        <?php foreach ($filieres as $f): ?>
                            <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['nom']) ?> (<?= htmlspecialchars($f['code_filiere']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Code Niveau</label>
                    <input type="text" name="code_niveau"  placeholder="ex: INFO-L1" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Libellé du niveau</label>
                    <input type="text" name="libelle"  placeholder="ex: Licence 1ère année" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600">
                </div>

                <div class="pt-2 flex justify-end space-x-2">
                    <button type="button" onclick="toggleModal(false)" class="px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition">
                        Annuler
                    </button>
                    <button type="submit" class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition shadow-sm">
                        Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        function toggleModal(open) {
            const container = document.getElementById('modal-container');
            const backdrop = document.getElementById('modal-backdrop');
            const box = document.getElementById('modal-box');

            if (open) {
                container.classList.remove('hidden');
                setTimeout(() => {
                    backdrop.classList.remove('opacity-0');
                    box.classList.remove('opacity-0', 'scale-95');
                    box.classList.add('opacity-100', 'scale-100');
                }, 10);
            } else {
                backdrop.classList.add('opacity-0');
                box.classList.remove('opacity-100', 'scale-100');
                box.classList.add('opacity-0', 'scale-95');
                setTimeout(() => {
                    container.classList.add('hidden');
                }, 300);
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