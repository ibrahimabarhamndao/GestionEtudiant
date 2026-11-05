<?php
$pageActuelle = 'documents'; 
$titrePage = 'Documents'; 
$descriptionPage = 'Gestion des documents et ressources'; 
require_once '../../config/db.php';

$message = "";

// Traitement de l'ajout d'un document
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'ajouter_document') {
    $nom_document = trim($_POST['nom_document']);
    $categorie = trim($_POST['categorie']);
    $type_fichier = trim($_POST['type_fichier']);
    $taille = trim($_POST['taille']);

    if (!empty($nom_document) && !empty($categorie)) {
        try {
            // Création automatique de la table documents si elle n'existe pas
            $pdo->exec("CREATE TABLE IF NOT EXISTS documents (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nom_document VARCHAR(255) NOT NULL,
                categorie VARCHAR(100) NOT NULL,
                type_fichier VARCHAR(20) NOT NULL,
                taille VARCHAR(50) DEFAULT '500 Ko',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");

            $stmt = $pdo->prepare("INSERT INTO documents (nom_document, categorie, type_fichier, taille) VALUES (?, ?, ?, ?)");
            $stmt->execute([$nom_document, $categorie, $type_fichier, $taille]);
            header("Location: documents.php");
            exit();
        } catch (PDOException $e) {
            $message = "Erreur lors de l'enregistrement du document.";
        }
    } else {
        $message = "Veuillez remplir les champs obligatoires.";
    }
}

// Récupération des documents
$documents = [];
try {
    $documents = $pdo->query("SELECT * FROM documents ORDER BY id DESC")->fetchAll();
} catch (PDOException $e) {
    $documents = [];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Administration — Documents</title>
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

            <!-- Barre d'outils supérieure : Recherche + Bouton Ajouter -->
            <div class="flex items-center space-x-3">
                <div class="relative w-80">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400">
                        <i class="fa-solid fa-magnifying-glass text-xs"></i>
                    </span>
                    <input type="text" placeholder="Rechercher un document..." class="w-full pl-10 pr-4 py-2.5 bg-white border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600 transition shadow-2xs">
                </div>
                <button onclick="toggleModal(true)" class="bg-red-600 text-white px-4 py-2.5 rounded-lg text-sm font-medium hover:bg-red-700 transition flex items-center space-x-2 shadow-sm">
                    <i class="fa-solid fa-plus text-xs"></i>
                    <span>Ajouter</span>
                </button>
            </div>

            <!-- Carte Conteneur Principale du Tableau -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 space-y-6">
                
                <!-- Sélecteur de vue (Tableau / Cartes) en haut à droite de la carte -->
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

                <!-- Tableau des Documents -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="text-gray-400 text-[11px] uppercase tracking-wider border-b border-gray-100">
                                <th class="py-3 px-4 font-semibold">Nom du document</th>
                                <th class="py-3 px-4 font-semibold">Catégorie</th>
                                <th class="py-3 px-4 font-semibold">Taper</th>
                                <th class="py-3 px-4 font-semibold">Taille</th>
                                <th class="py-3 px-4 font-semibold">Date</th>
                                <th class="py-3 px-4 font-semibold text-right">Actes</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-sm text-gray-600">
                            <?php if (count($documents) > 0): ?>
                                <?php foreach ($documents as $d): ?>
                                <tr class="hover:bg-gray-50/80 transition">
                                    <td class="py-4 px-4 font-bold text-gray-900"><?= htmlspecialchars($d['nom_document']) ?></td>
                                    <td class="py-4 px-4">
                                        <span class="bg-gray-100 text-gray-700 px-2.5 py-1 rounded-md text-xs font-medium"><?= htmlspecialchars($d['categorie']) ?></span>
                                    </td>
                                    <td class="py-4 px-4">
                                        <span class="bg-red-50 text-red-600 px-2 py-0.5 rounded text-xs font-bold"><?= htmlspecialchars($d['type_fichier']) ?></span>
                                    </td>
                                    <td class="py-4 px-4 text-gray-500 text-xs"><?= htmlspecialchars($d['taille']) ?></td>
                                    <td class="py-4 px-4 text-gray-500 text-xs"><?= date('d/m/Y', strtotime($d['created_at'])) ?></td>
                                    <td class="py-4 px-4 text-right space-x-3">
                                        <button class="text-gray-400 hover:text-blue-600 transition" title="Modifier"><i class="fa-regular fa-pen-to-square"></i></button>
                                        <button class="text-gray-400 hover:text-emerald-600 transition" title="Télécharger"><i class="fa-solid fa-download"></i></button>
                                        <button class="text-gray-400 hover:text-red-600 transition" title="Supprimer"><i class="fa-regular fa-trash-can"></i></button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <!-- Données de démonstration conformes à la maquette[cite: 10] -->
                                <tr class="hover:bg-gray-50/80 transition">
                                    <td class="py-4 px-4 font-bold text-gray-900">Règlement intérieur 2024-2025[cite: 10]</td>
                                    <td class="py-4 px-4"><span class="bg-gray-100 text-gray-700 px-2.5 py-1 rounded-md text-xs font-medium">Administratif[cite: 10]</span></td>
                                    <td class="py-4 px-4"><span class="bg-red-50 text-red-600 px-2 py-0.5 rounded text-xs font-bold">PDF[cite: 10]</span></td>
                                    <td class="py-4 px-4 text-gray-500 text-xs">245 Ko[cite: 10]</td>
                                    <td class="py-4 px-4 text-gray-500 text-xs">15/09/2024[cite: 10]</td>
                                    <td class="py-4 px-4 text-right space-x-3">
                                        <button class="text-gray-400 hover:text-blue-600 transition" title="Modifier"><i class="fa-regular fa-pen-to-square"></i></button>
                                        <button class="text-gray-400 hover:text-emerald-600 transition" title="Télécharger"><i class="fa-solid fa-download"></i></button>
                                        <button class="text-gray-400 hover:text-red-600 transition" title="Supprimer"><i class="fa-regular fa-trash-can"></i></button>
                                    </td>
                                </tr>
                                <tr class="hover:bg-gray-50/80 transition">
                                    <td class="py-4 px-4 font-bold text-gray-900">Calendrier académique 2024-2025[cite: 10]</td>
                                    <td class="py-4 px-4"><span class="bg-gray-100 text-gray-700 px-2.5 py-1 rounded-md text-xs font-medium">Administratif[cite: 10]</span></td>
                                    <td class="py-4 px-4"><span class="bg-red-50 text-red-600 px-2 py-0.5 rounded text-xs font-bold">PDF[cite: 10]</span></td>
                                    <td class="py-4 px-4 text-gray-500 text-xs">180 Ko[cite: 10]</td>
                                    <td class="py-4 px-4 text-gray-500 text-xs">10/09/2024[cite: 10]</td>
                                    <td class="py-4 px-4 text-right space-x-3">
                                        <button class="text-gray-400 hover:text-blue-600 transition" title="Modifier"><i class="fa-regular fa-pen-to-square"></i></button>
                                        <button class="text-gray-400 hover:text-emerald-600 transition" title="Télécharger"><i class="fa-solid fa-download"></i></button>
                                        <button class="text-gray-400 hover:text-red-600 transition" title="Supprimer"><i class="fa-regular fa-trash-can"></i></button>
                                    </td>
                                </tr>
                                <tr class="hover:bg-gray-50/80 transition">
                                    <td class="py-4 px-4 font-bold text-gray-900">Guide de l'étudiant[cite: 10]</td>
                                    <td class="py-4 px-4"><span class="bg-gray-100 text-gray-700 px-2.5 py-1 rounded-md text-xs font-medium">Pédagogie[cite: 10]</span></td>
                                    <td class="py-4 px-4"><span class="bg-red-50 text-red-600 px-2 py-0.5 rounded text-xs font-bold">PDF[cite: 10]</span></td>
                                    <td class="py-4 px-4 text-gray-500 text-xs">1,2 mois[cite: 10]</td>
                                    <td class="py-4 px-4 text-gray-500 text-xs">01/09/2024[cite: 10]</td>
                                    <td class="py-4 px-4 text-right space-x-3">
                                        <button class="text-gray-400 hover:text-blue-600 transition" title="Modifier"><i class="fa-regular fa-pen-to-square"></i></button>
                                        <button class="text-gray-400 hover:text-emerald-600 transition" title="Télécharger"><i class="fa-solid fa-download"></i></button>
                                        <button class="text-gray-400 hover:text-red-600 transition" title="Supprimer"><i class="fa-regular fa-trash-can"></i></button>
                                    </td>
                                </tr>
                                <tr class="hover:bg-gray-50/80 transition">
                                    <td class="py-4 px-4 font-bold text-gray-900">Syllabus Algorithmique[cite: 10]</td>
                                    <td class="py-4 px-4"><span class="bg-gray-100 text-gray-700 px-2.5 py-1 rounded-md text-xs font-medium">Cours[cite: 10]</span></td>
                                    <td class="py-4 px-4"><span class="bg-blue-50 text-blue-600 px-2 py-0.5 rounded text-xs font-bold">DOCX[cite: 10]</span></td>
                                    <td class="py-4 px-4 text-gray-500 text-xs">320 Ko[cite: 10]</td>
                                    <td class="py-4 px-4 text-gray-500 text-xs">05/09/2024[cite: 10]</td>
                                    <td class="py-4 px-4 text-right space-x-3">
                                        <button class="text-gray-400 hover:text-blue-600 transition" title="Modifier"><i class="fa-regular fa-pen-to-square"></i></button>
                                        <button class="text-gray-400 hover:text-emerald-600 transition" title="Télécharger"><i class="fa-solid fa-download"></i></button>
                                        <button class="text-gray-400 hover:text-red-600 transition" title="Supprimer"><i class="fa-regular fa-trash-can"></i></button>
                                    </td>
                                </tr>
                                <tr class="hover:bg-gray-50/80 transition">
                                    <td class="py-4 px-4 font-bold text-gray-900">Syllabus Base de données[cite: 10]</td>
                                    <td class="py-4 px-4"><span class="bg-gray-100 text-gray-700 px-2.5 py-1 rounded-md text-xs font-medium">Cours[cite: 10]</span></td>
                                    <td class="py-4 px-4"><span class="bg-blue-50 text-blue-600 px-2 py-0.5 rounded text-xs font-bold">DOCX[cite: 10]</span></td>
                                    <td class="py-4 px-4 text-gray-500 text-xs">280 Ko[cite: 10]</td>
                                    <td class="py-4 px-4 text-gray-500 text-xs">05/09/2024[cite: 10]</td>
                                    <td class="py-4 px-4 text-right space-x-3">
                                        <button class="text-gray-400 hover:text-blue-600 transition" title="Modifier"><i class="fa-regular fa-pen-to-square"></i></button>
                                        <button class="text-gray-400 hover:text-emerald-600 transition" title="Télécharger"><i class="fa-solid fa-download"></i></button>
                                        <button class="text-gray-400 hover:text-red-600 transition" title="Supprimer"><i class="fa-regular fa-trash-can"></i></button>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pied de tableau : Pagination -->
                <div class="flex justify-between items-center pt-4 border-t border-gray-100 text-xs text-gray-500">
                    <span>1 – 5 sur 6[cite: 10]</span>
                    <div class="flex items-center space-x-3">
                        <span>Page 1 sur 2[cite: 10]</span>
                        <div class="flex space-x-1">
                            <button class="w-7 h-7 flex items-center justify-center border border-gray-200 rounded text-gray-300 cursor-not-allowed"><i class="fa-solid fa-chevron-left text-[10px]"></i></button>
                            <button class="w-7 h-7 flex items-center justify-center border border-gray-200 rounded text-gray-700 hover:bg-gray-50 transition"><i class="fa-solid fa-chevron-right text-[10px]"></i></button>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </main>

    <!-- MODALE / POP-UP D'AJOUT DE DOCUMENT -->
    <div id="modal-container" class="fixed inset-0 z-50 flex items-center justify-center hidden">
        <div id="modal-backdrop" class="fixed inset-0 bg-black/50 opacity-0 transition-opacity duration-300" onclick="toggleModal(false)"></div>

        <div id="modal-box" class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 p-6 z-10 opacity-0 scale-95 transition-all duration-300 space-y-4">
            
            <div class="flex justify-between items-center border-b border-gray-100 pb-3">
                <h3 class="font-bold text-base text-gray-800">Ajouter un document</h3>
                <button onclick="toggleModal(false)" class="text-gray-400 hover:text-gray-600 text-xl font-bold">&times;</button>
            </div>

            <form action="documents.php" method="POST" class="space-y-3">
                <input type="hidden" name="action" value="ajouter_document">
                
                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Nom du document</label>
                    <input type="text" name="nom_document" required placeholder="ex: Emploi du temps L1" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600">
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Catégorie</label>
                        <select name="categorie" required class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm bg-white focus:outline-none focus:border-red-600">
                            <option value="Administratif">Administratif</option>
                            <option value="Pédagogie">Pédagogie</option>
                            <option value="Cours">Cours</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Format (Taper)</label>
                        <select name="type_fichier" required class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm bg-white focus:outline-none focus:border-red-600">
                            <option value="PDF">PDF</option>
                            <option value="DOCX">DOCX</option>
                            <option value="XLSX">XLSX</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Taille estimée</label>
                    <input type="text" name="taille" placeholder="ex: 350 Ko" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600">
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