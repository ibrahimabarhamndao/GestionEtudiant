<?php
$pageActuelle = 'classes'; 
$titrePage = 'Cours'; 
$descriptionPage = 'Organisez les classes par filière et niveau'; 
require_once '../../config/db.php';

// Gestion de l'ajout d'une classe
$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'ajouter') {
    $code_classe = trim($_POST['code_classe']);
    $nom = trim($_POST['nom']);
    $capacite = trim($_POST['capacite']);
    $filiere_id = trim($_POST['filiere_id']);
    $niveau_id = trim($_POST['niveau_id']);

    if (!empty($code_classe) && !empty($nom)) {
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS classes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                code_classe VARCHAR(50) UNIQUE NOT NULL,
                nom VARCHAR(150) NOT NULL,
                capacite INT DEFAULT 30,
                filiere_id INT,
                niveau_id INT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");

            $stmt = $pdo->prepare("INSERT INTO classes (code_classe, nom, capacite, filiere_id, niveau_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$code_classe, $nom, $capacite, $filiere_id, $niveau_id]);
            header("Location: classes.php");
            exit();
        } catch (PDOException $e) {
            $message = "Erreur : Ce code de classe existe déjà.";
        }
    } else {
        $message = "Veuillez remplir les champs obligatoires.";
    }
}

// Récupération des classes
$classes = [];
try {
    $classes = $pdo->query("SELECT * FROM classes ORDER BY id DESC")->fetchAll();
} catch (PDOException $e) {
    $classes = [];
}

