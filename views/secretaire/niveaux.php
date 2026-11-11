<?php
$pageActuelle = 'niveaux'; 
$titrePage = 'Niveaux'; 
$descriptionPage = 'Niveaux d\'études de l\'établissement'; 
require_once '../../config/db.php';

$recherche = trim($_GET['q'] ?? '');
$message = "";

$limiteParPage = 5;
$pageActuelleNum = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($pageActuelleNum < 1) $pageActuelleNum = 1;
$debut = ($pageActuelleNum - 1) * $limiteParPage;

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// Classes CSS d'un champ du formulaire (seule la bordure devient rouge en cas d'erreur)
function classesChamp($erreur) {
    return 'w-full px-3 py-2.5 border rounded-lg text-sm focus:outline-none focus:border-red-600 '
        . ($erreur !== '' ? 'border-red-500' : 'border-gray-200');
}

// ==========================================
// AJOUT ET MODIFICATION (avec validation)
// ==========================================

$champs = ['filiere_id', 'code_niveau', 'libelle', 'general'];
$erreurs = array_fill_keys($champs, '');

// Valeurs affichées dans le formulaire (conservées en cas d'erreur)
$ancien = [
    'action'      => 'ajouter',
    'id'          => '',
    'filiere_id'  => '',
    'code_niveau' => '',
    'libelle'     => '',
];
$aDesErreurs = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])
    && in_array($_POST['action'], ['ajouter', 'modifier'], true)) {

    $actionPost = $_POST['action'];
    $id = (int)($_POST['id'] ?? 0);
    $code_niveau = trim($_POST['code_niveau'] ?? '');
    $libelle = trim($_POST['libelle'] ?? '');
    $filiere_id = (int)($_POST['filiere_id'] ?? 0);

    $ancien = [
        'action'      => $actionPost,
        'id'          => $id > 0 ? $id : '',
        'filiere_id'  => $filiere_id > 0 ? $filiere_id : '',
        'code_niveau' => $code_niveau,
        'libelle'     => $libelle,
    ];

    try {

        // --- Filière ---
        if ($filiere_id <= 0) {
            $erreurs['filiere_id'] = "Veuillez sélectionner une filière.";
        } else {
            $check = $pdo->prepare("SELECT id FROM filieres WHERE id = ?");
            $check->execute([$filiere_id]);
            if (!$check->fetch(PDO::FETCH_ASSOC)) {
                $erreurs['filiere_id'] = "La filière sélectionnée n'existe pas.";
            }
        }

        // --- Code niveau ---
        if ($code_niveau === '') {
            $erreurs['code_niveau'] = "Le code niveau est obligatoire.";
        } elseif (mb_strlen($code_niveau) < 2) {
            $erreurs['code_niveau'] = "Le code niveau doit contenir au moins 2 caractères.";
        } elseif (mb_strlen($code_niveau) > 50) {
            $erreurs['code_niveau'] = "Le code niveau ne doit pas dépasser 50 caractères.";
        } else {
            // Unicité du code (en ignorant le niveau en cours de modification)
            $idExclu = ($actionPost === 'modifier') ? $id : 0;
            $check = $pdo->prepare("SELECT id FROM niveaux WHERE code_niveau = ? AND id <> ?");
            $check->execute([$code_niveau, $idExclu]);
            if ($check->fetch(PDO::FETCH_ASSOC)) {
                $erreurs['code_niveau'] = "Ce code niveau existe déjà.";
            }
        }

        // --- Libellé ---
        if ($libelle === '') {
            $erreurs['libelle'] = "Le libellé du niveau est obligatoire.";
        } elseif (mb_strlen($libelle) < 2) {
            $erreurs['libelle'] = "Le libellé doit contenir au moins 2 caractères.";
        } elseif (mb_strlen($libelle) > 100) {
            $erreurs['libelle'] = "Le libellé ne doit pas dépasser 100 caractères.";
        }

        // --- Modification : le niveau doit exister ---
        if ($actionPost === 'modifier') {
            if ($id <= 0) {
                $erreurs['general'] = "Identifiant de niveau invalide.";
            } else {
                $check = $pdo->prepare("SELECT id FROM niveaux WHERE id = ?");
                $check->execute([$id]);
                if (!$check->fetch(PDO::FETCH_ASSOC)) {
                    $erreurs['general'] = "Le niveau à modifier est introuvable.";
                }
            }
        }

        // --- Enregistrement si aucune erreur ---
        if (implode('', $erreurs) === '') {

            if ($actionPost === 'ajouter') {
                $stmt = $pdo->prepare("INSERT INTO niveaux (code_niveau, libelle, filiere_id) VALUES (?, ?, ?)");
                $stmt->execute([$code_niveau, $libelle, $filiere_id]);
                header("Location: niveaux.php?success=1");
                exit();
            }

            $stmt = $pdo->prepare("UPDATE niveaux SET code_niveau = ?, libelle = ?, filiere_id = ? WHERE id = ?");
            $stmt->execute([$code_niveau, $libelle, $filiere_id, $id]);
            header("Location: niveaux.php?success=2");
            exit();
        }

    } catch (PDOException $e) {

        if ($e->getCode() === '23000') {
            $erreurs['code_niveau'] = "Ce code niveau existe déjà.";
        } else {
            error_log("Erreur CRUD niveaux : " . $e->getMessage());
            $erreurs['general'] = "Erreur technique lors de l'enregistrement.";
        }
    }

    $aDesErreurs = implode('', $erreurs) !== '';
}

