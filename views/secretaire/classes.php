<?php
$pageActuelle = 'classes'; 
$titrePage = 'Gestion des Classes'; 
$descriptionPage = 'Gérez les filières et les classes de l\'établissement'; 
require_once '../../config/db.php';

$recherche = trim($_GET['q'] ?? '');
$message = "";

// Gestion de la pagination (5 éléments par page)
$limiteParPage = 5;
$pageActuelleNum = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($pageActuelleNum < 1) $pageActuelleNum = 1;
$debut = ($pageActuelleNum - 1) * $limiteParPage;

// Traitement de l'ajout ou de la modification d'une classe
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $actionPost = $_POST['action'];
    $id = $_POST['id'] ?? null;
    $code_classe = trim($_POST['code_classe'] ?? '');
    $nom = trim($_POST['nom'] ?? '');
    $capacite = trim($_POST['capacite'] ?? '35 places');
    $filiere_id = !empty($_POST['filiere_id']) ? $_POST['filiere_id'] : null;
    $niveau_id = !empty($_POST['niveau_id']) ? $_POST['niveau_id'] : null;

    $erreurs = [];
    if (empty($code_classe)) $erreurs[] = "Le code de la classe est obligatoire.";
    if (empty($nom)) $erreurs[] = "Le nom de la classe est obligatoire.";
    if (empty($filiere_id)) $erreurs[] = "Veuillez sélectionner une filière.";
    if (empty($niveau_id)) $erreurs[] = "Veuillez sélectionner un niveau.";

    if (empty($erreurs)) {
        try {
            if ($actionPost === 'ajouter_classe') {
                $stmt = $pdo->prepare("INSERT INTO classes (code_classe, nom, capacite, filiere_id, niveau_id) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$code_classe, $nom, $capacite, $filiere_id, $niveau_id]);
                header("Location: classes.php?success=1");
                exit();
            } elseif ($actionPost === 'modifier_classe' && !empty($id)) {
                $stmt = $pdo->prepare("UPDATE classes SET code_classe = ?, nom = ?, capacite = ?, filiere_id = ?, niveau_id = ? WHERE id = ?");
                $stmt->execute([$code_classe, $nom, $capacite, $filiere_id, $niveau_id, $id]);
                header("Location: classes.php?success=2");
                exit();
            }
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $message = "Erreur : Ce code de classe existe déjà en base de données.";
            } else {
                $message = "Erreur technique lors de l'enregistrement.";
            }
        }
    } else {
        $message = implode("<br>", $erreurs);
    }
}

// Récupération des données avec jointures, recherche et pagination
$totalTotal = 0;
$classes = [];
$filieres = [];
$niveaux = [];

