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

// Traitement de l'ajout d'une classe
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'ajouter_classe') {
    $code_classe = trim($_POST['code_classe'] ?? '');
    $nom = trim($_POST['nom'] ?? '');
    $capacite = trim($_POST['capacite'] ?? '35 places');
    $filiere_id = !empty($_POST['filiere_id']) ? $_POST['filiere_id'] : null;
    $niveau_id = !empty($_POST['niveau_id']) ? $_POST['niveau_id'] : null;

    $erreurs = [];

    if (empty($code_classe)) {
        $erreurs[] = "Le code de la classe est obligatoire.";
    }
    if (empty($nom)) {
        $erreurs[] = "Le nom de la classe est obligatoire.";
    }
    if (empty($filiere_id)) {
        $erreurs[] = "Veuillez sélectionner une filière.";
    }
    if (empty($niveau_id)) {
        $erreurs[] = "Veuillez sélectionner un niveau.";
    }

    if (empty($erreurs)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO classes (code_classe, nom, capacite, filiere_id, niveau_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$code_classe, $nom, $capacite, $filiere_id, $niveau_id]);
            header("Location: classes.php?success=1");
            exit();
        } catch (PDOException $e) {
            $message = "Erreur : Ce code de classe existe déjà en base de données.";
        }
    } else {
        $message = implode("<br>", $erreurs);
    }
}

// Récupération des données avec jointures
$totalTotal = 0;
$classes = [];
$filieres = [];
$niveaux = [];

