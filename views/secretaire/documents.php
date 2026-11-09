<?php
$pageActuelle = 'documents'; 
$titrePage = 'Documents'; 
$descriptionPage = 'Gestion des documents et ressources'; 
require_once '../../config/db.php';

$erreurs = [];
$old = [];
$recherche = trim($_GET['q'] ?? '');

// Gestion de la pagination (5 éléments par page)
$limiteParPage = 5;
$pageActuelleNum = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($pageActuelleNum < 1) $pageActuelleNum = 1;
$debut = ($pageActuelleNum - 1) * $limiteParPage;

// Traitement de l'ajout ou de la modification d'un document
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $actionPost = $_POST['action'];
    $id = $_POST['id'] ?? null;
    
    $nom_document = trim($_POST['nom_document'] ?? '');
    $type_fichier = trim($_POST['type_fichier'] ?? '');
    $categorie = trim($_POST['categorie'] ?? '');
    $taille_saisie = trim($_POST['taille'] ?? '');

    // Sauvegarde des anciennes valeurs pour les réafficher en cas d'erreur
    $old = $_POST;

    // Validation champ par champ
    if (empty($nom_document)) {
        $erreurs['nom_document'] = "Le nom du document est obligatoire.";
    }
    if (empty($type_fichier)) {
        $erreurs['type_fichier'] = "Le type de fichier est obligatoire (ex: PDF, DOCX).";
    }
    if (empty($categorie)) {
        $erreurs['categorie'] = "Veuillez sélectionner une catégorie.";
    }

    if (empty($erreurs)) {
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

            if ($actionPost === 'ajouter_document') {
                $stmt = $pdo->prepare("INSERT INTO documents (nom_document, categorie, type_fichier, taille, chemin_fichier) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$nom_document, $categorie, $type_fichier, $taille_saisie, $cheminFichierDestination]);
                header("Location: documents.php?success=1");
                exit();
            } elseif ($actionPost === 'modifier_document' && !empty($id)) {
                if (!empty($cheminFichierDestination)) {
                    $stmt = $pdo->prepare("UPDATE documents SET nom_document = ?, categorie = ?, type_fichier = ?, taille = ?, chemin_fichier = ? WHERE id = ?");
                    $stmt->execute([$nom_document, $categorie, $type_fichier, $taille_saisie, $cheminFichierDestination, $id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE documents SET nom_document = ?, categorie = ?, type_fichier = ?, taille = ? WHERE id = ?");
                    $stmt->execute([$nom_document, $categorie, $type_fichier, $taille_saisie, $id]);
                }
                header("Location: documents.php?success=2");
                exit();
            }
        } catch (PDOException $e) {
            $erreurs['general'] = "Erreur SQL : " . $e->getMessage();
        }
    }
}

// Récupération des documents avec recherche, comptage et pagination
$totalTotal = 0;
$documents = [];

try {
    if (!empty($recherche)) {
        $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM documents WHERE nom_document LIKE ? OR categorie LIKE ? OR type_fichier LIKE ?");
        $stmtCount->execute(["%$recherche%", "%$recherche%", "%$recherche%"]);
        $totalTotal = $stmtCount->fetchColumn();

        $stmt = $pdo->prepare("SELECT * FROM documents WHERE nom_document LIKE :q1 OR categorie LIKE :q2 OR type_fichier LIKE :q3 ORDER BY id DESC LIMIT :limite OFFSET :debut");
        $stmt->bindValue(':q1', "%$recherche%", PDO::PARAM_STR);
        $stmt->bindValue(':q2', "%$recherche%", PDO::PARAM_STR);
        $stmt->bindValue(':q3', "%$recherche%", PDO::PARAM_STR);
        $stmt->bindValue(':limite', $limiteParPage, PDO::PARAM_INT);
        $stmt->bindValue(':debut', $debut, PDO::PARAM_INT);
        $stmt->execute();
        $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $totalTotal = $pdo->query("SELECT COUNT(*) FROM documents")->fetchColumn();

        $stmt = $pdo->prepare("SELECT * FROM documents ORDER BY id DESC LIMIT :limite OFFSET :debut");
        $stmt->bindValue(':limite', $limiteParPage, PDO::PARAM_INT);
        $stmt->bindValue(':debut', $debut, PDO::PARAM_INT);
        $stmt->execute();
        $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $documents = [];
}

// Données de démonstration par défaut si la table est vide
$tousLesDocuments = [];
try {
    $tousLesDocuments = $pdo->query("SELECT * FROM documents ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $tousLesDocuments = [];
}

if (count($tousLesDocuments) === 0 && empty($recherche)) {
    $tousLesDocuments = [
        ['id' => 0, 'nom_document' => 'Règlement intérieur 2024-2025', 'categorie' => 'Administratif', 'type_fichier' => 'PDF', 'taille' => '245 Ko', 'created_at' => '2024-09-01', 'chemin_fichier' => ''],
        ['id' => 0, 'nom_document' => 'Calendrier académique 2024-2025', 'categorie' => 'Administratif', 'type_fichier' => 'PDF', 'taille' => '45 Ko', 'created_at' => '2024-09-15', 'chemin_fichier' => ''],
        ['id' => 0, 'nom_document' => 'Guide de l\'étudiant', 'categorie' => 'Pédagogique', 'type_fichier' => 'PDF', 'taille' => '145 Ko', 'created_at' => '2024-09-25', 'chemin_fichier' => ''],
        ['id' => 0, 'nom_document' => 'Syllabus Algorithmique', 'categorie' => 'Cours', 'type_fichier' => 'DOCX', 'taille' => '245 Ko', 'created_at' => '2024-09-05', 'chemin_fichier' => ''],
        ['id' => 0, 'nom_document' => 'Syllabus Base de données', 'categorie' => 'Administratif', 'type_fichier' => 'DOCX', 'taille' => '245 Ko', 'created_at' => '2024-09-13', 'chemin_fichier' => ''],
        ['id' => 0, 'nom_document' => 'Formulaire attestation de scolarité', 'categorie' => 'Administratif', 'type_fichier' => 'PDF', 'taille' => '245 Ko', 'created_at' => '2024-09-22', 'chemin_fichier' => ''],
    ];
    if (empty($documents)) {
        $documents = array_slice($tousLesDocuments, $debut, $limiteParPage);
    }
    $totalTotal = count($tousLesDocuments);
}

$totalPages = ceil($totalTotal / $limiteParPage);
if ($totalPages < 1) $totalPages = 1;
$debutAffichage = $totalTotal > 0 ? $debut + 1 : 0;
$finAffichage = min($debut + $limiteParPage, $totalTotal);

function fmtDate($d) {
    if (empty($d)) return '-';
    if (preg_match('/^\d{4}-\d{2}-\d{2}/', $d)) return date('d/m/Y', strtotime($d));
    return $d;
}

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

    <?php include '../../includes/sidebar_secretaire.php'; ?>

    <main class="flex-1 flex flex-col h-screen overflow-hidden">
        <?php include '../../includes/header.php'; ?>

        <div class="flex-1 p-8 overflow-y-auto space-y-6">
            
            <?php if (!empty($erreurs['general'])): ?>
                <div class="p-4 bg-red-50 border-l-4 border-red-500 text-red-700 text-sm rounded-lg shadow-sm">
                    <?= htmlspecialchars($erreurs['general']) ?>
                </div>
            <?php endif; ?>

            <!-- Barre d'outils : Recherche + Bouton Ajouter -->
            <div class="flex items-center justify-between">
                <form id="searchForm" action="documents.php" method="GET" class="relative w-80">
                    <input type="text" id="searchInput" name="q" value="<?= htmlspecialchars($recherche) ?>" placeholder="Rechercher un document..." class="w-full pl-4 pr-10 py-2.5 bg-white border border-gray-200 rounded-lg text-sm text-gray-700 placeholder-gray-400 focus:outline-none focus:border-red-600 transition" autocomplete="off">
                    <button type="submit" class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-gray-800 hover:text-red-600 cursor-pointer">
                        <i class="fa-solid fa-magnifying-glass text-sm"></i>
                    </button>
                </form>
                <button onclick="openAddDrawer()" class="bg-red-600 text-white px-5 py-2.5 rounded-lg text-sm font-semibold hover:bg-red-700 transition flex items-center space-x-2 shadow-sm cursor-pointer">
                    <span>Ajouter un document</span>
                    <i class="fa-solid fa-plus text-xs"></i>
                </button>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="flex justify-end px-4 py-3 border-b border-gray-100">
                    <div class="bg-gray-100 rounded-lg p-1 flex text-xs font-medium">
                        <button id="btn-table" type="button" onclick="setView('table')" class="px-3 py-1.5 rounded-md flex items-center space-x-1.5 transition bg-white text-red-600 shadow-sm cursor-pointer">
                            <i class="fa-solid fa-table"></i>
                            <span>Tableau</span>
                        </button>
                        <button id="btn-cards" type="button" onclick="setView('cards')" class="px-3 py-1.5 rounded-md flex items-center space-x-1.5 transition text-gray-500 hover:text-gray-800 cursor-pointer">
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
                            <?php if (empty($documents)): ?>
                                <tr><td colspan="6" class="py-6 text-gray-400 text-center text-xs">Aucun document trouvé.</td></tr>
                            <?php else: foreach ($documents as $d): 
                                $jsonDoc = json_encode($d, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
                            ?>
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
                                    <?php if (!empty($d['id'])): ?>
                                        <button type="button" onclick='openEditDrawer(<?= $jsonDoc ?>)' class="text-slate-500 hover:text-blue-600 transition cursor-pointer" title="Modifier"><i class="fa-solid fa-pen"></i></button>
                                        <?php if (!empty($d['chemin_fichier'])): ?>
                                            <a href="<?= htmlspecialchars($d['chemin_fichier']) ?>" download class="text-slate-500 hover:text-emerald-600 transition" title="Télécharger"><i class="fa-solid fa-download"></i></a>
                                        <?php else: ?>
                                            <button class="text-slate-300 cursor-not-allowed" title="Aucun fichier"><i class="fa-solid fa-download"></i></button>
                                        <?php endif; ?>
                                        <button type="button" onclick="openDeleteModalAsync(<?= $d['id'] ?>, '<?= htmlspecialchars($d['nom_document'], ENT_QUOTES) ?>', this.closest('tr'), 'supprimer_document')" class="text-slate-500 hover:text-red-600 transition cursor-pointer" title="Supprimer"><i class="fa-regular fa-trash-can"></i></button>
                                    <?php else: ?>
                                        <span class="text-xs text-gray-400">Défaut</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- VUE CARTES -->
                <div id="view-cards" class="hidden p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 items-start">
                        <?php foreach ($documents as $d): 
                            $jsonDoc = json_encode($d, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
                        ?>
                        <div class="bg-white border border-gray-100 rounded-xl shadow-md p-4">
                            <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                                <h4 class="text-sm font-medium text-gray-900 pr-3"><?= htmlspecialchars($d['nom_document']) ?></h4>
                                <div class="flex items-center space-x-3 text-slate-500 shrink-0">
                                    <?php if (!empty($d['id'])): ?>
                                        <button type="button" onclick='openEditDrawer(<?= $jsonDoc ?>)' class="hover:text-blue-600 transition cursor-pointer" title="Modifier"><i class="fa-solid fa-pen text-xs"></i></button>
                                        <?php if (!empty($d['chemin_fichier'])): ?>
                                            <a href="<?= htmlspecialchars($d['chemin_fichier']) ?>" download class="hover:text-emerald-600 transition" title="Télécharger"><i class="fa-solid fa-download text-xs"></i></a>
                                        <?php else: ?>
                                            <span class="text-slate-300"><i class="fa-solid fa-download text-xs"></i></span>
                                        <?php endif; ?>
                                        <button type="button" onclick="openDeleteModalAsync(<?= $d['id'] ?>, '<?= htmlspecialchars($d['nom_document'], ENT_QUOTES) ?>', this.closest('div.bg-white'), 'supprimer_document')" class="hover:text-red-600 transition cursor-pointer" title="Supprimer"><i class="fa-regular fa-trash-can text-xs"></i></button>
                                    <?php else: ?>
                                        <span class="text-xs text-gray-400">Défaut</span>
                                    <?php endif; ?>
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
                    <span><?= $debutAffichage ?>–<?= $finAffichage ?> sur <?= $totalTotal ?></span>
                    
                    <?php if ($totalTotal > 5): ?>
                    <div class="flex items-center space-x-3">
                        <span>Page <?= $pageActuelleNum ?> sur <?= $totalPages ?></span>
                        <div class="flex space-x-1">
                            <?php if ($pageActuelleNum > 1): ?>
                                <a href="documents.php?q=<?= urlencode($recherche) ?>&page=<?= $pageActuelleNum - 1 ?>" class="w-8 h-8 flex items-center justify-center border border-gray-200 rounded-lg text-gray-600 hover:bg-gray-100 transition"><i class="fa-solid fa-chevron-left text-[10px]"></i></a>
                            <?php else: ?>
                                <span class="w-8 h-8 flex items-center justify-center border border-gray-200 rounded-lg text-gray-300 cursor-not-allowed"><i class="fa-solid fa-chevron-left text-[10px]"></i></span>
                            <?php endif; ?>

                            <?php if ($pageActuelleNum < $totalPages): ?>
                                <a href="documents.php?q=<?= urlencode($recherche) ?>&page=<?= $pageActuelleNum + 1 ?>" class="w-8 h-8 flex items-center justify-center border border-gray-200 rounded-lg text-gray-600 hover:bg-gray-100 transition"><i class="fa-solid fa-chevron-right text-[10px]"></i></a>
                            <?php else: ?>
                                <span class="w-8 h-8 flex items-center justify-center border border-gray-200 rounded-lg text-gray-300 cursor-not-allowed"><i class="fa-solid fa-chevron-right text-[10px]"></i></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </main>

    <!-- DRAWER LATÉRAL AJOUT / MODIFICATION -->
    <div id="drawer-overlay" class="fixed inset-0 bg-black/50 z-50 hidden transition-opacity opacity-0" onclick="toggleDrawer(false)"></div>
    
    <div id="drawer" class="fixed top-0 right-0 h-full w-[420px] bg-white shadow-2xl z-50 transform translate-x-full transition-transform duration-300 ease-in-out flex flex-col justify-between overflow-y-auto">
        <div>
            <div class="p-6 border-b border-gray-100 flex justify-between items-center sticky top-0 bg-white z-10">
                <h3 id="drawer-title" class="font-bold text-base text-gray-800">Ajouter un document</h3>
                <button onclick="toggleDrawer(false)" class="text-gray-400 hover:text-gray-600 text-xl font-bold cursor-pointer">&times;</button>
            </div>

            <form id="documentForm" action="documents.php" method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
                <input type="hidden" id="form-action" name="action" value="<?= htmlspecialchars($old['action'] ?? 'ajouter_document') ?>">
                <input type="hidden" id="document_id" name="id" value="<?= htmlspecialchars($old['id'] ?? '') ?>">
                
                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Nom du document*</label>
                    <input type="text" id="nom_document" name="nom_document" value="<?= htmlspecialchars($old['nom_document'] ?? '') ?>" placeholder="ex: Règlement intérieur 2025" class="w-full px-3 py-2.5 border <?= isset($erreurs['nom_document']) ? 'border-red-500 bg-red-50/20' : 'border-gray-200' ?> rounded-lg text-sm focus:outline-none focus:border-red-600">
                    <?php if (isset($erreurs['nom_document'])): ?>
                        <p class="text-xs text-red-600 mt-1"><?= $erreurs['nom_document'] ?></p>
                    <?php endif; ?>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Type*</label>
                    <input type="text" id="type_fichier" name="type_fichier" value="<?= htmlspecialchars($old['type_fichier'] ?? 'PDF') ?>" placeholder="PDF" class="w-full px-3 py-2.5 border <?= isset($erreurs['type_fichier']) ? 'border-red-500 bg-red-50/20' : 'border-gray-200' ?> rounded-lg text-sm focus:outline-none focus:border-red-600">
                    <?php if (isset($erreurs['type_fichier'])): ?>
                        <p class="text-xs text-red-600 mt-1"><?= $erreurs['type_fichier'] ?></p>
                    <?php endif; ?>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Catégorie*</label>
                    <select id="categorie" name="categorie" class="w-full px-3 py-2.5 border <?= isset($erreurs['categorie']) ? 'border-red-500 bg-red-50/20' : 'border-gray-200' ?> rounded-lg text-sm bg-white focus:outline-none focus:border-red-600">
                        <option value="">Sélectionner une catégorie</option>
                        <option value="Administratif" <?= (isset($old['categorie']) && $old['categorie'] === 'Administratif') ? 'selected' : '' ?>>Administratif</option>
                        <option value="Pédagogique" <?= (isset($old['categorie']) && $old['categorie'] === 'Pédagogique') ? 'selected' : '' ?>>Pédagogique</option>
                        <option value="Cours" <?= (isset($old['categorie']) && $old['categorie'] === 'Cours') ? 'selected' : '' ?>>Cours</option>
                    </select>
                    <?php if (isset($erreurs['categorie'])): ?>
                        <p class="text-xs text-red-600 mt-1"><?= $erreurs['categorie'] ?></p>
                    <?php endif; ?>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Taille (optionnel)</label>
                    <input type="text" id="taille" name="taille" value="<?= htmlspecialchars($old['taille'] ?? '') ?>" placeholder="ex: 1,2 Mo" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600">
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

                <div class="pt-4 flex items-center justify-between space-x-3 border-t border-gray-100">
    <button type="button" onclick="toggleDrawer(false)" class="w-full px-4 py-2.5 bg-white text-gray-800 border border-gray-200 text-sm font-semibold rounded-xl hover:bg-gray-50 transition">
        Annuler
    </button>
    <button type="submit" id="submit-btn" class="w-full px-4 py-2.5 bg-red-600 text-white text-sm font-semibold rounded-xl hover:bg-red-700 transition shadow-sm">
        <?= $modeEdition ? 'Modifier la filière' : 'Ajouter la filière' ?>
    </button>
</div>
            </form>
        </div>
    </div>

    <!-- MODALE SUPPRESSION -->
    <div id="deleteModal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center opacity-0 transition-opacity duration-300">
        <div class="bg-white rounded-2xl p-6 w-[400px] shadow-2xl transform scale-95 transition-transform duration-300">
            <div class="flex items-center space-x-3 mb-4">
                <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center text-red-600 font-bold">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
                <h3 class="font-bold text-gray-900 text-base">Confirmation de suppression</h3>
            </div>
            <p id="deleteModalMessage" class="text-xs text-gray-600 mb-6 leading-relaxed">Voulez-vous vraiment effectuer cette suppression ?</p>
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeDeleteModal()" class="px-4 py-2 bg-gray-100 text-gray-700 text-xs font-semibold rounded-lg hover:bg-gray-200 transition cursor-pointer">Annuler</button>
                <button id="deleteModalConfirmBtn" type="button" class="px-4 py-2 bg-red-600 text-white text-xs font-semibold rounded-lg hover:bg-red-700 transition shadow-sm cursor-pointer">Supprimer</button>
            </div>
        </div>
    </div>

    <div id="toastContainer" class="fixed bottom-6 right-6 z-50 flex flex-col space-y-3"></div>

    <script>
        function showToast(message, type = 'success', onUndo = null) {
            const container = document.getElementById('toastContainer');
            const isSuccess = type === 'success';
            const bgColor = isSuccess ? 'bg-[#00B67A]' : 'bg-[#DC2626]'; 
            
            const toast = document.createElement('div');
            toast.className = `flex items-center justify-between space-x-4 ${bgColor} text-white px-6 py-4 rounded-2xl shadow-xl transform transition-all duration-300 translate-y-5 opacity-0 min-w-[340px]`;
            
            const iconSvg = isSuccess 
                ? `<svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path></svg>`
                : `<svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>`;

            toast.innerHTML = `
                <div class="flex items-center space-x-3">
                    ${iconSvg}
                    <span class="text-sm font-semibold tracking-wide">${message}</span>
                </div>
                <div class="flex items-center space-x-3">
                    ${onUndo ? `<button id="toastUndoBtn" class="text-xs bg-white/20 hover:bg-white/30 text-white font-semibold px-2.5 py-1 rounded-lg transition cursor-pointer">Annuler</button>` : ''}
                    <button onclick="this.closest('div.flex').parentElement.remove()" class="text-white/80 hover:text-white transition-colors cursor-pointer">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
            `;

            container.appendChild(toast);
            setTimeout(() => toast.classList.remove('translate-y-5', 'opacity-0'), 50);

            if (onUndo) {
                const undoBtn = toast.querySelector('#toastUndoBtn');
                undoBtn.addEventListener('click', () => {
                    clearTimeout(toast.dataset.timeoutId);
                    onUndo();
                    toast.remove();
                });
            }

            const hideTimeout = setTimeout(() => {
                if (toast.parentElement) {
                    toast.classList.add('opacity-0', 'translate-y-5');
                    setTimeout(() => toast.remove(), 300);
                }
            }, 5000);

            toast.dataset.timeoutId = hideTimeout;
        }

        let targetToDeleteId = null;
        let rowElement = null;
        let deletedData = null;

        function openDeleteModalAsync(id, itemName, element, actionType) {
            targetToDeleteId = id;
            rowElement = element;
            
            const modal = document.getElementById('deleteModal');
            const msgEl = document.getElementById('deleteModalMessage');
            const confirmBtn = document.getElementById('deleteModalConfirmBtn');

            msgEl.textContent = `Voulez-vous vraiment supprimer le document "${itemName}" ?`;

            confirmBtn.onclick = function() {
                closeDeleteModal();

                fetch('action.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: actionType, id: targetToDeleteId })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        deletedData = data.data; 
                        if (rowElement) rowElement.remove();

                        showToast("Document supprimé avec succès.", "error", () => {
                            fetch('action.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({ action: 'restaurer_document', data: deletedData })
                            })
                            .then(res => res.json())
                            .then(restData => {
                                if (restData.success) {
                                    location.reload();
                                } else {
                                    alert("Erreur lors de la restauration.");
                                }
                            })
                            .catch(() => alert("Erreur réseau."));
                        });
                    } else {
                        alert(data.message || "Erreur lors de la suppression.");
                    }
                })
                .catch(() => alert("Erreur réseau."));
            };

            modal.classList.remove('hidden');
            setTimeout(() => {
                modal.classList.remove('opacity-0');
                modal.querySelector('div').classList.remove('scale-95');
                modal.querySelector('div').classList.add('scale-100');
            }, 10);
        }

        function closeDeleteModal() {
            const modal = document.getElementById('deleteModal');
            modal.classList.add('opacity-0');
            modal.querySelector('div').classList.remove('scale-100');
            modal.querySelector('div').classList.add('scale-95');
            setTimeout(() => modal.classList.add('hidden'), 300);
        }

        let searchTimeout;
        const searchInput = document.getElementById('searchInput');
        const searchForm = document.getElementById('searchForm');

        if (searchInput && searchForm) {
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => searchForm.submit(), 400);
            });
        }

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

        function openAddDrawer() {
            document.getElementById('drawer-title').textContent = "Ajouter un document";
            document.getElementById('form-action').value = "ajouter_document";
            document.getElementById('document_id').value = "";
            document.getElementById('submit-btn').textContent = "Ajouter le document";
            document.getElementById('documentForm').reset();
            toggleDrawer(true);
        }

        function openEditDrawer(doc) {
            document.getElementById('drawer-title').textContent = "Modifier le document";
            document.getElementById('form-action').value = "modifier_document";
            document.getElementById('document_id').value = doc.id;
            document.getElementById('submit-btn').textContent = "Modifier";

            document.getElementById('nom_document').value = doc.nom_document || '';
            document.getElementById('type_fichier').value = doc.type_fichier || 'PDF';
            document.getElementById('categorie').value = doc.categorie || 'Administratif';
            document.getElementById('taille').value = doc.taille || '';

            toggleDrawer(true);
        }

        function updateFileName(input) {
            const label = document.getElementById('file-label');
            if (input.files && input.files[0]) {
                label.innerHTML = `<i class="fa-solid fa-file-circle-check text-2xl text-emerald-600 mb-1"></i><span class="font-bold text-gray-800">${input.files[0].name}</span><span class="text-emerald-600 text-[11px]">Fichier prêt à être uploadé</span>`;
            }
        }

        function setView(mode) {
            const isTable = mode === 'table';
            const viewTable = document.getElementById('view-table');
            const viewCards = document.getElementById('view-cards');
            
            if (viewTable) viewTable.classList.toggle('hidden', !isTable);
            if (viewCards) viewCards.classList.toggle('hidden', isTable);

            const active = ['bg-white', 'text-red-600', 'shadow-sm'];
            const inactive = ['text-gray-500', 'hover:text-gray-800'];
            const btnTable = document.getElementById('btn-table');
            const btnCards = document.getElementById('btn-cards');

            if (btnTable && btnCards) {
                [[btnTable, isTable], [btnCards, !isTable]].forEach(([btn, on]) => {
                    active.forEach(c => btn.classList.toggle(c, on));
                    inactive.forEach(c => btn.classList.toggle(c, !on));
                });
            }
        }

        // Si des erreurs de validation existent, réouvrir automatiquement le tiroir au chargement
        <?php if (!empty($erreurs) && !isset($erreurs['general'])): ?>
            document.addEventListener('DOMContentLoaded', () => {
                toggleDrawer(true);
            });
        <?php endif; ?>
    </script>

    <?php if (isset($_GET['success'])): ?>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                <?php if ($_GET['success'] == '2'): ?>
                    showToast("Document modifié avec succès.", "success");
                <?php elseif ($_GET['success'] == '1'): ?>
                    showToast("Document ajouté avec succès.", "success");
                <?php endif; ?>
            });
        </script>
    <?php endif; ?>
</body>
</html>