try {
    $sqlCount = "SELECT COUNT(*) FROM classes c JOIN filieres f ON c.filiere_id = f.id WHERE c.nom LIKE :q1 OR c.code_classe LIKE :q2";
    $stmtCount = $pdo->prepare($sqlCount);
    $stmtCount->execute([
        ':q1' => "%$recherche%",
        ':q2' => "%$recherche%"
    ]);
    $totalTotal = $stmtCount->fetchColumn();

    $sql = "SELECT c.*, f.nom as nom_filiere, n.libelle as nom_niveau 
            FROM classes c 
            LEFT JOIN filieres f ON c.filiere_id = f.id 
            LEFT JOIN niveaux n ON c.niveau_id = n.id 
            WHERE c.nom LIKE :q1 OR c.code_classe LIKE :q2 
            ORDER BY c.id DESC LIMIT :limite OFFSET :debut";
            
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':q1', "%$recherche%", PDO::PARAM_STR);
    $stmt->bindValue(':q2', "%$recherche%", PDO::PARAM_STR);
    $stmt->bindValue(':limite', $limiteParPage, PDO::PARAM_INT);
    $stmt->bindValue(':debut', $debut, PDO::PARAM_INT);
    $stmt->execute();
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Récupération des listes pour les select
    $filieres = $pdo->query("SELECT * FROM filieres ORDER BY nom ASC")->fetchAll(PDO::FETCH_ASSOC);
    $niveaux = $pdo->query("SELECT * FROM niveaux ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $classes = [];
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
    <title>Administration — Gestion des Classes</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 flex h-screen overflow-hidden font-sans">

    <?php include '../../includes/sidebar_secretaire.php'; ?>

    <main class="flex-1 flex flex-col h-screen overflow-hidden">
        <?php include '../../includes/header.php'; ?>

        <div class="flex-1 p-8 overflow-y-auto space-y-6">
            
            <?php if (!empty($message)): ?>
                <div class="p-4 bg-red-50 border-l-4 border-red-500 text-red-700 text-sm rounded-lg shadow-sm">
                    <?= $message ?>
                </div>
            <?php endif; ?>

            <!-- Barre d'outils : Recherche + Bouton Ajouter -->
            <div class="flex items-center justify-between">
                <form id="searchForm" action="classes.php" method="GET" class="relative w-80">
                    <input type="text" id="searchInput" name="q" value="<?= htmlspecialchars($recherche) ?>" placeholder="Rechercher une classe..." class="w-full pl-4 pr-10 py-2.5 bg-white border border-gray-200 rounded-lg text-sm text-gray-700 placeholder-gray-400 focus:outline-none focus:border-red-600 transition" autocomplete="off">
                    <button type="submit" class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-gray-800 hover:text-red-600 cursor-pointer">
                        <i class="fa-solid fa-magnifying-glass text-sm"></i>
                    </button>
                </form>
                <button onclick="openAddDrawer()" class="bg-red-600 text-white px-5 py-2.5 rounded-lg text-sm font-semibold hover:bg-red-700 transition flex items-center space-x-2 shadow-sm cursor-pointer">
                    <span>Ajouter une classe</span>
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
                                <th class="py-4 px-4 font-semibold">Code Classe</th>
                                <th class="py-4 px-4 font-semibold">Nom</th>
                                <th class="py-4 px-4 font-semibold">Capacité</th>
                                <th class="py-4 px-4 font-semibold">Filière</th>
                                <th class="py-4 px-4 font-semibold">Niveau</th>
                                <th class="py-4 px-4 font-semibold">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-xs">
                            <?php if (empty($classes)): ?>
                                <tr><td colspan="6" class="py-6 text-gray-400">Aucune classe trouvée.</td></tr>
                            <?php else: foreach ($classes as $c): 
                                $jsonClasse = json_encode($c, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
                            ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="py-4 px-4"><span class="bg-gray-100 text-gray-600 px-2.5 py-1 rounded-md font-medium text-[11px]"><?= htmlspecialchars($c['code_classe']) ?></span></td>
                                <td class="py-4 px-4 text-sm font-bold text-gray-900"><?= htmlspecialchars($c['nom']) ?></td>
                                <td class="py-4 px-4 font-medium text-gray-800"><?= htmlspecialchars($c['capacite'] ?? '35 places') ?></td>
                                <td class="py-4 px-4"><span class="bg-blue-50 text-blue-600 px-2.5 py-1 rounded-full font-medium text-[11px]"><?= htmlspecialchars($c['nom_filiere'] ?? '-') ?></span></td>
                                <td class="py-4 px-4"><span class="bg-gray-100 text-gray-600 px-2.5 py-1 rounded-full text-[11px]"><?= htmlspecialchars($c['nom_niveau'] ?? '-') ?></span></td>
                                <td class="py-4 px-4 space-x-3 whitespace-nowrap">
                                    <button type="button" onclick='openEditDrawer(<?= $jsonClasse ?>)' class="text-slate-500 hover:text-blue-600 transition cursor-pointer" title="Modifier"><i class="fa-solid fa-pen"></i></button>
                                    <button type="button" onclick="openDeleteModalAsync(<?= $c['id'] ?>, '<?= htmlspecialchars($c['code_classe'], ENT_QUOTES) ?>', this.closest('tr'), 'supprimer_classe')" class="text-slate-500 hover:text-red-600 transition cursor-pointer" title="Supprimer"><i class="fa-regular fa-trash-can"></i></button>
                                </td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- VUE CARTES -->
                <div id="view-cards" class="hidden p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                        <?php if (empty($classes)): ?>
                            <p class="text-gray-400 text-xs">Aucune classe trouvée.</p>
                        <?php else: foreach ($classes as $c): 
                            $jsonClasseCard = json_encode($c, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
                        ?>
                        <div class="bg-white border border-gray-100 rounded-xl shadow-md p-4">
                            <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                                <span class="text-sm font-bold text-gray-800"><?= htmlspecialchars($c['code_classe']) ?></span>
                                <div class="flex items-center space-x-3 text-slate-500">
                                    <button type="button" onclick='openEditDrawer(<?= $jsonClasseCard ?>)' class="hover:text-blue-600 transition cursor-pointer" title="Modifier"><i class="fa-solid fa-pen text-xs"></i></button>
                                    <button type="button" onclick="openDeleteModalAsync(<?= $c['id'] ?>, '<?= htmlspecialchars($c['code_classe'], ENT_QUOTES) ?>', this.closest('div.bg-white'), 'supprimer_classe')" class="hover:text-red-600 transition cursor-pointer" title="Supprimer"><i class="fa-regular fa-trash-can text-xs"></i></button>
                                </div>
                            </div>
                            <div class="pt-3 space-y-3">
                                <div>
                                    <p class="text-xs text-slate-500">Nom</p>
                                    <p class="text-sm font-bold text-gray-900"><?= htmlspecialchars($c['nom']) ?></p>
                                </div>
                                <div class="flex justify-between items-center">
                                    <div>
                                        <p class="text-xs text-slate-500 mb-1">Capacité</p>
                                        <p class="text-xs font-semibold text-gray-700"><?= htmlspecialchars($c['capacite'] ?? '35 places') ?></p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-slate-500 mb-1">Filière</p>
                                        <span class="bg-blue-50 text-blue-600 px-2 py-0.5 rounded text-[11px] font-medium"><?= htmlspecialchars($c['nom_filiere'] ?? '-') ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; endif; ?>
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
                                <a href="classes.php?q=<?= urlencode($recherche) ?>&page=<?= $pageActuelleNum - 1 ?>" class="w-8 h-8 flex items-center justify-center border border-gray-200 rounded-lg text-gray-600 hover:bg-gray-100 transition"><i class="fa-solid fa-chevron-left text-[10px]"></i></a>
                            <?php else: ?>
                                <span class="w-8 h-8 flex items-center justify-center border border-gray-200 rounded-lg text-gray-300 cursor-not-allowed"><i class="fa-solid fa-chevron-left text-[10px]"></i></span>
                            <?php endif; ?>

                            <?php if ($pageActuelleNum < $totalPages): ?>
                                <a href="classes.php?q=<?= urlencode($recherche) ?>&page=<?= $pageActuelleNum + 1 ?>" class="w-8 h-8 flex items-center justify-center border border-gray-200 rounded-lg text-gray-600 hover:bg-gray-100 transition"><i class="fa-solid fa-chevron-right text-[10px]"></i></a>
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
    
    <div id="drawer" class="fixed top-0 right-0 h-full w-[440px] bg-white shadow-2xl z-50 transform translate-x-full transition-transform duration-300 ease-in-out flex flex-col justify-between overflow-y-auto">
        <div>
            <div class="p-6 border-b border-gray-100 flex justify-between items-center sticky top-0 bg-white z-10">
                <h3 id="drawer-title" class="font-bold text-base text-gray-800">Nouvelle classe</h3>
                <button onclick="toggleDrawer(false)" class="text-gray-400 hover:text-gray-600 text-xl font-bold cursor-pointer">&times;</button>
            </div>

            <form id="addClassForm" action="classes.php" method="POST" class="p-6 space-y-4" onsubmit="return validateForm(event)">
                <input type="hidden" id="form-action" name="action" value="ajouter_classe">
                <input type="hidden" id="classe_id" name="id" value="">
                
                <h4 class="text-xs font-bold text-red-600 uppercase tracking-wider">Information de la classe</h4>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Code Classe*</label>
                    <input type="text" id="code_classe" name="code_classe" placeholder="INFO-L1-A" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600">
                    <p id="error-code" class="text-xs text-red-500 mt-1 hidden"></p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Nom*</label>
                    <input type="text" id="nom" name="nom" placeholder="Informatique L1 Groupe A" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600">
                    <p id="error-nom" class="text-xs text-red-500 mt-1 hidden"></p>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Capacité</label>
                        <input type="text" id="capacite" name="capacite" value="35 places" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Filière*</label>
                        <select id="filiere_id" name="filiere_id" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm bg-white focus:outline-none focus:border-red-600">
                            <option value="">Sélectionner</option>
                            <?php foreach ($filieres as $f): ?>
                                <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p id="error-filiere" class="text-xs text-red-500 mt-1 hidden"></p>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Niveau*</label>
                    <select id="niveau_id" name="niveau_id" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm bg-white focus:outline-none focus:border-red-600">
                        <option value="">Sélectionner un niveau</option>
                        <?php foreach ($niveaux as $n): ?>
                            <option value="<?= $n['id'] ?>"><?= htmlspecialchars($n['libelle']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p id="error-niveau" class="text-xs text-red-500 mt-1 hidden"></p>
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
        <div class="bg-white rounded-2xl p-6 w-[400px] shadow-2xl transform scale-95 transition-transform duration-300">
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

        let targetToDeleteId = null;
        let rowElement = null;
        let deletedData = null;

        function openDeleteModalAsync(id, itemName, element, actionType) {
            targetToDeleteId = id;
            rowElement = element;
            
            const modal = document.getElementById('deleteModal');
            const msgEl = document.getElementById('deleteModalMessage');
            const confirmBtn = document.getElementById('deleteModalConfirmBtn');

            msgEl.textContent = `Voulez-vous vraiment supprimer la classe "${itemName}" ?`;

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

                        showToast("Classe supprimée avec succès.", "error", () => {
                            fetch('action.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({ action: 'restaurer_classe', data: deletedData })
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
            document.getElementById('drawer-title').textContent = "Nouvelle classe";
            document.getElementById('form-action').value = "ajouter_classe";
            document.getElementById('classe_id').value = "";
            document.getElementById('submit-btn').textContent = "Ajouter";
            document.getElementById('addClassForm').reset();
            document.getElementById('capacite').value = "35 places";
            toggleDrawer(true);
        }

        function openEditDrawer(classe) {
            document.getElementById('drawer-title').textContent = "Modifier la classe";
            document.getElementById('form-action').value = "modifier_classe";
            document.getElementById('classe_id').value = classe.id;
            document.getElementById('submit-btn').textContent = "Modifier";

            document.getElementById('code_classe').value = classe.code_classe || '';
            document.getElementById('nom').value = classe.nom || '';
            document.getElementById('capacite').value = classe.capacite || '35 places';
            document.getElementById('filiere_id').value = classe.filiere_id || '';
            document.getElementById('niveau_id').value = classe.niveau_id || '';

            toggleDrawer(true);
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

        function validateForm(event) {
            let isValid = true;
            const code = document.getElementById('code_classe');
            const nom = document.getElementById('nom');
            const filiere = document.getElementById('filiere_id');
            const niveau = document.getElementById('niveau_id');

            const errCode = document.getElementById('error-code');
            const errNom = document.getElementById('error-nom');
            const errFiliere = document.getElementById('error-filiere');
            const errNiveau = document.getElementById('error-niveau');

            [code, nom, filiere, niveau].forEach(el => el.classList.remove('border-red-500'));
            [errCode, errNom, errFiliere, errNiveau].forEach(el => {
                if(el) {
                    el.classList.add('hidden');
                    el.textContent = '';
                }
            });

            if (!code.value.trim()) {
                code.classList.add('border-red-500');
                errCode.textContent = "Le code de la classe est obligatoire.";
                errCode.classList.remove('hidden');
                isValid = false;
            }
            if (!nom.value.trim()) {
                nom.classList.add('border-red-500');
                errNom.textContent = "Le nom de la classe est obligatoire.";
                errNom.classList.remove('hidden');
                isValid = false;
            }
            if (!filiere.value) {
                filiere.classList.add('border-red-500');
                errFiliere.textContent = "Veuillez sélectionner une filière.";
                errFiliere.classList.remove('hidden');
                isValid = false;
            }
            if (!niveau.value) {
                niveau.classList.add('border-red-500');
                errNiveau.textContent = "Veuillez sélectionner un niveau.";
                errNiveau.classList.remove('hidden');
                isValid = false;
            }

            if (!isValid) {
                event.preventDefault();
                return false;
            }
            return true;
        }
    </script>

    <?php if (isset($_GET['success'])): ?>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                <?php if ($_GET['success'] == '2'): ?>
                    showToast("Classe modifiée avec succès.", "success");
                <?php elseif ($_GET['success'] == '1'): ?>
                    showToast("Classe ajoutée avec succès.", "success");
                <?php endif; ?>
            });
        </script>
    <?php endif; ?>

</body>
</html>