try {
    $sqlCount = "SELECT COUNT(*) FROM classes c WHERE c.nom LIKE :q OR c.code_classe LIKE :q";
    $stmtCount = $pdo->prepare($sqlCount);
    $stmtCount->execute([':q' => "%$recherche%"]);
    $totalTotal = $stmtCount->fetchColumn();

    $sql = "SELECT c.*, f.nom as nom_filiere, n.libelle as nom_niveau 
            FROM classes c 
            LEFT JOIN filieres f ON c.filiere_id = f.id 
            LEFT JOIN niveaux n ON c.niveau_id = n.id 
            WHERE c.nom LIKE :q OR c.code_classe LIKE :q 
            ORDER BY c.id DESC LIMIT :limite OFFSET :debut";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':q', "%$recherche%", PDO::PARAM_STR);
    $stmt->bindValue(':limite', $limiteParPage, PDO::PARAM_INT);
    $stmt->bindValue(':debut', $debut, PDO::PARAM_INT);
    $stmt->execute();
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Récupération des listes pour les select (en utilisant 'libelle' pour les niveaux)
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

            <div class="flex items-center justify-between">
                <form action="classes.php" method="GET" class="relative w-80">
                    <input type="text" name="q" value="<?= htmlspecialchars($recherche) ?>" placeholder="Rechercher une classe..." class="w-full pl-4 pr-10 py-2.5 bg-white border border-gray-200 rounded-lg text-sm text-gray-700 placeholder-gray-400 focus:outline-none focus:border-red-600 transition" onchange="this.form.submit()">
                    <button type="submit" class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-gray-800 hover:text-red-600">
                        <i class="fa-solid fa-magnifying-glass text-sm"></i>
                    </button>
                </form>
                <button onclick="toggleDrawer(true)" class="bg-red-600 text-white px-5 py-2.5 rounded-lg text-sm font-semibold hover:bg-red-700 transition flex items-center space-x-2 shadow-sm">
                    <span>Ajouter une classe</span>
                    <i class="fa-solid fa-plus text-xs"></i>
                </button>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
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
                            <?php else: foreach ($classes as $c): ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="py-4 px-4"><span class="bg-gray-100 text-gray-600 px-2.5 py-1 rounded-md font-medium text-[11px]"><?= htmlspecialchars($c['code_classe']) ?></span></td>
                                <td class="py-4 px-4 text-sm font-bold text-gray-900"><?= htmlspecialchars($c['nom']) ?></td>
                                <td class="py-4 px-4 font-medium text-gray-800"><?= htmlspecialchars($c['capacite'] ?? '35 places') ?></td>
                                <td class="py-4 px-4"><span class="bg-blue-50 text-blue-600 px-2.5 py-1 rounded-full font-medium text-[11px]"><?= htmlspecialchars($c['nom_filiere'] ?? '-') ?></span></td>
                                <td class="py-4 px-4"><span class="bg-gray-100 text-gray-600 px-2.5 py-1 rounded-full text-[11px]"><?= htmlspecialchars($c['nom_niveau'] ?? '-') ?></span></td>
                                <td class="py-4 px-4 space-x-3 whitespace-nowrap">
                                    <button class="text-slate-500 hover:text-blue-600 transition" title="Modifier"><i class="fa-solid fa-pen"></i></button>
                                    <button onclick="openDeleteModal('supprimer_classe.php?id=<?= $c['id'] ?>', 'Voulez-vous vraiment supprimer la classe <?= htmlspecialchars($c['code_classe']) ?> ?')" class="text-slate-500 hover:text-red-600 transition" title="Supprimer"><i class="fa-regular fa-trash-can"></i></button>
                                </td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>

                <div id="view-cards" class="hidden p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                        <?php foreach ($classes as $c): ?>
                        <div class="bg-white border border-gray-100 rounded-xl shadow-md p-4">
                            <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                                <span class="text-sm font-bold text-gray-800"><?= htmlspecialchars($c['code_classe']) ?></span>
                                <div class="flex items-center space-x-3 text-slate-500">
                                    <button class="hover:text-blue-600 transition" title="Modifier"><i class="fa-solid fa-pen text-xs"></i></button>
                                    <button onclick="openDeleteModal('supprimer_classe.php?id=<?= $c['id'] ?>', 'Voulez-vous vraiment supprimer la classe <?= htmlspecialchars($c['code_classe']) ?> ?')" class="hover:text-red-600 transition" title="Supprimer"><i class="fa-regular fa-trash-can text-xs"></i></button>
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
                        <?php endforeach; ?>
                    </div>
                </div>

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

    <!-- DRAWER LATÉRAL D'AJOUT -->
    <div id="drawer-overlay" class="fixed inset-0 bg-black/50 z-50 hidden transition-opacity opacity-0" onclick="toggleDrawer(false)"></div>
    
    <div id="drawer" class="fixed top-0 right-0 h-full w-[440px] bg-white shadow-2xl z-50 transform translate-x-full transition-transform duration-300 ease-in-out flex flex-col justify-between overflow-y-auto">
        <div>
            <div class="p-6 border-b border-gray-100 flex justify-between items-center sticky top-0 bg-white z-10">
                <h3 class="font-bold text-base text-gray-800">Nouvelle classe</h3>
                <button onclick="toggleDrawer(false)" class="text-gray-400 hover:text-gray-600 text-xl font-bold">&times;</button>
            </div>

            <form id="addClassForm" action="classes.php" method="POST" class="p-6 space-y-4" onsubmit="return validateForm(event)">
                <input type="hidden" name="action" value="ajouter_classe">
                
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
                        <input type="text" name="capacite" value="35 places" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600">
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

                <div class="pt-4 flex items-center justify-end space-x-3 border-t border-gray-100">
                    <button type="button" onclick="toggleDrawer(false)" class="px-4 py-2.5 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition">
                        Annuler
                    </button>
                    <button type="submit" class="px-4 py-2.5 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition shadow-sm">
                        Ajouter
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
                el.classList.add('hidden');
                el.textContent = '';
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

    <?php include '../../includes/modal_toast.php'; ?>

    <?php if (isset($_GET['success'])): ?>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                showToast("Classe ajoutée avec succès.", "success");
            });
        </script>
    <?php endif; ?>

</body>
</html>