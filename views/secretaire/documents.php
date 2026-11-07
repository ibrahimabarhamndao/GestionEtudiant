<?php
$pageActuelle = 'documents'; 
$titrePage = 'Documents'; 
$descriptionPage = 'Gestion des documents et ressources'; 
require_once '../../config/db.php';

$message = "";

// Traitement de l'ajout d'un document avec upload de fichier
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'ajouter_document') {
    $nom_document = trim($_POST['nom_document']);
    $type_fichier = trim($_POST['type_fichier']);
    $categorie = trim($_POST['categorie']);
    $taille_saisie = trim($_POST['taille']);

    if (!empty($nom_document) && !empty($categorie)) {
        try {
            // Création de la table si elle n'existe pas
            $pdo->exec("CREATE TABLE IF NOT EXISTS documents (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nom_document VARCHAR(255) NOT NULL,
                categorie VARCHAR(100) NOT NULL,
                type_fichier VARCHAR(20) NOT NULL,
                taille VARCHAR(50) DEFAULT '1,2 Mo',
                chemin_fichier VARCHAR(255) DEFAULT '',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");

            $cheminFichierDestination = "";

            // Gestion de l'upload réel du fichier
            if (isset($_FILES['fichier']) && $_FILES['fichier']['error'] === UPLOAD_ERR_OK) {
                $fileTmpPath = $_FILES['fichier']['tmp_name'];
                $fileName = $_FILES['fichier']['name'];
                $fileSize = $_FILES['fichier']['size'];
                
                // Calcul de la taille en Ko ou Mo si non saisie
                if (empty($taille_saisie)) {
                    $taille_saisie = ($fileSize > 1048576) ? round($fileSize / 1048576, 1) . ' Mo' : round($fileSize / 1024) . ' Ko';
                }

                $uploadFileDir = '../../uploads/';
                if (!is_dir($uploadFileDir)) {
                    mkdir($uploadFileDir, 0755, true);
                }

                $dest_path = $uploadFileDir . time() . '_' . $fileName;
                if(move_uploaded_file($fileTmpPath, $dest_path)) {
                    $cheminFichierDestination = $dest_path;
                }
            } else {
                $taille_saisie = !empty($taille_saisie) ? $taille_saisie : '500 Ko';
            }

            $stmt = $pdo->prepare("INSERT INTO documents (nom_document, categorie, type_fichier, taille, chemin_fichier) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$nom_document, $categorie, $type_fichier, $taille_saisie, $cheminFichierDestination]);
            
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

// Données de démonstration conformes à la maquette 
if (count($documents) === 0) {
    $documents = [
        ['nom_document' => 'Règlement intérieur 2024-2025',        'categorie' => 'Administratif', 'type_fichier' => 'PDF',  'taille' => '245 Ko', 'created_at' => '01/09/2024', 'chemin_fichier' => ''],
        ['nom_document' => 'Calendrier académique 2024-2025',      'categorie' => 'Administratif', 'type_fichier' => 'PDF',  'taille' => '45 Ko',  'created_at' => '15/09/2024', 'chemin_fichier' => ''],
        ['nom_document' => 'Guide de l\'étudiant',                 'categorie' => 'Pédagogique',   'type_fichier' => 'PDF',  'taille' => '145 Ko', 'created_at' => '25/09/2024', 'chemin_fichier' => ''],
        ['nom_document' => 'Syllabus Algorithmique',               'categorie' => 'Cours',         'type_fichier' => 'DOCX', 'taille' => '245 Ko', 'created_at' => '05/09/2024', 'chemin_fichier' => ''],
        ['nom_document' => 'Syllabus Base de données',             'categorie' => 'Administratif', 'type_fichier' => 'DOCX', 'taille' => '245 Ko', 'created_at' => '13/09/2024', 'chemin_fichier' => ''],
        ['nom_document' => 'Formulaire attestation de scolarité',  'categorie' => 'Administratif', 'type_fichier' => 'PDF',  'taille' => '245 Ko', 'created_at' => '22/09/2024', 'chemin_fichier' => ''],
    ];
}
$total = count($documents);

// Date au format JJ/MM/AAAA (accepte une date SQL ou une date déjà formatée)
function fmtDate($d) {
    if (empty($d)) return '-';
    if (preg_match('/^\d{4}-\d{2}-\d{2}/', $d)) return date('d/m/Y', strtotime($d));
    return $d;
}

// Couleur du badge de type : PDF en rouge, autres (DOCX...) en bleu
function classeType($type) {
    return (strtoupper($type) === 'PDF') ? 'bg-red-50 text-red-600' : 'bg-blue-50 text-blue-600';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
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

            <!-- Barre d'outils : Recherche (gauche) + Bouton Ajouter (droite) -->
            <div class="flex items-center justify-between">
                <div class="relative w-80">
                    <input type="text" placeholder="Rechercher un document..." class="w-full pl-4 pr-10 py-2.5 bg-white border border-gray-200 rounded-lg text-sm text-gray-700 placeholder-gray-400 focus:outline-none focus:border-red-600 transition">
                    <span class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-gray-800 pointer-events-none">
                        <i class="fa-solid fa-magnifying-glass text-sm"></i>
                    </span>
                </div>
                <button onclick="toggleDrawer(true)" class="bg-red-600 text-white px-5 py-2.5 rounded-lg text-sm font-semibold hover:bg-red-700 transition flex items-center space-x-2 shadow-sm">
                    <span>Ajouter un document</span>
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
                                <th class="py-4 px-6 font-semibold text-left">Nom du document</th>
                                <th class="py-4 px-4 font-semibold">Catégorie</th>
                                <th class="py-4 px-4 font-semibold">Type</th>
                                <th class="py-4 px-4 font-semibold">Taille</th>
                                <th class="py-4 px-4 font-semibold">Date</th>
                                <th class="py-4 px-6 font-semibold">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-sm">
                            <?php foreach ($documents as $d): ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="py-4 px-6 text-left font-medium text-gray-900"><?= htmlspecialchars($d['nom_document']) ?></td>
                                <td class="py-4 px-4">
                                    <span class="inline-block bg-gray-100 text-gray-600 px-2.5 py-1 rounded-full text-[11px] font-medium"><?= htmlspecialchars($d['categorie']) ?></span>
                                </td>
                                <td class="py-4 px-4">
                                    <span class="inline-block <?= classeType($d['type_fichier']) ?> px-2.5 py-1 rounded-full text-[11px] font-semibold"><?= htmlspecialchars($d['type_fichier']) ?></span>
                                </td>
                                <td class="py-4 px-4 text-xs text-slate-500"><?= htmlspecialchars($d['taille']) ?></td>
                                <td class="py-4 px-4 text-xs text-slate-500"><?= htmlspecialchars(fmtDate($d['created_at'])) ?></td>
                                <td class="py-4 px-6 space-x-3 whitespace-nowrap">
                                    <?php if (!empty($d['chemin_fichier'])): ?>
                                        <a href="<?= htmlspecialchars($d['chemin_fichier']) ?>" download class="text-slate-400 hover:text-emerald-600 transition" title="Télécharger"><i class="fa-solid fa-download"></i></a>
                                    <?php else: ?>
                                        <button class="text-slate-400 hover:text-emerald-600 transition" title="Télécharger"><i class="fa-solid fa-download"></i></button>
                                    <?php endif; ?>
                                    <button class="text-slate-600 hover:text-red-600 transition" title="Supprimer"><i class="fa-regular fa-trash-can"></i></button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- VUE CARTES -->
                <div id="view-cards" class="hidden p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 items-start">
                        <?php foreach ($documents as $d): ?>
                        <div class="bg-white border border-gray-100 rounded-xl shadow-md p-4">
                            <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                                <h4 class="text-sm font-medium text-gray-900 pr-3"><?= htmlspecialchars($d['nom_document']) ?></h4>
                                <div class="flex items-center space-x-3 text-slate-500 shrink-0">
                                    <button class="hover:text-blue-600 transition" title="Modifier"><i class="fa-solid fa-pen text-xs"></i></button>
                                    <?php if (!empty($d['chemin_fichier'])): ?>
                                        <a href="<?= htmlspecialchars($d['chemin_fichier']) ?>" download class="hover:text-emerald-600 transition" title="Télécharger"><i class="fa-solid fa-download text-xs"></i></a>
                                    <?php else: ?>
                                        <button class="hover:text-emerald-600 transition" title="Télécharger"><i class="fa-solid fa-download text-xs"></i></button>
                                    <?php endif; ?>
                                    <button class="hover:text-red-600 transition" title="Supprimer"><i class="fa-regular fa-trash-can text-xs"></i></button>
                                </div>
                            </div>
                            <div class="pt-3 space-y-3">
                                <div>
                                    <p class="text-xs text-slate-500 mb-1">Catégorie</p>
                                    <span class="inline-block bg-gray-100 text-gray-600 px-2.5 py-1 rounded-full text-[11px] font-medium"><?= htmlspecialchars($d['categorie']) ?></span>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-500 mb-1">Type</p>
                                    <span class="inline-block <?= classeType($d['type_fichier']) ?> px-2.5 py-1 rounded-full text-[11px] font-semibold"><?= htmlspecialchars($d['type_fichier']) ?></span>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-500">Taille</p>
                                    <p class="text-xs text-slate-600"><?= htmlspecialchars($d['taille']) ?></p>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-500">Date</p>
                                    <p class="text-xs text-slate-600"><?= htmlspecialchars(fmtDate($d['created_at'])) ?></p>
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

    <!-- DRAWER LATÉRAL D'AJOUT DE DOCUMENT -->
    <div id="drawer-overlay" class="fixed inset-0 bg-black/50 z-50 hidden transition-opacity opacity-0" onclick="toggleDrawer(false)"></div>
    
    <div id="drawer" class="fixed top-0 right-0 h-full w-[420px] bg-white shadow-2xl z-50 transform translate-x-full transition-transform duration-300 ease-in-out flex flex-col justify-between overflow-y-auto">
        <div>
            <div class="p-6 border-b border-gray-100 flex justify-between items-center sticky top-0 bg-white z-10">
                <h3 class="font-bold text-base text-gray-800">Ajouter un document</h3>
                <button onclick="toggleDrawer(false)" class="text-gray-400 hover:text-gray-600 text-xl font-bold">&times;</button>
            </div>

            <form action="documents.php" method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
                <input type="hidden" name="action" value="ajouter_document">
                
                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Nom du document</label>
                    <input type="text" name="nom_document"  placeholder="ex: Règlement intérieur 2025" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Type</label>
                    <input type="text" name="type_fichier"  placeholder="PDF" value="PDF" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Catégorie</label>
                    <select name="categorie"  class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm bg-white focus:outline-none focus:border-red-600">
                        <option value="Administratif">Administratif</option>
                        <option value="Pédagogique">Pédagogique</option>
                        <option value="Cours">Cours</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Taille</label>
                    <input type="text" name="taille" placeholder="ex: 1,2 Mo" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Fichier</label>
                    <div class="border-2 border-dashed border-gray-200 rounded-xl p-6 text-center cursor-pointer hover:border-red-500 transition relative bg-gray-50/50">
                        <input type="file" name="fichier" class="absolute inset-0 opacity-0 cursor-pointer" onchange="updateFileName(this)">
                        <div id="file-label" class="flex flex-col items-center space-y-2 text-gray-400 text-xs">
                            <i class="fa-solid fa-download text-xl text-gray-500 mb-1"></i>
                            <span class="font-medium text-gray-700">Cliquez pour sélectionner un fichier</span>
                            <span class="text-gray-400 text-[11px]">PDF, DOCX, XLSX - Max 10 Mo</span>
                        </div>
                    </div>
                </div>

                <div class="pt-4 flex items-center justify-end space-x-3 border-t border-gray-100">
                    <button type="button" onclick="toggleDrawer(false)" class="px-4 py-2.5 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition">
                        Annuler
                    </button>
                    <button type="submit" class="px-4 py-2.5 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition shadow-sm">
                        Ajouter le document
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

        function updateFileName(input) {
            const label = document.getElementById('file-label');
            if (input.files && input.files[0]) {
                label.innerHTML = `<i class="fa-solid fa-file-circle-check text-2xl text-emerald-600 mb-1"></i><span class="font-bold text-gray-800">${input.files[0].name}</span><span class="text-emerald-600 text-[11px]">Fichier prêt à être uploadé</span>`;
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