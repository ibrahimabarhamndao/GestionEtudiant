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
    <!-- FontAwesome pour de vraies icônes professionnelles -->
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

            <!-- Carte Conteneur Principale (identique à la maquette) -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 space-y-6">
                
                <!-- Barre d'outils supérieure : Recherche + Vues + Bouton Ajouter -->
                <div class="flex justify-between items-center">
                    <!-- Recherche -->
                    <div class="relative w-80">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400">
                            <i class="fa-solid fa-magnifying-glass text-xs"></i>
                        </span>
                        <input type="text" placeholder="Rechercher..." class="w-full pl-10 pr-4 py-2 bg-white border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600 transition">
                    </div>

                    <!-- Groupe Droite : Vues (Tableau / Cartes) + Bouton Ajouter -->
                    <div class="flex items-center space-x-3">
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
                        <button onclick="toggleModal(true)" class="bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-red-700 transition flex items-center space-x-2 shadow-sm">
                            <i class="fa-solid fa-plus text-xs"></i>
                            <span>Ajouter un niveau</span>
                        </button>
                    </div>
                </div>

                <!-- Tableau épuré et aligné -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="text-gray-400 text-[11px] uppercase tracking-wider border-b border-gray-100">
                                <th class="py-3 px-4 font-semibold">Code Niveau</th>
                                <th class="py-3 px-4 font-semibold">Libellé</th>
                                <th class="py-3 px-4 font-semibold">Filière</th>
                                <th class="py-3 px-4 font-semibold text-right">Actes</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-sm text-gray-600">
                            <?php if (count($niveaux) > 0): ?>
                                <?php foreach ($niveaux as $n): ?>
                                <tr class="hover:bg-gray-50/80 transition">
                                    <td class="py-4 px-4">
                                        <span class="bg-gray-100 text-gray-700 px-2.5 py-1 rounded-md text-xs font-medium"><?= htmlspecialchars($n['code_niveau']) ?></span>
                                    </td>
                                    <td class="py-4 px-4 font-medium text-gray-900"><?= htmlspecialchars($n['libelle']) ?></td>
                                    <td class="py-4 px-4">
                                        <span class="bg-blue-50 text-blue-600 px-2.5 py-1 rounded-md text-xs font-medium"><?= htmlspecialchars($n['nom_filiere']) ?></span>
                                    </td>
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
                                <!-- Exemples statiques pour refléter la maquette si la base est vide -->
                                <tr class="hover:bg-gray-50/80 transition">
                                    <td class="py-4 px-4"><span class="bg-gray-100 text-gray-700 px-2.5 py-1 rounded-md text-xs font-medium">INFO-L1</span></td>
                                    <td class="py-4 px-4 font-medium text-gray-900">Licence 1ère année</td>
                                    <td class="py-4 px-4"><span class="bg-blue-50 text-blue-600 px-2.5 py-1 rounded-md text-xs font-medium">Informatique</span></td>
                                    <td class="py-4 px-4 text-right space-x-3">
                                        <button class="text-gray-400 hover:text-blue-600 transition"><i class="fa-regular fa-pen-to-square"></i></button>
                                        <button class="text-gray-400 hover:text-red-600 transition"><i class="fa-regular fa-trash-can"></i></button>
                                    </td>
                                </tr>
                                <tr class="hover:bg-gray-50/80 transition">
                                    <td class="py-4 px-4"><span class="bg-gray-100 text-gray-700 px-2.5 py-1 rounded-md text-xs font-medium">INFO-L2</span></td>
                                    <td class="py-4 px-4 font-medium text-gray-900">Licence 2ème année</td>
                                    <td class="py-4 px-4"><span class="bg-blue-50 text-blue-600 px-2.5 py-1 rounded-md text-xs font-medium">Informatique</span></td>
                                    <td class="py-4 px-4 text-right space-x-3">
                                        <button class="text-gray-400 hover:text-blue-600 transition"><i class="fa-regular fa-pen-to-square"></i></button>
                                        <button class="text-gray-400 hover:text-red-600 transition"><i class="fa-regular fa-trash-can"></i></button>
                                    </td>
                                </tr>
                                <tr class="hover:bg-gray-50/80 transition">
                                    <td class="py-4 px-4"><span class="bg-gray-100 text-gray-700 px-2.5 py-1 rounded-md text-xs font-medium">DROIT-M1</span></td>
                                    <td class="py-4 px-4 font-medium text-gray-900">Master 1ère année</td>
                                    <td class="py-4 px-4"><span class="bg-blue-50 text-blue-600 px-2.5 py-1 rounded-md text-xs font-medium">Droit</span></td>
                                    <td class="py-4 px-4 text-right space-x-3">
                                        <button class="text-gray-400 hover:text-blue-600 transition"><i class="fa-regular fa-pen-to-square"></i></button>
                                        <button class="text-gray-400 hover:text-red-600 transition"><i class="fa-regular fa-trash-can"></i></button>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pied de tableau : Pagination -->
                <div class="flex justify-between items-center pt-4 border-t border-gray-100 text-xs text-gray-500">
                    <span>1 – 5 sur 7</span>
                    <div class="flex items-center space-x-3">
                        <span>Page 1 sur 2</span>
                        <div class="flex space-x-1">
                            <button class="w-7 h-7 flex items-center justify-center border border-gray-200 rounded text-gray-400 hover:bg-gray-50 transition"><i class="fa-solid fa-chevron-left text-[10px]"></i></button>
                            <button class="w-7 h-7 flex items-center justify-center border border-gray-200 rounded text-gray-700 hover:bg-gray-50 transition"><i class="fa-solid fa-chevron-right text-[10px]"></i></button>
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