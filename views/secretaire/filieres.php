<?php
$pageActuelle = 'filieres'; 
$titrePage = 'Filières'; 
$descriptionPage = 'Gérez les filières de formation'; 
require_once '../../config/db.php';

$recherche = trim($_GET['q'] ?? '');
$message = "";

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// Classes CSS d'un champ du formulaire (seule la bordure devient rouge en cas d'erreur)
function classesChamp($erreur) {
    return 'w-full px-3 py-2.5 border rounded-lg text-sm focus:outline-none focus:border-red-600 '
        . ($erreur !== '' ? 'border-red-500' : 'border-gray-200');
}

// Création automatique de la table si elle n'existe pas
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS filieres (
        id INT AUTO_INCREMENT PRIMARY KEY,
        code_filiere VARCHAR(50) NOT NULL UNIQUE,
        nom VARCHAR(150) NOT NULL,
        responsable VARCHAR(150) DEFAULT NULL,
        description TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
} catch (PDOException $e) {
    error_log("Erreur table filieres : " . $e->getMessage());
}

// ==========================================
// AJOUT ET MODIFICATION (avec validation)
// ==========================================

$champs = ['code_filiere', 'nom', 'responsable', 'description', 'general'];
$erreurs = array_fill_keys($champs, '');

// Valeurs affichées dans le formulaire (conservées en cas d'erreur)
$ancien = [
    'action'       => 'ajouter_filiere',
    'id'           => '',
    'code_filiere' => '',
    'nom'          => '',
    'responsable'  => '',
    'description'  => '',
];
$aDesErreurs = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])
    && in_array($_POST['action'], ['ajouter_filiere', 'modifier_filiere'], true)) {

    $actionPost = $_POST['action'];
    $id = (int)($_POST['id'] ?? 0);
    $code_filiere = trim($_POST['code_filiere'] ?? '');
    $nom = trim($_POST['nom'] ?? '');
    $responsable = trim($_POST['responsable'] ?? '');
    $description = trim($_POST['description'] ?? '');

    $ancien = [
        'action'       => $actionPost,
        'id'           => $id > 0 ? $id : '',
        'code_filiere' => $code_filiere,
        'nom'          => $nom,
        'responsable'  => $responsable,
        'description'  => $description,
    ];

    try {

        // --- Code filière ---
        if ($code_filiere === '') {
            $erreurs['code_filiere'] = "Le code de la filière est obligatoire.";
        } elseif (mb_strlen($code_filiere) < 2) {
            $erreurs['code_filiere'] = "Le code doit contenir au moins 2 caractères.";
        } elseif (mb_strlen($code_filiere) > 50) {
            $erreurs['code_filiere'] = "Le code ne doit pas dépasser 50 caractères.";
        } else {
            // Unicité du code (en ignorant la filière en cours de modification)
            $idExclu = ($actionPost === 'modifier_filiere') ? $id : 0;
            $check = $pdo->prepare("SELECT id FROM filieres WHERE code_filiere = ? AND id <> ?");
            $check->execute([$code_filiere, $idExclu]);
            if ($check->fetch(PDO::FETCH_ASSOC)) {
                $erreurs['code_filiere'] = "Ce code de filière existe déjà.";
            }
        }

        // --- Nom ---
        if ($nom === '') {
            $erreurs['nom'] = "Le nom de la filière est obligatoire.";
        } elseif (mb_strlen($nom) < 2) {
            $erreurs['nom'] = "Le nom doit contenir au moins 2 caractères.";
        } elseif (mb_strlen($nom) > 150) {
            $erreurs['nom'] = "Le nom ne doit pas dépasser 150 caractères.";
        }

        // --- Responsable (facultatif) ---
        if (mb_strlen($responsable) > 150) {
            $erreurs['responsable'] = "Le nom du responsable ne doit pas dépasser 150 caractères.";
        }

        // --- Description (facultative) ---
        if (mb_strlen($description) > 500) {
            $erreurs['description'] = "La description ne doit pas dépasser 500 caractères.";
        }

        // --- Modification : la filière doit exister ---
        if ($actionPost === 'modifier_filiere') {
            if ($id <= 0) {
                $erreurs['general'] = "Identifiant de filière invalide.";
            } else {
                $check = $pdo->prepare("SELECT id FROM filieres WHERE id = ?");
                $check->execute([$id]);
                if (!$check->fetch(PDO::FETCH_ASSOC)) {
                    $erreurs['general'] = "La filière à modifier est introuvable.";
                }
            }
        }

        // --- Enregistrement si aucune erreur ---
        if (implode('', $erreurs) === '') {

            if ($actionPost === 'ajouter_filiere') {
                $stmt = $pdo->prepare("INSERT INTO filieres (code_filiere, nom, responsable, description) VALUES (?, ?, ?, ?)");
                $stmt->execute([$code_filiere, $nom, $responsable, $description]);
                header("Location: filieres.php?success=1");
                exit();
            }

            $stmt = $pdo->prepare("UPDATE filieres SET code_filiere = ?, nom = ?, responsable = ?, description = ? WHERE id = ?");
            $stmt->execute([$code_filiere, $nom, $responsable, $description, $id]);
            header("Location: filieres.php?success=2");
            exit();
        }

    } catch (PDOException $e) {

        if ($e->getCode() === '23000') {
            $erreurs['code_filiere'] = "Ce code de filière existe déjà.";
        } else {
            error_log("Erreur CRUD filieres : " . $e->getMessage());
            $erreurs['general'] = "Erreur technique lors de l'enregistrement.";
        }
    }

    $aDesErreurs = implode('', $erreurs) !== '';
}

