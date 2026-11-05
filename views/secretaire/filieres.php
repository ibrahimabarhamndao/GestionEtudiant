<?php
$pageActuelle = 'filieres'; 
$titrePage = 'Filières'; 
$descriptionPage = 'Gérez les filières de formation'; 
require_once '../../config/db.php';

// Gestion de l'ajout d'une filière
$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'ajouter') {
    $code_filiere = trim($_POST['code_filiere']);
    $nom = trim($_POST['nom']);
    $responsable = trim($_POST['responsable']);
    $description = trim($_POST['description']);

    if (!empty($code_filiere) && !empty($nom)) {
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS filieres (
                id INT AUTO_INCREMENT PRIMARY KEY,
                code_filiere VARCHAR(50) UNIQUE NOT NULL,
                nom VARCHAR(150) NOT NULL,
                responsable VARCHAR(150) DEFAULT '',
                description TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");

            $stmt = $pdo->prepare("INSERT INTO filieres (code_filiere, nom, responsable, description) VALUES (?, ?, ?, ?)");
            $stmt->execute([$code_filiere, $nom, $responsable, $description]);
            header("Location: filieres.php");
            exit();
        } catch (PDOException $e) {
            $message = "Erreur : Ce code filière existe déjà.";
        }
    } else {
        $message = "Veuillez remplir les champs obligatoires.";
    }
}