// Récupération des filières et niveaux pour le formulaire d'ajout
$filieres = $pdo->query("SELECT * FROM filieres ORDER BY nom ASC")->fetchAll();
$niveaux = $pdo->query("SELECT * FROM niveaux ORDER BY libelle ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Administration — Classes</title>
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
                    <input type="text" placeholder="Rechercher une classe..." class="w-full pl-10 pr-4 py-2.5 bg-white border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600 transition shadow-2xs">
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

                <!-- Tableau des Classes / Cours -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="text-gray-400 text-[11px] uppercase tracking-wider border-b border-gray-100">
                                <th class="py-3 px-4 font-semibold">Classe de code</th>
                                <th class="py-3 px-4 font-semibold">Nom</th>
                                <th class="py-3 px-4 font-semibold">Capacité</th>
                                <th class="py-3 px-4 font-semibold">Filière</th>
                                <th class="py-3 px-4 font-semibold">Niveau</th>
                                <th class="py-3 px-4 font-semibold text-right">Actes</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-sm text-gray-600">
                            <?php if (count($classes) > 0): ?>
                                <?php foreach ($classes as $c): ?>
                                <tr class="hover:bg-gray-50/80 transition">
                                    <td class="py-4 px-4">
                                        <span class="bg-gray-100 text-gray-700 px-2.5 py-1 rounded-md text-xs font-medium"><?= htmlspecialchars($c['code_classe']) ?></span>
                                    </td>
                                    <td class="py-4 px-4 font-medium text-gray-900"><?= htmlspecialchars($c['nom']) ?></td>
                                    <td class="py-4 px-4 text-gray-600"><?= htmlspecialchars($c['capacite']) ?> places</td>
                                    <td class="py-4 px-4">
                                        <span class="bg-blue-50 text-blue-600 px-2.5 py-1 rounded-md text-xs font-medium">INFO</span>
                                    </td>
                                    <td class="py-4 px-4 text-gray-500 text-xs">INFO-L1</td>
                                    <td class="py-4 px-4 text-right space-x-3">
                                        <button class="text-gray-400 hover:text-emerald-600 transition" title="Voir"><i class="fa-regular fa-eye"></i></button>
                                        <button class="text-gray-400 hover:text-blue-600 transition" title="Modifier"><i class="fa-regular fa-pen-to-square"></i></button>
                                        <button class="text-gray-400 hover:text-red-600 transition" title="Supprimer"><i class="fa-regular fa-trash-can"></i></button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <!-- Données exactes issues de votre maquette[cite: 8] -->
                                <tr class="hover:bg-gray-50/80 transition">
                                    <td class="py-4 px-4"><span class="bg-gray-100 text-gray-700 px-2.5 py-1 rounded-md text-xs font-medium">INFO-L1-A</span></td>
                                    <td class="py-4 px-4 font-medium text-gray-900">Informatique L1 Groupe A[cite: 8]</td>
                                    <td class="py-4 px-4 text-gray-600">35 places[cite: 8]</td>
                                    <td class="py-4 px-4"><span class="bg-blue-50 text-blue-600 px-2.5 py-1 rounded-md text-xs font-medium">INFO[cite: 8]</span></td>
                                    <td class="py-4 px-4 text-gray-500 text-xs font-medium">INFO-L1[cite: 8]</td>
                                    <td class="py-4 px-4 text-right space-x-3">
                                        <button class="text-gray-400 hover:text-emerald-600 transition"><i class="fa-regular fa-eye"></i></button>
                                        <button class="text-gray-400 hover:text-blue-600 transition"><i class="fa-regular fa-pen-to-square"></i></button>
                                        <button class="text-gray-400 hover:text-red-600 transition"><i class="fa-regular fa-trash-can"></i></button>
                                    </td>
                                </tr>
                                <tr class="hover:bg-gray-50/80 transition">
                                    <td class="py-4 px-4"><span class="bg-gray-100 text-gray-700 px-2.5 py-1 rounded-md text-xs font-medium">INFO-L2-A</span></td>
                                    <td class="py-4 px-4 font-medium text-gray-900">Informatique L2 Groupe A[cite: 8]</td>
                                    <td class="py-4 px-4 text-gray-600">30 places[cite: 8]</td>
                                    <td class="py-4 px-4"><span class="bg-blue-50 text-blue-600 px-2.5 py-1 rounded-md text-xs font-medium">INFO[cite: 8]</span></td>
                                    <td class="py-4 px-4 text-gray-500 text-xs font-medium">INFO-L2[cite: 8]</td>
                                    <td class="py-4 px-4 text-right space-x-3">
                                        <button class="text-gray-400 hover:text-emerald-600 transition"><i class="fa-regular fa-eye"></i></button>
                                        <button class="text-gray-400 hover:text-blue-600 transition"><i class="fa-regular fa-pen-to-square"></i></button>
                                        <button class="text-gray-400 hover:text-red-600 transition"><i class="fa-regular fa-trash-can"></i></button>
                                    </td>
                                </tr>
                                <tr class="hover:bg-gray-50/80 transition">
                                    <td class="py-4 px-4"><span class="bg-gray-100 text-gray-700 px-2.5 py-1 rounded-md text-xs font-medium">GEST-L1-A</span></td>
                                    <td class="py-4 px-4 font-medium text-gray-900">Gestion L1 Groupe A[cite: 8]</td>
                                    <td class="py-4 px-4 text-gray-600">40 places[cite: 8]</td>
                                    <td class="py-4 px-4"><span class="bg-blue-50 text-blue-600 px-2.5 py-1 rounded-md text-xs font-medium">GEST[cite: 8]</span></td>
                                    <td class="py-4 px-4 text-gray-500 text-xs font-medium">GEST-L1[cite: 8]</td>
                                    <td class="py-4 px-4 text-right space-x-3">
                                        <button class="text-gray-400 hover:text-emerald-600 transition"><i class="fa-regular fa-eye"></i></button>
                                        <button class="text-gray-400 hover:text-blue-600 transition"><i class="fa-regular fa-pen-to-square"></i></button>
                                        <button class="text-gray-400 hover:text-red-600 transition"><i class="fa-regular fa-trash-can"></i></button>
                                    </td>
                                </tr>
                                <tr class="hover:bg-gray-50/80 transition">
                                    <td class="py-4 px-4"><span class="bg-gray-100 text-gray-700 px-2.5 py-1 rounded-md text-xs font-medium">MARQUE-L1-A</span></td>
                                    <td class="py-4 px-4 font-medium text-gray-900">Marketing L1 Groupe A[cite: 8]</td>
                                    <td class="py-4 px-4 text-gray-600">30 places[cite: 8]</td>
                                    <td class="py-4 px-4"><span class="bg-blue-50 text-blue-600 px-2.5 py-1 rounded-md text-xs font-medium">MARQUE[cite: 8]</span></td>
                                    <td class="py-4 px-4 text-gray-500 text-xs font-medium">MARQUE-L1[cite: 8]</td>
                                    <td class="py-4 px-4 text-right space-x-3">
                                        <button class="text-gray-400 hover:text-emerald-600 transition"><i class="fa-regular fa-eye"></i></button>
                                        <button class="text-gray-400 hover:text-blue-600 transition"><i class="fa-regular fa-pen-to-square"></i></button>
                                        <button class="text-gray-400 hover:text-red-600 transition"><i class="fa-regular fa-trash-can"></i></button>
                                    </td>
                                </tr>
                                <tr class="hover:bg-gray-50/80 transition">
                                    <td class="py-4 px-4"><span class="bg-gray-100 text-gray-700 px-2.5 py-1 rounded-md text-xs font-medium">DROIT-M1-A</span></td>
                                    <td class="py-4 px-4 font-medium text-gray-900">Droit M1 Groupe A[cite: 8]</td>
                                    <td class="py-4 px-4 text-gray-600">25 places[cite: 8]</td>
                                    <td class="py-4 px-4"><span class="bg-blue-50 text-blue-600 px-2.5 py-1 rounded-md text-xs font-medium">DROIT[cite: 8]</span></td>
                                    <td class="py-4 px-4 text-gray-500 text-xs font-medium">DROIT-M1[cite: 8]</td>
                                    <td class="py-4 px-4 text-right space-x-3">
                                        <button class="text-gray-400 hover:text-emerald-600 transition"><i class="fa-regular fa-eye"></i></button>
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
                    <span>1 – 5 sur 5[cite: 8]</span>
                    <div class="flex items-center space-x-3">
                        <span>Page 1 sur 1[cite: 8]</span>
                        <div class="flex space-x-1">
                            <button class="w-7 h-7 flex items-center justify-center border border-gray-200 rounded text-gray-300 cursor-not-allowed"><i class="fa-solid fa-chevron-left text-[10px]"></i></button>
                            <button class="w-7 h-7 flex items-center justify-center border border-gray-200 rounded text-gray-300 cursor-not-allowed"><i class="fa-solid fa-chevron-right text-[10px]"></i></button>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </main>

    <!-- MODALE / POP-UP D'AJOUT DE CLASSE -->
    <div id="modal-container" class="fixed inset-0 z-50 flex items-center justify-center hidden">
        <div id="modal-backdrop" class="fixed inset-0 bg-black/50 opacity-0 transition-opacity duration-300" onclick="toggleModal(false)"></div>

        <div id="modal-box" class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 p-6 z-10 opacity-0 scale-95 transition-all duration-300 space-y-4">
            
            <div class="flex justify-between items-center border-b border-gray-100 pb-3">
                <h3 class="font-bold text-base text-gray-800">Ajouter une classe</h3>
                <button onclick="toggleModal(false)" class="text-gray-400 hover:text-gray-600 text-xl font-bold">&times;</button>
            </div>

            <form action="classes.php" method="POST" class="space-y-4">
                <input type="hidden" name="action" value="ajouter">
                
                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Code Classe</label>
                    <input type="text" name="code_classe" required placeholder="ex: INFO-L1-A" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Nom de la classe</label>
                    <input type="text" name="nom" required placeholder="ex: Informatique L1 Groupe A" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Capacité (places)</label>
                    <input type="number" name="capacite" value="30" required class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600">
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