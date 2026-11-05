<?php
$pageActuelle = 'niveaux';
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
$query = "SELECT niveaux.*, filieres.nom AS nom_filiere, filieres.code_filiere 
          FROM niveaux 
          JOIN filieres ON niveaux.filiere_id = filieres.id 
          ORDER BY niveaux.id DESC";
$niveaux = $pdo->query($query)->fetchAll();

// Récupération des filières pour le select
$filieres = $pdo->query("SELECT * FROM filieres ORDER BY nom ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Administration — Niveaux</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 flex h-screen overflow-hidden">

    <!-- Inclusion de la Sidebar unifiée -->
    <?php include '../../includes/sidebar_secretaire.php'; ?>

    <!-- Contenu Principal -->
    <main class="flex-1 flex flex-col h-screen overflow-hidden">
        
        <!-- Header Supérieur -->
        <header class="bg-white border-b border-gray-100 px-8 py-4 flex justify-between items-center">
            <div>
                <h2 class="text-xl font-bold text-gray-800">Niveaux</h2>
                <p class="text-xs text-gray-500">Niveaux d'études de l'établissement</p>
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
        <div class="flex-1 p-8 overflow-y-auto">
            
            <!-- Barre de recherche et Bouton Ajouter / Vues -->
            <div class="flex justify-between items-center mb-6">
                <div class="relative w-96">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">🔍</span>
                    <input type="text" placeholder="Rechercher une filière..." class="w-full pl-10 pr-4 py-2 bg-white border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600">
                </div>
                <div class="flex items-center space-x-3">
                    <div class="bg-white border border-gray-200 rounded-lg p-1 flex text-xs">
                        <button class="px-3 py-1 bg-red-50 text-red-600 font-semibold rounded">Tableau</button>
                        <button class="px-3 py-1 text-gray-500 hover:text-gray-800">Cartes</button>
                    </div>
                    <button onclick="toggleDrawer(true)" class="bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-red-700 transition flex items-center space-x-1">
                        <span>Ajouter un niveaux</span> <span class="font-bold">+</span>
                    </button>
                </div>
            </div>

            <?php if (!empty($message)): ?>
                <div class="mb-4 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 text-sm rounded">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <!-- Tableau des Niveaux -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 text-gray-400 text-xs uppercase tracking-wider border-b border-gray-100">
                            <th class="p-4 font-semibold">Code Niveau</th>
                            <th class="p-4 font-semibold">Libellé</th>
                            <th class="p-4 font-semibold">Filière</th>
                            <th class="p-4 font-semibold text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm text-gray-600">
                        <?php if (count($niveaux) > 0): ?>
                            <?php foreach ($niveaux as $n): ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="p-4 font-bold text-gray-700">
                                    <span class="bg-gray-100 px-2.5 py-1 rounded-md text-xs"><?= htmlspecialchars($n['code_niveau']) ?></span>
                                </td>
                                <td class="p-4 font-semibold text-gray-800"><?= htmlspecialchars($n['libelle']) ?></td>
                                <td class="p-4">
                                    <span class="bg-blue-50 text-blue-600 px-2.5 py-1 rounded-md text-xs font-bold"><?= htmlspecialchars($n['nom_filiere']) ?></span>
                                </td>
                                <td class="p-4 text-right space-x-2">
                                    <button class="text-gray-400 hover:text-blue-600 transition" title="Modifier">✏️</button>
                                    <button class="text-gray-400 hover:text-red-600 transition" title="Supprimer">🗑️</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="p-8 text-center text-gray-400">Aucun niveau enregistré pour le moment.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- DRAWER D'AJOUT -->
    <div id="drawer-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden transition-opacity opacity-0" onclick="toggleDrawer(false)"></div>
    
    <div id="drawer" class="fixed top-0 right-0 h-full w-96 bg-white shadow-2xl z-50 transform translate-x-full transition-transform duration-300 ease-in-out flex flex-col justify-between">
        <div>
            <div class="p-6 border-b border-gray-100 flex justify-between items-center">
                <h3 class="font-bold text-lg text-gray-800">Ajouter un niveau</h3>
                <button onclick="toggleDrawer(false)" class="text-gray-400 hover:text-gray-600 text-xl font-bold">&times;</button>
            </div>

            <form action="niveaux.php" method="POST" class="p-6 space-y-4">
                <input type="hidden" name="action" value="ajouter">
                
                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Filière</label>
                    <select name="filiere_id" required class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600 bg-white">
                        <option value="">Sélectionner une filière</option>
                        <?php foreach ($filieres as $f): ?>
                            <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['nom']) ?> (<?= htmlspecialchars($f['code_filiere']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Code Niveau</label>
                    <input type="text" name="code_niveau" required placeholder="ex: INFO-L1" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Libellé du niveau</label>
                    <input type="text" name="libelle" required placeholder="ex: Licence 1ère année" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600">
                </div>

                <div class="pt-4">
                    <button type="submit" class="w-full bg-red-600 text-white py-2.5 rounded-lg text-sm font-medium hover:bg-red-700 transition">
                        Enregistrer le niveau
                    </button>
                </div>
            </form>
        </div>
    </div>

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
    </script>
</body>
</html>