// Récupération des filières
$filieres = [];
try {
    $filieres = $pdo->query("SELECT * FROM filieres ORDER BY id DESC")->fetchAll();
} catch (PDOException $e) {
    $filieres = [];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
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

            <!-- Barre supérieure : Recherche à gauche et Bouton Ajouter juste à côté -->
            <div class="flex items-center space-x-3">
                <div class="relative w-80">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400">
                        <i class="fa-solid fa-magnifying-glass text-xs"></i>
                    </span>
                    <input type="text" placeholder="Rechercher une filière..." class="w-full pl-10 pr-4 py-2.5 bg-white border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600 transition shadow-2xs">
                </div>
                <button onclick="toggleModal(true)" class="bg-red-600 text-white px-4 py-2.5 rounded-lg text-sm font-medium hover:bg-red-700 transition flex items-center space-x-2 shadow-sm">
                    <i class="fa-solid fa-plus text-xs"></i>
                    <span>Ajouter</span>
                </button>
            </div>

            <!-- Carte Conteneur Principale du Tableau -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 space-y-6">
                
                <!-- Sélecteur de vue (Tableau / Cartes) positionné en haut à droite de la carte -->
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

                <!-- Tableau épuré -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="text-gray-400 text-[11px] uppercase tracking-wider border-b border-gray-100">
                                <th class="py-3 px-4 font-semibold">Filière de code</th>
                                <th class="py-3 px-4 font-semibold">Nom</th>
                                <th class="py-3 px-4 font-semibold">Responsable</th>
                                <th class="py-3 px-4 font-semibold">Description</th>
                                <th class="py-3 px-4 font-semibold text-right">Actes</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-sm text-gray-600">
                            <?php if (count($filieres) > 0): ?>
                                <?php foreach ($filieres as $f): ?>
                                <tr class="hover:bg-gray-50/80 transition">
                                    <td class="py-4 px-4">
                                        <span class="bg-red-50 text-red-700 px-2.5 py-1 rounded-md text-xs font-bold"><?= htmlspecialchars($f['code_filiere']) ?></span>
                                    </td>
                                    <td class="py-4 px-4 font-medium text-gray-900"><?= htmlspecialchars($f['nom']) ?></td>
                                    <td class="py-4 px-4 text-gray-600"><?= htmlspecialchars($f['responsable']) ?></td>
                                    <td class="py-4 px-4 text-gray-500 text-xs"><?= htmlspecialchars($f['description']) ?></td>
                                    <td class="py-4 px-4 text-right space-x-3">
                                        <button class="text-gray-400 hover:text-blue-600 transition" title="Modifier">
                                            <i class="fa-regular fa-pen-to-square"></i>
                                        </button>
                                        <button class="text-gray-400 hover:text-red-600 transition" title="Supprimer">
                                            <i class="fa-regular fa-trash-can"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <!-- Données de démonstration conformes à la maquette[cite: 6] -->
                                <tr class="hover:bg-gray-50/80 transition">
                                    <td class="py-4 px-4"><span class="bg-red-50 text-red-700 px-2.5 py-1 rounded-md text-xs font-bold">INFO</span></td>
                                    <td class="py-4 px-4 font-medium text-gray-900">Informatique[cite: 6]</td>
                                    <td class="py-4 px-4 text-gray-600">Dr Amadou Diallo[cite: 6]</td>
                                    <td class="py-4 px-4 text-gray-500 text-xs">Formation en développement logiciels et réseaux[cite: 6]</td>
                                    <td class="py-4 px-4 text-right space-x-3">
                                        <button class="text-gray-400 hover:text-blue-600 transition"><i class="fa-regular fa-pen-to-square"></i></button>
                                        <button class="text-gray-400 hover:text-red-600 transition"><i class="fa-regular fa-trash-can"></i></button>
                                    </td>
                                </tr>
                                <tr class="hover:bg-gray-50/80 transition">
                                    <td class="py-4 px-4"><span class="bg-red-50 text-red-700 px-2.5 py-1 rounded-md text-xs font-bold">GEST</span></td>
                                    <td class="py-4 px-4 font-medium text-gray-900">Gestion[cite: 6]</td>
                                    <td class="py-4 px-4 text-gray-600">Pr. Fatou Seck[cite: 6]</td>
                                    <td class="py-4 px-4 text-gray-500 text-xs">Gestion des entreprises et comptabilité[cite: 6]</td>
                                    <td class="py-4 px-4 text-right space-x-3">
                                        <button class="text-gray-400 hover:text-blue-600 transition"><i class="fa-regular fa-pen-to-square"></i></button>
                                        <button class="text-gray-400 hover:text-red-600 transition"><i class="fa-regular fa-trash-can"></i></button>
                                    </td>
                                </tr>
                                <tr class="hover:bg-gray-50/80 transition">
                                    <td class="py-4 px-4"><span class="bg-red-50 text-red-700 px-2.5 py-1 rounded-md text-xs font-bold">MARQUE</span></td>
                                    <td class="py-4 px-4 font-medium text-gray-900">Commercialisation[cite: 6]</td>
                                    <td class="py-4 px-4 text-gray-600">Dr Ibrahima Ndiaye[cite: 6]</td>
                                    <td class="py-4 px-4 text-gray-500 text-xs">Marketing numérique et communication[cite: 6]</td>
                                    <td class="py-4 px-4 text-right space-x-3">
                                        <button class="text-gray-400 hover:text-blue-600 transition"><i class="fa-regular fa-pen-to-square"></i></button>
                                        <button class="text-gray-400 hover:text-red-600 transition"><i class="fa-regular fa-trash-can"></i></button>
                                    </td>
                                </tr>
                                <tr class="hover:bg-gray-50/80 transition">
                                    <td class="py-4 px-4"><span class="bg-red-50 text-red-700 px-2.5 py-1 rounded-md text-xs font-bold">DROIT</span></td>
                                    <td class="py-4 px-4 font-medium text-gray-900">Droit[cite: 6]</td>
                                    <td class="py-4 px-4 text-gray-600">Pr. Aïssatou Ba[cite: 6]</td>
                                    <td class="py-4 px-4 text-gray-500 text-xs">Droit des affaires et contentieux[cite: 6]</td>
                                    <td class="py-4 px-4 text-right space-x-3">
                                        <button class="text-gray-400 hover:text-blue-600 transition"><i class="fa-regular fa-pen-to-square"></i></button>
                                        <button class="text-gray-400 hover:text-red-600 transition"><i class="fa-regular fa-trash-can"></i></button>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pied de tableau : Pagination identique à la maquette -->
                <div class="flex justify-between items-center pt-4 border-t border-gray-100 text-xs text-gray-500">
                    <span>1 – 4 sur 4[cite: 6]</span>
                    <div class="flex items-center space-x-3">
                        <span>Page 1 sur 1[cite: 6]</span>
                        <div class="flex space-x-1">
                            <button class="w-7 h-7 flex items-center justify-center border border-gray-200 rounded text-gray-300 cursor-not-allowed"><i class="fa-solid fa-chevron-left text-[10px]"></i></button>
                            <button class="w-7 h-7 flex items-center justify-center border border-gray-200 rounded text-gray-300 cursor-not-allowed"><i class="fa-solid fa-chevron-right text-[10px]"></i></button>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </main>

    <!-- MODALE / POP-UP D'AJOUT -->
    <div id="modal-container" class="fixed inset-0 z-50 flex items-center justify-center hidden">
        <div id="modal-backdrop" class="fixed inset-0 bg-black/50 opacity-0 transition-opacity duration-300" onclick="toggleModal(false)"></div>

        <div id="modal-box" class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 p-6 z-10 opacity-0 scale-95 transition-all duration-300 space-y-4">
            
            <div class="flex justify-between items-center border-b border-gray-100 pb-3">
                <h3 class="font-bold text-base text-gray-800">Ajouter une filière</h3>
                <button onclick="toggleModal(false)" class="text-gray-400 hover:text-gray-600 text-xl font-bold">&times;</button>
            </div>

            <form action="filieres.php" method="POST" class="space-y-4">
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
                    <input type="text" name="responsable" placeholder="ex: Dr Amadou Diallo" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Description</label>
                    <textarea name="description" rows="3" placeholder="Description de la filière..." class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600"></textarea>
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
                setTimeout => {
                    container.classList.add('hidden');
                }, 300);
            }
        }
    </script>
</body>
</html>