$modeEdition = ($ancien['action'] === 'modifier');

// Récupération sécurisée avec pagination et recherche
$totalTotal = 0;
$niveaux = [];
try {
    $sqlCount = "SELECT COUNT(*) FROM niveaux n JOIN filieres f ON n.filiere_id = f.id WHERE n.code_niveau LIKE :q1 OR n.libelle LIKE :q2 OR f.nom LIKE :q3";
    $stmtCount = $pdo->prepare($sqlCount);
    $stmtCount->execute([
        ':q1' => "%$recherche%",
        ':q2' => "%$recherche%",
        ':q3' => "%$recherche%"
    ]);
    $totalTotal = (int)$stmtCount->fetchColumn();

    $sql = "SELECT n.*, f.nom AS nom_filiere, f.code_filiere 
            FROM niveaux n 
            JOIN filieres f ON n.filiere_id = f.id 
            WHERE n.code_niveau LIKE :q1 OR n.libelle LIKE :q2 OR f.nom LIKE :q3 
            ORDER BY n.id DESC LIMIT :limite OFFSET :debut";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':q1', "%$recherche%", PDO::PARAM_STR);
    $stmt->bindValue(':q2', "%$recherche%", PDO::PARAM_STR);
    $stmt->bindValue(':q3', "%$recherche%", PDO::PARAM_STR);
    $stmt->bindValue(':limite', $limiteParPage, PDO::PARAM_INT);
    $stmt->bindValue(':debut', $debut, PDO::PARAM_INT);
    $stmt->execute();
    $niveaux = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $niveaux = [];
}

