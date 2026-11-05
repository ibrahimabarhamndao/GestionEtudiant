<?php
$pageActuelle = 'documents';
$titrePage = 'Documents';
$descriptionPage = 'Organisez les documents administratifs et pédagogiques';
require_once '../../config/db.php';

$message = "";

// Traitement de l'ajout/upload d'un document
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'ajouter_document') {
    $titre = trim($_POST['titre']);
    $categorie = trim($_POST['categorie']);
    
    if (!empty($titre) && isset($_FILES['fichier']) && $_FILES['fichier']['error'] === 0) {
        $dossierUpload = '../../assets/uploads/';
        if (!is_dir($dossierUpload)) {
            mkdir($dossierUpload, 0777, true);
        }
        
        $nomOriginal = $_FILES['fichier']['name'];
        $extension = strtolower(pathinfo($nomOriginal, PATHINFO_EXTENSION));
        $tailleOctets = $_FILES['fichier']['size'];
        
        // Calcul de la taille lisible (Ko)
        $tailleKo = round($tailleOctets / 1024) . ' Ko';
        
        $nomFichier = time() . '_' . basename($nomOriginal);
        $cheminCible = $dossierUpload . $nomFichier;

        if (move_uploaded_file($_FILES['fichier']['tmp_name'], $cheminCible)) {
            try {
                // Vérification et création automatique de la table si besoin avec les nouveaux champs
                $pdo->exec("CREATE TABLE IF NOT EXISTS documents_centralises (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    titre VARCHAR(150) NOT NULL,
                    categorie VARCHAR(50) NOT NULL,
                    type VARCHAR(20) NOT NULL,
                    taille VARCHAR(20) NOT NULL,
                    chemin VARCHAR(255) NOT NULL,
                    created_at DATE NOT NULL
                )");

                $dateJour = date('Y-m-d');
                $stmt = $pdo->prepare("INSERT INTO documents_centralises (titre, categorie, type, taille, chemin, created_at) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$titre, $categorie, strtoupper($extension), $tailleKo, $nomFichier, $dateJour]);
                header("Location: documents.php");
                exit();
            } catch (PDOException $e) {
                $message = "Erreur lors de l'enregistrement en base de données.";
            }
        } else {
            $message = "Erreur lors du téléchargement du fichier.";
        }
    } else {
        $message = "Veuillez remplir tous les champs et sélectionner un fichier valide.";
    }
}

// Récupération des documents
$documents = [];
try {
    $documents = $pdo->query("SELECT * FROM documents_centralises ORDER BY id DESC")->fetchAll();
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
</head>
<body class="bg-gray-50 flex h-screen overflow-hidden">

    <!-- Sidebar Unifiée -->
    <?php include '../../includes/sidebar_secretaire.php'; ?>

    <!-- Contenu Principal -->
    <main class="flex-1 flex flex-col h-screen overflow-hidden">
        
        <!-- Header Supérieur Dynamique -->
        <?php include '../../includes/header.php'; ?>

        <!-- Corps de la page -->
        <div class="flex-1 p-8 overflow-y-auto space-y-6">
            
            <!-- Barre de recherche et Boutons d'action / Vues -->
            <div class="flex justify-between items-center">
                <div class="relative w-96">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">🔍</span>
                    <input type="text" placeholder="Rechercher un document..." class="w-full pl-10 pr-4 py-2 bg-white border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600">
                </div>
                <div class="flex items-center space-x-3">
                    <div class="bg-white border border-gray-200 rounded-lg p-1 flex text-xs">
                        <button class="px-3 py-1 bg-red-50 text-red-600 font-semibold rounded flex items-center space-x-1">
                            <span>📊</span> <span>Tableau</span>
                        </button>
                        <button class="px-3 py-1 text-gray-500 hover:text-gray-800 flex items-center space-x-1">
                            <span>📑</span> <span>Cartes</span>
                        </button>
                    </div>
                    <button onclick="toggleDrawer(true)" class="bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-red-700 transition flex items-center space-x-1 shadow-sm">
                        <span>Ajouter un document</span> <span class="font-bold">+</span>
                    </button>
                </div>
            </div>

            <?php if (!empty($message)): ?>
                <div class="p-4 bg-red-50 border-l-4 border-red-500 text-red-700 text-sm rounded">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <!-- Tableau des Documents (Conforme à la maquette) -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 text-gray-400 text-xs uppercase tracking-wider border-b border-gray-100">
                            <th class="p-4 font-semibold">Nom du document</th>
                            <th class="p-4 font-semibold">Catégorie</th>
                            <th class="p-4 font-semibold">Type</th>
                            <th class="p-4 font-semibold">Taille</th>
                            <th class="p-4 font-semibold">Date</th>
                            <th class="p-4 font-semibold text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm text-gray-600">
                        <?php if (count($documents) > 0): ?>
                            <?php foreach ($documents as $doc): ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="p-4 font-semibold text-gray-800 flex items-center space-x-3">
                                    <span class="text-xl">📄</span>
                                    <span><?= htmlspecialchars($doc['titre']) ?></span>
                                </td>
                                <td class="p-4">
                                    <span class="bg-gray-100 text-gray-600 px-2.5 py-1 rounded-md text-xs font-medium">
                                        <?= htmlspecialchars($doc['categorie']) ?>
                                    </span>
                                </td>
                                <td class="p-4">
                                    <?php 
                                    $badgeColor = 'bg-red-50 text-red-600';
                                    if ($doc['type'] == 'DOCX') $badgeColor = 'bg-blue-50 text-blue-600';
                                    ?>
                                    <span class="<?= $badgeColor ?> px-2.5 py-1 rounded-md text-xs font-bold">
                                        <?= htmlspecialchars($doc['type']) ?>
                                    </span>
                                </td>
                                <td class="p-4 text-gray-500 font-medium text-xs"><?= htmlspecialchars($doc['taille']) ?></td>
                                <td class="p-4 text-gray-500 text-xs">
                                    <?= date('d/m/Y', strtotime($doc['created_at'])) ?>
                                </td>
                                <td class="p-4 text-right space-x-3">
                                    <?php if (!empty($doc['chemin'])): ?>
                                        <a href="../../assets/uploads/<?= htmlspecialchars($doc['chemin']) ?>" target="_blank" class="text-gray-400 hover:text-green-600 transition" title="Télécharger">📥</a>
                                    <?php endif; ?>
                                    <button class="text-gray-400 hover:text-red-600 transition" title="Supprimer">🗑️</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <!-- Exemple statique de démonstration si la base est vide pour refléter fidèlement la maquette -->
                            <tr class="hover:bg-gray-50 transition">
                                <td class="p-4 font-semibold text-gray-800 flex items-center space-x-3">
                                    <span class="text-xl">📄</span>
                                    <span>Règlement intérieur 2024-2025</span>
                                </td>
                                <td class="p-4"><span class="bg-gray-100 text-gray-600 px-2.5 py-1 rounded-md text-xs font-medium">Administratif</span></td>
                                <td class="p-4"><span class="bg-red-50 text-red-600 px-2.5 py-1 rounded-md text-xs font-bold">PDF</span></td>
                                <td class="p-4 text-gray-500 font-medium text-xs">245 Ko</td>
                                <td class="p-4 text-gray-500 text-xs">01/09/2024</td>
                                <td class="p-4 text-right space-x-3">
                                    <button class="text-gray-400 hover:text-green-600 transition" title="Télécharger">📥</button>
                                    <button class="text-gray-400 hover:text-red-600 transition" title="Supprimer">🗑️</button>
                                </td>
                            </tr>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="p-4 font-semibold text-gray-800 flex items-center space-x-3">
                                    <span class="text-xl">📄</span>
                                    <span>Calendrier académique 2024-2025</span>
                                </td>
                                <td class="p-4"><span class="bg-gray-100 text-gray-600 px-2.5 py-1 rounded-md text-xs font-medium">Administratif</span></td>
                                <td class="p-4"><span class="bg-red-50 text-red-600 px-2.5 py-1 rounded-md text-xs font-bold">PDF</span></td>
                                <td class="p-4 text-gray-500 font-medium text-xs">45 Ko</td>
                                <td class="p-4 text-gray-500 text-xs">15/09/2024</td>
                                <td class="p-4 text-right space-x-3">
                                    <button class="text-gray-400 hover:text-green-600 transition" title="Télécharger">📥</button>
                                    <button class="text-gray-400 hover:text-red-600 transition" title="Supprimer">🗑️</button>
                                </td>
                            </tr>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="p-4 font-semibold text-gray-800 flex items-center space-x-3">
                                    <span class="text-xl">📄</span>
                                    <span>Guide de l'étudiant</span>
                                </td>
                                <td class="p-4"><span class="bg-gray-100 text-gray-600 px-2.5 py-1 rounded-md text-xs font-medium">Pédagogique</span></td>
                                <td class="p-4"><span class="bg-red-50 text-red-600 px-2.5 py-1 rounded-md text-xs font-bold">PDF</span></td>
                                <td class="p-4 text-gray-500 font-medium text-xs">145 Ko</td>
                                <td class="p-4 text-gray-500 text-xs">25/09/2024</td>
                                <td class="p-4 text-right space-x-3">
                                    <button class="text-gray-400 hover:text-green-600 transition" title="Télécharger">📥</button>
                                    <button class="text-gray-400 hover:text-red-600 transition" title="Supprimer">🗑️</button>
                                </td>
                            </tr>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="p-4 font-semibold text-gray-800 flex items-center space-x-3">
                                    <span class="text-xl">📄</span>
                                    <span>Syllabus Algorithmique</span>
                                </td>
                                <td class="p-4"><span class="bg-gray-100 text-gray-600 px-2.5 py-1 rounded-md text-xs font-medium">Cours</span></td>
                                <td class="p-4"><span class="bg-blue-50 text-blue-600 px-2.5 py-1 rounded-md text-xs font-bold">DOCX</span></td>
                                <td class="p-4 text-gray-500 font-medium text-xs">245 Ko</td>
                                <td class="p-4 text-gray-500 text-xs">05/09/2024</td>
                                <td class="p-4 text-right space-x-3">
                                    <button class="text-gray-400 hover:text-green-600 transition" title="Télécharger">📥</button>
                                    <button class="text-gray-400 hover:text-red-600 transition" title="Supprimer">🗑️</button>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </main>

    <!-- DRAWER D'AJOUT DE DOCUMENT -->
    <div id="drawer-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden transition-opacity opacity-0" onclick="toggleDrawer(false)"></div>
    
    <div id="drawer" class="fixed top-0 right-0 h-full w-96 bg-white shadow-2xl z-50 transform translate-x-full transition-transform duration-300 ease-in-out flex flex-col justify-between">
        <div>
            <div class="p-6 border-b border-gray-100 flex justify-between items-center">
                <h3 class="font-bold text-lg text-gray-800">Ajouter un document</h3>
                <button onclick="toggleDrawer(false)" class="text-gray-400 hover:text-gray-600 text-xl font-bold">&times;</button>
            </div>

            <form action="documents.php" method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
                <input type="hidden" name="action" value="ajouter_document">
                
                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Nom du document</label>
                    <input type="text" name="titre" required placeholder="ex: Emploi du temps L1" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Catégorie</label>
                    <select name="categorie" required class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm bg-white focus:outline-none focus:border-red-600">
                        <option value="Administratif">Administratif</option>
                        <option value="Pédagogique">Pédagogique</option>
                        <option value="Cours">Cours</option>
                        <option value="Examen">Examen</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Fichier (PDF, DOCX, etc.)</label>
                    <input type="file" name="fichier" required class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm bg-white focus:outline-none focus:border-red-600 file:mr-4 file:py-1 file:px-4 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-red-50 file:text-red-700 hover:file:bg-red-100">
                </div>

                <div class="pt-4">
                    <button type="submit" class="w-full bg-red-600 text-white py-2.5 rounded-lg text-sm font-medium hover:bg-red-700 transition">
                        Importer et enregistrer
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