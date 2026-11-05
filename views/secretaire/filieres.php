<?php
$pageActuelle = 'filieres'; 
$titrePage = 'Filières'; 
$descriptionPage = 'Gérez les filières de formation';
require_once '../../config/db.php';

// Gestion de l'ajout d'une filière
$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'ajouter') {
    $code = trim($_POST['code_filiere']);
    $nom = trim($_POST['nom']);
    $responsable = trim($_POST['responsable']);
    $description = trim($_POST['description']);

    if (!empty($code) && !empty($nom) && !empty($responsable)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO filieres (code_filiere, nom, responsable, description) VALUES (?, ?, ?, ?)");
            $stmt->execute([$code, $nom, $responsable, $description]);
            header("Location: filieres.php");
            exit();
        } catch (PDOException $e) {
            $message = "Erreur : Ce code filière existe déjà.";
        }
    } else {
        $message = "Veuillez remplir tous les champs obligatoires.";
    }
}

// Récupération des filières
$stmt = $pdo->query("SELECT * FROM filieres ORDER BY id DESC");
$filieres = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Administration — Filières</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 flex h-screen overflow-hidden">

    <!-- Inclusion de la Sidebar unifiée -->
    <?php include '../../includes/sidebar_secretaire.php'; ?>

    <!-- Contenu Principal -->
    <main class="flex-1 flex flex-col h-screen overflow-hidden">
      <!-- Header Supérieur Dynamique -->
    <?php include '../../includes/header.php'; ?>
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
                        <span>Ajouter</span> <span class="font-bold">+</span>
                    </button>
                </div>
            </div>

            <?php if (!empty($message)): ?>
                <div class="mb-4 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 text-sm rounded">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <!-- Tableau des Filières -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 text-gray-400 text-xs uppercase tracking-wider border-b border-gray-100">
                            <th class="p-4 font-semibold">Code Filière</th>
                            <th class="p-4 font-semibold">Nom</th>
                            <th class="p-4 font-semibold">Responsable</th>
                            <th class="p-4 font-semibold">Description</th>
                            <th class="p-4 font-semibold text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm text-gray-600">
                        <?php if (count($filieres) > 0): ?>
                            <?php foreach ($filieres as $f): ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="p-4 font-bold text-red-600">
                                    <span class="bg-red-50 px-2.5 py-1 rounded-md text-xs"><?= htmlspecialchars($f['code_filiere']) ?></span>
                                </td>
                                <td class="p-4 font-semibold text-gray-800"><?= htmlspecialchars($f['nom']) ?></td>
                                <td class="p-4 text-gray-600"><?= htmlspecialchars($f['responsable']) ?></td>
                                <td class="p-4 text-gray-500"><?= htmlspecialchars($f['description']) ?></td>
                                <td class="p-4 text-right space-x-2">
                                    <button class="text-gray-400 hover:text-blue-600 transition" title="Modifier">✏️</button>
                                    <button class="text-gray-400 hover:text-red-600 transition" title="Supprimer">🗑️</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="p-8 text-center text-gray-400">Aucune filière trouvée pour le moment.</td>
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
                <h3 class="font-bold text-lg text-gray-800">Ajouter une filière</h3>
                <button onclick="toggleDrawer(false)" class="text-gray-400 hover:text-gray-600 text-xl font-bold">&times;</button>
            </div>

            <form action="filieres.php" method="POST" class="p-6 space-y-4">
                <input type="hidden" name="action" value="ajouter">
                
                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Code Filière</label>
                    <input type="text" name="code_filiere" required placeholder="ex: INFO" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Nom de la filière</label>
                    <input type="text" name="nom" required placeholder="ex: Informatique" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Responsable</label>
                    <input type="text" name="responsable" required placeholder="ex: Dr. Amadou Diallo" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Description</label>
                    <textarea name="description" rows="3" placeholder="Description de la filière..." class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600"></textarea>
                </div>

                <div class="pt-4">
                    <button type="submit" class="w-full bg-red-600 text-white py-2.5 rounded-lg text-sm font-medium hover:bg-red-700 transition">
                        Enregistrer la filière
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