// Récupération des filières pour le select
$filieres = [];
try {
    $filieres = $pdo->query("SELECT * FROM filieres ORDER BY nom ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $filieres = [];
}

$totalPages = ceil($totalTotal / $limiteParPage);
if ($totalPages < 1) $totalPages = 1;
$debutAffichage = $totalTotal > 0 ? $debut + 1 : 0;
$finAffichage = min($debut + $limiteParPage, $totalTotal);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Administration — Niveaux</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 flex h-screen overflow-hidden font-sans">

    <?php include '../../includes/sidebar_secretaire.php'; ?>

    <main class="flex-1 min-w-0 flex flex-col h-screen overflow-hidden">
        <?php include '../../includes/header.php'; ?>

        <div class="flex-1 p-4 sm:p-6 md:p-8 overflow-y-auto space-y-6">
            
            <?php if (!empty($message)): ?>
                <div class="p-4 bg-red-50 border-l-4 border-red-500 text-red-700 text-sm rounded-lg shadow-sm">
                    <?= h($message) ?>
                </div>
            <?php endif; ?>

            <!-- Barre d'outils : Recherche + Bouton Ajouter -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-4">
                <form id="searchForm" action="niveaux.php" method="GET" class="relative w-full sm:w-80">
                    <input type="text" id="searchInput" name="q" value="<?= h($recherche) ?>" placeholder="Rechercher un niveau..." class="w-full pl-4 pr-10 py-2.5 bg-white border border-gray-200 rounded-lg text-sm text-gray-700 placeholder-gray-400 focus:outline-none focus:border-red-600 transition" autocomplete="off">
                    <button type="submit" class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-gray-800 hover:text-red-600 cursor-pointer">
                        <i class="fa-solid fa-magnifying-glass text-sm"></i>
                    </button>
                </form>
                <button onclick="openAddModal()" class="bg-red-600 text-white px-5 py-2.5 rounded-lg text-sm font-semibold hover:bg-red-700 transition flex items-center space-x-2 shadow-sm justify-center shrink-0 cursor-pointer">
                    <span>Ajouter un niveau</span>
                    <i class="fa-solid fa-plus text-xs"></i>
                </button>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                
                <div class="hidden lg:flex justify-end px-4 py-3 border-b border-gray-100">
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
                                <th class="py-4 px-6 font-semibold">Code niveau</th>
                                <th class="py-4 px-4 font-semibold">Libellé</th>
                                <th class="py-4 px-4 font-semibold">Filière</th>
                                <th class="py-4 px-6 font-semibold">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-sm">
                            <?php if (empty($niveaux)): ?>
                                <tr><td colspan="4" class="py-6 text-gray-400 text-center text-xs">Aucun niveau trouvé.</td></tr>
                            <?php else: foreach ($niveaux as $n): 
                                $jsonNiveau = json_encode($n, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
                                $jsonCode = json_encode($n['code_niveau'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
                            ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="py-4 px-6">
                                    <span class="inline-block bg-gray-100 text-gray-600 px-2.5 py-1 rounded-full text-[11px] font-medium"><?= h($n['code_niveau']) ?></span>
                                </td>
                                <td class="py-4 px-4 font-bold text-gray-900"><?= h($n['libelle']) ?></td>
                                <td class="py-4 px-4">
                                    <span class="inline-block bg-blue-50 text-blue-700 px-2.5 py-1 rounded-full text-[11px] font-medium"><?= h($n['nom_filiere']) ?></span>
                                </td>
                                <td class="py-4 px-6 space-x-3 whitespace-nowrap">
                                    <button type="button" onclick='openEditModal(<?= $jsonNiveau ?>)' class="text-slate-500 hover:text-blue-600 transition cursor-pointer" title="Modifier"><i class="fa-solid fa-pen"></i></button>
                                    <button type="button" onclick="openDeleteModalAsync(<?= (int)$n['id'] ?>, <?= h($jsonCode) ?>, this.closest('tr'), 'supprimer_niveau')" class="text-slate-500 hover:text-red-600 transition cursor-pointer" title="Supprimer"><i class="fa-regular fa-trash-can"></i></button>
                                </td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- VUE CARTES -->
                <div id="view-cards" class="hidden p-4 sm:p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                        <?php if (empty($niveaux)): ?>
                            <p class="text-gray-400 text-xs">Aucun niveau trouvé.</p>
                        <?php else: foreach ($niveaux as $n): 
                            $jsonNiveauCard = json_encode($n, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
                            $jsonCodeCard = json_encode($n['code_niveau'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
                        ?>
                        <div class="bg-white border border-gray-100 rounded-xl shadow-md p-4">
                            <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                                <span class="inline-block bg-gray-100 text-gray-600 px-2.5 py-1 rounded text-[11px] font-medium"><?= h($n['code_niveau']) ?></span>
                                <div class="flex items-center space-x-3 text-slate-500">
                                    <button type="button" onclick='openEditModal(<?= $jsonNiveauCard ?>)' class="hover:text-blue-600 transition cursor-pointer" title="Modifier"><i class="fa-solid fa-pen text-xs"></i></button>
                                    <button type="button" onclick="openDeleteModalAsync(<?= (int)$n['id'] ?>, <?= h($jsonCodeCard) ?>, this.closest('div.bg-white'), 'supprimer_niveau')" class="hover:text-red-600 transition cursor-pointer" title="Supprimer"><i class="fa-regular fa-trash-can text-xs"></i></button>
                                </div>
                            </div>
                            <div class="pt-3 space-y-3">
                                <div>
                                    <p class="text-xs text-slate-500">Libellé</p>
                                    <p class="text-sm font-bold text-gray-900"><?= h($n['libelle']) ?></p>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-500 mb-1">Filière</p>
                                    <span class="inline-block bg-blue-50 text-blue-700 px-2.5 py-1 rounded-full text-[11px] font-medium"><?= h($n['nom_filiere']) ?></span>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; endif; ?>
                    </div>
                </div>

                <!-- Pagination -->
                <div class="flex flex-wrap gap-3 justify-between items-center px-4 sm:px-6 py-4 border-t border-gray-100 text-xs text-slate-500">
                    <span><?= $debutAffichage ?>–<?= $finAffichage ?> sur <?= $totalTotal ?></span>
                    
                    <?php if ($totalTotal > 5): ?>
                    <div class="flex items-center space-x-3">
                        <span>Page <?= $pageActuelleNum ?> sur <?= $totalPages ?></span>
                        <div class="flex space-x-1">
                            <?php if ($pageActuelleNum > 1): ?>
                                <a href="niveaux.php?q=<?= urlencode($recherche) ?>&page=<?= $pageActuelleNum - 1 ?>" class="w-8 h-8 flex items-center justify-center border border-gray-200 rounded-lg text-gray-600 hover:bg-gray-100 transition"><i class="fa-solid fa-chevron-left text-[10px]"></i></a>
                            <?php else: ?>
                                <span class="w-8 h-8 flex items-center justify-center border border-gray-200 rounded-lg text-gray-300 cursor-not-allowed"><i class="fa-solid fa-chevron-left text-[10px]"></i></span>
                            <?php endif; ?>

                            <?php if ($pageActuelleNum < $totalPages): ?>
                                <a href="niveaux.php?q=<?= urlencode($recherche) ?>&page=<?= $pageActuelleNum + 1 ?>" class="w-8 h-8 flex items-center justify-center border border-gray-200 rounded-lg text-gray-600 hover:bg-gray-100 transition"><i class="fa-solid fa-chevron-right text-[10px]"></i></a>
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

    <!-- MODALE AJOUT / MODIFICATION -->
    <div id="modal-container" class="fixed inset-0 z-50 flex items-center justify-center hidden">
        <div id="modal-backdrop" class="fixed inset-0 bg-black/50 opacity-0 transition-opacity duration-300" onclick="toggleModal(false)"></div>

        <div id="modal-box" class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 p-6 z-10 opacity-0 scale-95 transition-all duration-300 space-y-4 max-h-[90vh] overflow-y-auto">
            
            <div class="flex justify-between items-center border-b border-gray-100 pb-3">
                <h3 id="modal-title" class="font-bold text-base text-gray-800"><?= $modeEdition ? 'Modifier le niveau' : 'Ajouter un niveau' ?></h3>
                <button onclick="toggleModal(false)" class="text-gray-400 hover:text-gray-600 text-xl font-bold cursor-pointer">&times;</button>
            </div>

            <form id="niveauForm" action="niveaux.php" method="POST" class="space-y-4" novalidate>
                <input type="hidden" id="form-action" name="action" value="<?= h($ancien['action']) ?>">
                <input type="hidden" id="niveau_id" name="id" value="<?= h($ancien['id']) ?>">

                <!-- Erreur générale -->
                <div id="err-general"
                     class="p-3 bg-red-50 border-l-4 border-red-500 text-red-700 text-xs rounded-lg <?= $erreurs['general'] === '' ? 'hidden' : '' ?>">
                    <?= h($erreurs['general']) ?>
                </div>
                
                <!-- Filière -->
                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Filière*</label>
                    <select id="filiere_id" name="filiere_id" class="<?= classesChamp($erreurs['filiere_id']) ?> bg-white">
                        <option value="">Sélectionner une filière</option>
                        <?php foreach ($filieres as $f): ?>
                            <option value="<?= (int)$f['id'] ?>" <?= (string)$ancien['filiere_id'] === (string)$f['id'] ? 'selected' : '' ?>>
                                <?= h($f['nom']) ?> (<?= h($f['code_filiere']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p id="err-filiere_id" class="mt-1 text-xs text-red-600 <?= $erreurs['filiere_id'] === '' ? 'hidden' : '' ?>"><?= h($erreurs['filiere_id']) ?></p>
                </div>

                <!-- Code niveau -->
                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Code Niveau*</label>
                    <input type="text" id="code_niveau" name="code_niveau" value="<?= h($ancien['code_niveau']) ?>" placeholder="ex: INFO-L1" class="<?= classesChamp($erreurs['code_niveau']) ?>">
                    <p id="err-code_niveau" class="mt-1 text-xs text-red-600 <?= $erreurs['code_niveau'] === '' ? 'hidden' : '' ?>"><?= h($erreurs['code_niveau']) ?></p>
                </div>

                <!-- Libellé -->
                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Libellé du niveau*</label>
                    <input type="text" id="libelle" name="libelle" value="<?= h($ancien['libelle']) ?>" placeholder="ex: Licence 1ère année" class="<?= classesChamp($erreurs['libelle']) ?>">
                    <p id="err-libelle" class="mt-1 text-xs text-red-600 <?= $erreurs['libelle'] === '' ? 'hidden' : '' ?>"><?= h($erreurs['libelle']) ?></p>
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

    <!-- MODALE DE SUPPRESSION FIGMA -->
    <div id="deleteModal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center opacity-0 transition-opacity duration-300">
        <div class="bg-white rounded-2xl p-6 w-[400px] max-w-[90%] shadow-2xl transform scale-95 transition-transform duration-300">
            <div class="flex items-center space-x-3 mb-4">
                <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center text-red-600 font-bold">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
                <h3 class="font-bold text-gray-900 text-base">Confirmation de suppression</h3>
            </div>
            <p id="deleteModalMessage" class="text-xs text-gray-600 mb-6 leading-relaxed">
                Voulez-vous vraiment effectuer cette suppression ?
            </p>
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeDeleteModal()" class="px-4 py-2 bg-gray-100 text-gray-700 text-xs font-semibold rounded-lg hover:bg-gray-200 transition cursor-pointer">
                    Annuler
                </button>
                <button id="deleteModalConfirmBtn" type="button" class="px-4 py-2 bg-red-600 text-white text-xs font-semibold rounded-lg hover:bg-red-700 transition shadow-sm cursor-pointer">
                    Supprimer
                </button>
            </div>
        </div>
    </div>

    <!-- CONTAINER DES TOASTS NOTIFICATIONS -->
    <div id="toastContainer" class="fixed bottom-4 right-4 sm:bottom-6 sm:right-6 z-50 flex flex-col space-y-3"></div>

    <!-- Scripts -->
    <script>
        function showToast(message, type = 'success', onUndo = null) {
            const container = document.getElementById('toastContainer');
            const isSuccess = type === 'success';
            const bgColor = isSuccess ? 'bg-[#00B67A]' : 'bg-[#DC2626]'; 
            
            const toast = document.createElement('div');
            toast.className = `flex items-center justify-between space-x-4 ${bgColor} text-white px-6 py-4 rounded-2xl shadow-xl transform transition-all duration-300 translate-y-5 opacity-0 w-[calc(100vw-2rem)] sm:w-auto sm:min-w-[340px]`;
            
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
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
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

        // ==========================================
        // VALIDATION DES CHAMPS (côté navigateur)
        // ==========================================

        const CHAMPS_FORM = ['filiere_id', 'code_niveau', 'libelle'];

        function setErreur(champ, texte) {
            const input = document.getElementById(champ);
            const zone = document.getElementById('err-' + champ);

            if (zone) {
                zone.textContent = texte;
                zone.classList.toggle('hidden', texte === '');
            }

            if (input) {
                input.classList.toggle('border-red-500', texte !== '');
                input.classList.toggle('border-gray-200', texte === '');
            }
        }

        function effacerErreurs() {
            CHAMPS_FORM.forEach(c => setErreur(c, ''));

            const general = document.getElementById('err-general');
            general.textContent = '';
            general.classList.add('hidden');
        }

        function validerFormulaire() {
            effacerErreurs();

            let valide = true;
            const erreur = (champ, texte) => { setErreur(champ, texte); valide = false; };

            const filiere = document.getElementById('filiere_id').value;
            const code = document.getElementById('code_niveau').value.trim();
            const libelle = document.getElementById('libelle').value.trim();

            if (filiere === '') {
                erreur('filiere_id', "Veuillez sélectionner une filière.");
            }

            if (code === '') {
                erreur('code_niveau', "Le code niveau est obligatoire.");
            } else if (code.length < 2) {
                erreur('code_niveau', "Le code niveau doit contenir au moins 2 caractères.");
            } else if (code.length > 50) {
                erreur('code_niveau', "Le code niveau ne doit pas dépasser 50 caractères.");
            }

            if (libelle === '') {
                erreur('libelle', "Le libellé du niveau est obligatoire.");
            } else if (libelle.length < 2) {
                erreur('libelle', "Le libellé doit contenir au moins 2 caractères.");
            } else if (libelle.length > 100) {
                erreur('libelle', "Le libellé ne doit pas dépasser 100 caractères.");
            }

            return valide;
        }

        document.getElementById('niveauForm').addEventListener('submit', function(e) {
            if (!validerFormulaire()) {
                e.preventDefault();
            }
        });

        // Efface l'erreur d'un champ dès que l'utilisateur le corrige
        CHAMPS_FORM.forEach(champ => {
            const el = document.getElementById(champ);
            el.addEventListener('input', () => setErreur(champ, ''));
            el.addEventListener('change', () => setErreur(champ, ''));
        });

        // Vide complètement le formulaire (reset() restaurerait les anciennes valeurs après une erreur serveur)
        function viderFormulaire() {
            document.getElementById('filiere_id').value = '';
            document.getElementById('code_niveau').value = '';
            document.getElementById('libelle').value = '';
            effacerErreurs();
        }

        // ==========================================
        // SUPPRESSION / RESTAURATION
        // ==========================================

        let targetToDeleteId = null;
        let rowElement = null;
        let deletedData = null;

        function openDeleteModalAsync(id, itemName, element, actionType) {
            targetToDeleteId = id;
            rowElement = element;
            
            const modal = document.getElementById('deleteModal');
            const msgEl = document.getElementById('deleteModalMessage');
            const confirmBtn = document.getElementById('deleteModalConfirmBtn');

            msgEl.textContent = `Voulez-vous vraiment supprimer le niveau "${itemName}" ?`;

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

                        showToast("Niveau supprimé avec succès.", "error", () => {
                            fetch('action.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({ action: 'restaurer_niveau', data: deletedData })
                            })
                            .then(res => res.json())
                            .then(restData => {
                                if (restData.success) {
                                    location.reload();
                                } else {
                                    alert("Erreur lors de la restauration.");
                                }
                            })
                            .catch(() => alert("Erreur réseau lors de la tentative de restauration."));
                        });
                    } else {
                        alert(data.message || "Erreur lors de la suppression.");
                    }
                })
                .catch(() => alert("Erreur réseau. Impossible de contacter le serveur."));
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

        // Recherche dynamique avec Debounce (400ms)
        let searchTimeout;
        const searchInput = document.getElementById('searchInput');
        const searchForm = document.getElementById('searchForm');

        if (searchInput && searchForm) {
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => searchForm.submit(), 400);
            });

            window.addEventListener('DOMContentLoaded', () => {
                if (searchInput.value.length > 0) {
                    searchInput.focus();
                    searchInput.setSelectionRange(searchInput.value.length, searchInput.value.length);
                }
            });
        }

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

        function openAddModal() {
            viderFormulaire();
            document.getElementById('modal-title').textContent = "Ajouter un niveau";
            document.getElementById('form-action').value = "ajouter";
            document.getElementById('niveau_id').value = "";
            document.getElementById('submit-btn').textContent = "Enregistrer";
            toggleModal(true);
        }

        function openEditModal(niveau) {
            viderFormulaire();
            document.getElementById('modal-title').textContent = "Modifier le niveau";
            document.getElementById('form-action').value = "modifier";
            document.getElementById('niveau_id').value = niveau.id;
            document.getElementById('submit-btn').textContent = "Modifier";

            document.getElementById('code_niveau').value = niveau.code_niveau || '';
            document.getElementById('libelle').value = niveau.libelle || '';
            document.getElementById('filiere_id').value = niveau.filiere_id || '';

            toggleModal(true);
        }

        // Réouverture de la modale si le serveur a renvoyé des erreurs
        <?php if ($aDesErreurs): ?>
        document.addEventListener('DOMContentLoaded', () => toggleModal(true));
        <?php endif; ?>

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
    </script>

    <?php if (isset($_GET['success'])): ?>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                <?php if ($_GET['success'] == '2'): ?>
                    showToast("Niveau modifié avec succès.", "success");
                <?php elseif ($_GET['success'] == '1'): ?>
                    showToast("Niveau ajouté avec succès.", "success");
                <?php endif; ?>
            });
        </script>
    <?php endif; ?>

</body>
</html>