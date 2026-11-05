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
                    <button onclick="toggleModal(true)" class="bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-red-700 transition flex items-center space-x-1 shadow-sm">
                        <span>Ajouter un niveau</span> <span class="font-bold">+</span>
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

    <!-- MODALE / POP-UP D'AJOUT DE NIVEAU -->
    <div id="modal-container" class="fixed inset-0 z-50 flex items-center justify-center hidden">
        <!-- Fond sombre semi-transparent -->
        <div id="modal-backdrop" class="fixed inset-0 bg-black/50 opacity-0 transition-opacity duration-300" onclick="toggleModal(false)"></div>

        <!-- Boîte Modale -->
        <div id="modal-box" class="relative bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 p-6 z-10 opacity-0 scale-95 transition-all duration-300">
            
            <!-- En-tête Pop-up -->
            <div class="flex justify-between items-center border-b border-gray-100 pb-4 mb-4">
                <h3 class="font-bold text-lg text-gray-800">Ajouter un niveau</h3>
                <button onclick="toggleModal(false)" class="text-gray-400 hover:text-gray-600 text-2xl font-bold leading-none">&times;</button>
            </div>

            <!-- Formulaire -->
            <form action="niveaux.php" method="POST" class="space-y-4">
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

                <!-- Actions du Formulaire -->
                <div class="pt-4 flex justify-end space-x-3">
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

    <!-- Script de contrôle du Pop-up -->
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
    </script>
</body>
</html>