$modeEdition = ($ancien['action'] === 'modifier_filiere');

// ==========================================
// RÉCUPÉRATION DES FILIÈRES
// ==========================================

$filieres = [];
try {
    if (!empty($recherche)) {
        $stmt = $pdo->prepare("SELECT * FROM filieres WHERE nom LIKE :q1 OR code_filiere LIKE :q2 OR responsable LIKE :q3 ORDER BY id DESC");
        $stmt->execute([':q1' => "%$recherche%", ':q2' => "%$recherche%", ':q3' => "%$recherche%"]);
        $filieres = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $filieres = $pdo->query("SELECT * FROM filieres ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $filieres = [];
}

$total = count($filieres);

$jsonFlags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Administration — Filières</title>
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

            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-4">
                <form id="searchForm" action="filieres.php" method="GET" class="relative w-full sm:w-80">
                    <input type="text" id="searchInput" name="q" value="<?= h($recherche) ?>" placeholder="Rechercher une filière..." class="w-full pl-4 pr-10 py-2.5 bg-white border border-gray-200 rounded-lg text-sm text-gray-700 placeholder-gray-400 focus:outline-none focus:border-red-600 transition" autocomplete="off">
                    <button type="submit" class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-gray-800 hover:text-red-600">
                        <i class="fa-solid fa-magnifying-glass text-sm"></i>
                    </button>
                </form>
                <button onclick="openAddDrawer()" class="bg-red-600 text-white px-5 py-2.5 rounded-lg text-sm font-semibold hover:bg-red-700 transition flex items-center space-x-2 shadow-sm justify-center shrink-0">
                    <span>Ajouter une filière</span>
                    <i class="fa-solid fa-plus text-xs"></i>
                </button>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                
                <div class="hidden lg:flex justify-end px-4 py-3 border-b border-gray-100">
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

                <div id="view-table" class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 text-slate-500 text-[11px] uppercase tracking-wider">
                                <th class="py-4 px-6 font-semibold">Code filière</th>
                                <th class="py-4 px-4 font-semibold">Nom</th>
                                <th class="py-4 px-4 font-semibold">Responsable</th>
                                <th class="py-4 px-4 font-semibold">Description</th>
                                <th class="py-4 px-6 font-semibold text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-sm">
                            <?php if (empty($filieres)): ?>
                                <tr><td colspan="5" class="py-6 px-6 text-gray-400 text-center">Aucune filière trouvée.</td></tr>
                            <?php else: foreach ($filieres as $f): ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="py-4 px-6">
                                    <span class="inline-block px-2.5 py-1 bg-red-50 text-red-600 text-[11px] font-bold rounded-full"><?= h($f['code_filiere']) ?></span>
                                </td>
                                <td class="py-4 px-4 font-bold text-gray-900"><?= h($f['nom']) ?></td>
                                <td class="py-4 px-4 text-xs text-slate-500"><?= h($f['responsable'] ?: 'Non assigné') ?></td>
                                <td class="py-4 px-4 text-xs text-slate-500"><?= h($f['description'] ?: '-') ?></td>
                                <td class="py-4 px-6 text-right space-x-3">
                                    <button type="button" onclick='openEditDrawer(<?= json_encode($f, $jsonFlags) ?>)' class="text-slate-500 hover:text-blue-600 transition cursor-pointer" title="Modifier"><i class="fa-solid fa-pen"></i></button>
                                    <button type="button" onclick="openDeleteModalAsync(<?= (int)$f['id'] ?>, <?= h(json_encode($f['nom'], $jsonFlags)) ?>, this.closest('tr'))" class="text-slate-500 hover:text-red-600 transition cursor-pointer" title="Supprimer"><i class="fa-regular fa-trash-can"></i></button>
                                </td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>

                <div id="view-cards" class="hidden p-4 sm:p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                        <?php foreach ($filieres as $f): ?>
                        <div class="bg-white border border-gray-100 rounded-xl shadow-md p-4">
                            <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                                <span class="inline-block px-2.5 py-1 bg-red-50 text-red-600 text-[11px] font-bold rounded"><?= h($f['code_filiere']) ?></span>
                                <div class="flex items-center space-x-3 text-slate-500">
                                    <button type="button" onclick='openEditDrawer(<?= json_encode($f, $jsonFlags) ?>)' class="hover:text-blue-600 transition cursor-pointer" title="Modifier"><i class="fa-solid fa-pen text-xs"></i></button>
                                    <button type="button" onclick="openDeleteModalAsync(<?= (int)$f['id'] ?>, <?= h(json_encode($f['nom'], $jsonFlags)) ?>, this.closest('div.bg-white'))" class="hover:text-red-600 transition cursor-pointer" title="Supprimer"><i class="fa-regular fa-trash-can text-xs"></i></button>
                                </div>
                            </div>
                            <div class="pt-3 space-y-3">
                                <div>
                                    <p class="text-xs text-slate-500">Nom</p>
                                    <p class="text-sm font-bold text-gray-900"><?= h($f['nom']) ?></p>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-500">Responsable</p>
                                    <p class="text-xs text-slate-600"><?= h($f['responsable'] ?: 'Non assigné') ?></p>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-500">Description</p>
                                    <p class="text-xs text-slate-600"><?= h($f['description'] ?: '-') ?></p>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="flex flex-wrap gap-3 justify-between items-center px-4 sm:px-6 py-4 border-t border-gray-100 text-xs text-slate-500">
                    <span>Total : <?= $total ?> filière(s)</span>
                </div>

            </div>
        </div>
    </main>

    <!-- DRAWER LATÉRAL AJOUT / MODIFICATION -->
    <div id="drawer-overlay" class="fixed inset-0 bg-black/50 z-50 hidden transition-opacity opacity-0" onclick="toggleDrawer(false)"></div>
    
    <div id="drawer" class="fixed top-0 right-0 h-full w-full max-w-[420px] bg-white shadow-2xl z-50 transform translate-x-full transition-transform duration-300 ease-in-out flex flex-col justify-between overflow-y-auto">
        <div>
            <div class="p-6 border-b border-gray-100 flex justify-between items-center sticky top-0 bg-white z-10">
                <h3 id="drawer-title" class="font-bold text-base text-gray-800"><?= $modeEdition ? 'Modifier la filière' : 'Nouvelle filière' ?></h3>
                <button onclick="toggleDrawer(false)" class="text-gray-400 hover:text-gray-600 text-xl font-bold">&times;</button>
            </div>

            <form id="filiereForm" action="filieres.php" method="POST" class="p-6 space-y-4" novalidate>
                <input type="hidden" id="form-action" name="action" value="<?= h($ancien['action']) ?>">
                <input type="hidden" id="filiere_id" name="id" value="<?= h($ancien['id']) ?>">

                <!-- Erreur générale -->
                <div id="err-general"
                     class="p-3 bg-red-50 border-l-4 border-red-500 text-red-700 text-xs rounded-lg <?= $erreurs['general'] === '' ? 'hidden' : '' ?>">
                    <?= h($erreurs['general']) ?>
                </div>
                
                <!-- Code -->
                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">CODE*</label>
                    <input type="text" id="code_filiere" name="code_filiere" value="<?= h($ancien['code_filiere']) ?>" placeholder="INFO" class="<?= classesChamp($erreurs['code_filiere']) ?>">
                    <p id="err-code_filiere" class="mt-1 text-xs text-red-600 <?= $erreurs['code_filiere'] === '' ? 'hidden' : '' ?>"><?= h($erreurs['code_filiere']) ?></p>
                </div>

                <!-- Nom -->
                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">NOM*</label>
                    <input type="text" id="nom" name="nom" value="<?= h($ancien['nom']) ?>" placeholder="Informatique" class="<?= classesChamp($erreurs['nom']) ?>">
                    <p id="err-nom" class="mt-1 text-xs text-red-600 <?= $erreurs['nom'] === '' ? 'hidden' : '' ?>"><?= h($erreurs['nom']) ?></p>
                </div>

                <!-- Responsable -->
                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">RESPONSABLE</label>
                    <input type="text" id="responsable" name="responsable" value="<?= h($ancien['responsable']) ?>" placeholder="Dr. Nom Prenom" class="<?= classesChamp($erreurs['responsable']) ?>">
                    <p id="err-responsable" class="mt-1 text-xs text-red-600 <?= $erreurs['responsable'] === '' ? 'hidden' : '' ?>"><?= h($erreurs['responsable']) ?></p>
                </div>

                <!-- Description -->
                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">DESCRIPTION</label>
                    <textarea id="description" name="description" rows="3" placeholder="Description de la filière......" class="<?= classesChamp($erreurs['description']) ?>"><?= h($ancien['description']) ?></textarea>
                    <p id="err-description" class="mt-1 text-xs text-red-600 <?= $erreurs['description'] === '' ? 'hidden' : '' ?>"><?= h($erreurs['description']) ?></p>
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
                <button type="button" onclick="closeDeleteModal()" class="px-4 py-2 bg-gray-100 text-gray-700 text-xs font-semibold rounded-lg hover:bg-gray-200 transition">
                    Annuler
                </button>
                <a id="deleteModalConfirmBtn" href="#" class="px-4 py-2 bg-red-600 text-white text-xs font-semibold rounded-lg hover:bg-red-700 transition shadow-sm">
                    Supprimer
                </a>
            </div>
        </div>
    </div>

    <!-- CONTAINER DES TOASTS NOTIFICATIONS -->
    <div id="toastContainer" class="fixed bottom-4 right-4 sm:bottom-6 sm:right-6 z-50 flex flex-col space-y-3"></div>

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

        const CHAMPS_FORM = ['code_filiere', 'nom', 'responsable', 'description'];

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

            const code = document.getElementById('code_filiere').value.trim();
            const nom = document.getElementById('nom').value.trim();
            const responsable = document.getElementById('responsable').value.trim();
            const description = document.getElementById('description').value.trim();

            if (code === '') {
                erreur('code_filiere', "Le code de la filière est obligatoire.");
            } else if (code.length < 2) {
                erreur('code_filiere', "Le code doit contenir au moins 2 caractères.");
            } else if (code.length > 50) {
                erreur('code_filiere', "Le code ne doit pas dépasser 50 caractères.");
            }

            if (nom === '') {
                erreur('nom', "Le nom de la filière est obligatoire.");
            } else if (nom.length < 2) {
                erreur('nom', "Le nom doit contenir au moins 2 caractères.");
            } else if (nom.length > 150) {
                erreur('nom', "Le nom ne doit pas dépasser 150 caractères.");
            }

            if (responsable.length > 150) {
                erreur('responsable', "Le nom du responsable ne doit pas dépasser 150 caractères.");
            }

            if (description.length > 500) {
                erreur('description', "La description ne doit pas dépasser 500 caractères.");
            }

            return valide;
        }

        document.getElementById('filiereForm').addEventListener('submit', function(e) {
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
            document.getElementById('code_filiere').value = '';
            document.getElementById('nom').value = '';
            document.getElementById('responsable').value = '';
            document.getElementById('description').value = '';
            effacerErreurs();
        }

        // ==========================================
        // SUPPRESSION / RESTAURATION
        // ==========================================

        let filiereToDeleteId = null;
        let filiereRowElement = null;
        let deletedFiliereData = null;

        function openDeleteModalAsync(id, nomFiliere, rowElement) {
            filiereToDeleteId = id;
            filiereRowElement = rowElement;
            
            const modal = document.getElementById('deleteModal');
            const msgEl = document.getElementById('deleteModalMessage');
            const confirmBtn = document.getElementById('deleteModalConfirmBtn');

            msgEl.textContent = `Voulez-vous vraiment supprimer la filière "${nomFiliere}" ?`;

            confirmBtn.onclick = function(e) {
                e.preventDefault();
                closeDeleteModal();

                // Appel vers action.php unifié
                fetch('action.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'supprimer_filiere', id: filiereToDeleteId })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        deletedFiliereData = data.data; 
                        filiereRowElement.remove();

                        showToast("Filière supprimée avec succès.", "error", () => {
                            fetch('action.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({ action: 'restaurer_filiere', data: deletedFiliereData })
                            })
                            .then(res => res.json())
                            .then(restData => {
                                if (restData.success) {
                                    location.reload();
                                } else {
                                    alert("Erreur lors de la restauration.");
                                }
                            });
                        });
                    } else {
                        alert(data.message);
                    }
                })
                .catch(err => console.error("Erreur:", err));
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

            window.addEventListener('DOMContentLoaded', () => {
                if (searchInput.value.length > 0) {
                    searchInput.focus();
                    searchInput.setSelectionRange(searchInput.value.length, searchInput.value.length);
                }
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
            viderFormulaire();
            document.getElementById('drawer-title').textContent = "Nouvelle filière";
            document.getElementById('form-action').value = "ajouter_filiere";
            document.getElementById('filiere_id').value = "";
            document.getElementById('submit-btn').textContent = "Ajouter la filière";
            toggleDrawer(true);
        }

        function openEditDrawer(filiere) {
            viderFormulaire();
            document.getElementById('drawer-title').textContent = "Modifier la filière";
            document.getElementById('form-action').value = "modifier_filiere";
            document.getElementById('filiere_id').value = filiere.id;
            document.getElementById('submit-btn').textContent = "Modifier la filière";

            document.getElementById('code_filiere').value = filiere.code_filiere || '';
            document.getElementById('nom').value = filiere.nom || '';
            document.getElementById('responsable').value = filiere.responsable || '';
            document.getElementById('description').value = filiere.description || '';

            toggleDrawer(true);
        }

        // Réouverture du drawer si le serveur a renvoyé des erreurs
        <?php if ($aDesErreurs): ?>
        document.addEventListener('DOMContentLoaded', () => toggleDrawer(true));
        <?php endif; ?>

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

    <?php if (isset($_GET['success'])): ?>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                <?php if ($_GET['success'] == '2'): ?>
                    showToast("Filière modifiée avec succès.", "success");
                <?php elseif ($_GET['success'] == '1'): ?>
                    showToast("Filière ajoutée avec succès.", "success");
                <?php endif; ?>
            });
        </script>
    <?php endif; ?>

</body>
</html>