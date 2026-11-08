<?php
$pageActuelle = 'etudiants'; 
$titrePage = 'Étudiants & Comptes'; 
$descriptionPage = 'Gérez les inscriptions et comptes étudiants'; 
require_once '../../config/db.php';

$tab = $_GET['tab'] ?? 'etudiants';
$recherche = trim($_GET['q'] ?? '');
$message = "";

$limiteParPage = 5;
$pageActuelleNum = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($pageActuelleNum < 1) $pageActuelleNum = 1;
$debut = ($pageActuelleNum - 1) * $limiteParPage;

// Traitement POST global (Étudiants et Utilisateurs)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $actionPost = $_POST['action'];

    if ($actionPost === 'ajouter_etudiant' || $actionPost === 'modifier_etudiant') {
        $id = $_POST['id'] ?? null;
        $matricule = trim($_POST['matricule'] ?? '');
        $nom = trim($_POST['nom'] ?? '');
        $prenom = trim($_POST['prenom'] ?? '');
        $date_naissance = trim($_POST['date_naissance'] ?? '');
        $sexe = trim($_POST['sexe'] ?? '');
        $telephone = trim($_POST['telephone'] ?? '');
        $adresse = trim($_POST['adresse'] ?? '');
        $classe_id = !empty($_POST['classe_id']) ? $_POST['classe_id'] : null;

        $erreurs = [];
        if (empty($matricule)) $erreurs[] = "Le matricule est obligatoire.";
        if (empty($nom)) $erreurs[] = "Le nom est obligatoire.";
        if (empty($prenom)) $erreurs[] = "Le prénom est obligatoire.";
        if (empty($telephone)) {
            $erreurs[] = "Le numéro de téléphone est obligatoire.";
        } else {
            $phoneRegex = '/^(?:\+221)?\s*(?:30|33|70|75|76|77|78)\s*\d{3}\s*\d{2}\s*\d{2}$/';
            if (!preg_match($phoneRegex, $telephone)) {
                $erreurs[] = "Le format du numéro sénégalais est invalide.";
            }
        }
        if (empty($classe_id)) $erreurs[] = "Veuillez sélectionner une classe.";

        if (empty($erreurs)) {
            try {
                if ($actionPost === 'ajouter_etudiant') {
                    $stmt = $pdo->prepare("INSERT INTO etudiants (matricule, nom, prenom, date_naissance, sexe, telephone, adresse, classe_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$matricule, $nom, $prenom, $date_naissance, $sexe, $telephone, $adresse, $classe_id]);
                    header("Location: etudiants.php?tab=etudiants&success=1");
                    exit();
                } elseif ($actionPost === 'modifier_etudiant' && !empty($id)) {
                    $stmt = $pdo->prepare("UPDATE etudiants SET matricule = ?, nom = ?, prenom = ?, date_naissance = ?, sexe = ?, telephone = ?, adresse = ?, classe_id = ? WHERE id = ?");
                    $stmt->execute([$matricule, $nom, $prenom, $date_naissance, $sexe, $telephone, $adresse, $classe_id, $id]);
                    header("Location: etudiants.php?tab=etudiants&success=2");
                    exit();
                }
            } catch (PDOException $e) {
                if ($e->getCode() === '23000') {
                    $message = "Erreur : Ce matricule existe déjà dans la base de données.";
                } else {
                    $message = "Erreur technique lors de l'enregistrement de l'étudiant.";
                }
            }
        } else {
            $message = implode("<br>", $erreurs);
        }
    } 
    elseif ($actionPost === 'ajouter_utilisateur') {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $message = "L'email et le mot de passe sont obligatoires pour créer un compte.";
        } else {
            try {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (email, password) VALUES (?, ?)");
                $stmt->execute([$email, $hashedPassword]);
                header("Location: etudiants.php?tab=utilisateurs&success=3");
                exit();
            } catch (PDOException $e) {
                if ($e->getCode() === '23000') {
                    $message = "Erreur : Cet email est déjà associé à un compte.";
                } else {
                    $message = "Erreur technique lors de la création du compte.";
                }
            }
        }
    }
}

$totalTotal = 0;
$etudiants = [];
$utilisateurs = [];
$classes = [];

try {
    if ($tab === 'utilisateurs') {
        $sqlCount = "SELECT COUNT(*) FROM users WHERE email LIKE :q1";
        $stmtCount = $pdo->prepare($sqlCount);
        $stmtCount->execute([':q1' => "%$recherche%"]);
        $totalTotal = $stmtCount->fetchColumn();

        $sql = "SELECT * FROM users WHERE email LIKE :q1 ORDER BY id ASC LIMIT :limite OFFSET :debut";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':q1', "%$recherche%", PDO::PARAM_STR);
    } else {
        $sqlCount = "SELECT COUNT(*) FROM etudiants WHERE nom LIKE :q1 OR prenom LIKE :q2 OR matricule LIKE :q3 OR telephone LIKE :q4";
        $stmtCount = $pdo->prepare($sqlCount);
        $stmtCount->execute([
            ':q1' => "%$recherche%",
            ':q2' => "%$recherche%",
            ':q3' => "%$recherche%",
            ':q4' => "%$recherche%"
        ]);
        $totalTotal = $stmtCount->fetchColumn();

        $sql = "SELECT e.*, c.code_classe, c.nom as nom_classe 
                FROM etudiants e 
                LEFT JOIN classes c ON e.classe_id = c.id 
                WHERE e.nom LIKE :q1 OR e.prenom LIKE :q2 OR e.matricule LIKE :q3 OR e.telephone LIKE :q4 
                ORDER BY e.id DESC LIMIT :limite OFFSET :debut";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':q1', "%$recherche%", PDO::PARAM_STR);
        $stmt->bindValue(':q2', "%$recherche%", PDO::PARAM_STR);
        $stmt->bindValue(':q3', "%$recherche%", PDO::PARAM_STR);
        $stmt->bindValue(':q4', "%$recherche%", PDO::PARAM_STR);
    }

    $stmt->bindValue(':limite', $limiteParPage, PDO::PARAM_INT);
    $stmt->bindValue(':debut', $debut, PDO::PARAM_INT);
    $stmt->execute();

    if ($tab === 'utilisateurs') {
        $utilisateurs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $etudiants = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $message = "Erreur de chargement des données.";
}

try {
    $classes = $pdo->query("SELECT * FROM classes ORDER BY nom ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $classes = [];
}

function fmtDate($d) {
    if (empty($d)) return '-';
    if (preg_match('/^\d{4}-\d{2}-\d{2}/', $d)) return date('d/m/Y', strtotime($d));
    return $d;
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
    <title>Administration — Étudiants et comptes</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 flex h-screen overflow-hidden font-sans">

    <?php include '../../includes/sidebar_secretaire.php'; ?>

    <main class="flex-1 flex flex-col h-screen overflow-hidden">
        <?php include '../../includes/header.php'; ?>

        <div class="flex-1 p-8 overflow-y-auto space-y-6">

            <div class="flex items-center space-x-2 border-b border-gray-200 pb-2">
                <a href="etudiants.php?tab=etudiants" class="px-3 py-1.5 rounded-lg text-xs font-semibold transition <?= ($tab !== 'utilisateurs') ? 'bg-red-50 text-red-600' : 'text-slate-500 hover:text-gray-800' ?>">
                    Étudiants
                </a>
                <a href="etudiants.php?tab=utilisateurs" class="px-3 py-1.5 rounded-lg text-xs font-semibold transition <?= ($tab === 'utilisateurs') ? 'bg-red-50 text-red-600' : 'text-slate-500 hover:text-gray-800' ?>">
                    Comptes
                </a>
            </div>

            <div class="flex items-center justify-between">
                <form id="searchForm" action="etudiants.php" method="GET" class="relative w-80">
                    <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
                    <input type="text" id="searchInput" name="q" value="<?= htmlspecialchars($recherche) ?>" placeholder="<?= ($tab !== 'utilisateurs') ? 'Rechercher un étudiant...' : 'Rechercher un compte...' ?>" class="w-full pl-4 pr-10 py-2.5 bg-white border border-gray-200 rounded-lg text-sm text-gray-700 placeholder-gray-400 focus:outline-none focus:border-red-600 transition" autocomplete="off">
                    <button type="submit" class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-gray-800 hover:text-red-600">
                        <i class="fa-solid fa-magnifying-glass text-sm"></i>
                    </button>
                </form>
                <button onclick="<?= ($tab !== 'utilisateurs') ? 'openAddStudentDrawer()' : 'openAddUserDrawer()' ?>" class="bg-red-600 text-white px-5 py-2.5 rounded-lg text-sm font-semibold hover:bg-red-700 transition flex items-center space-x-2 shadow-sm cursor-pointer">
                    <span><?= ($tab !== 'utilisateurs') ? 'Ajouter un étudiant' : 'Ajouter un utilisateur' ?></span>
                    <i class="fa-solid fa-plus text-xs"></i>
                </button>
            </div>

            <?php if (!empty($message)): ?>
                <div class="p-4 bg-red-50 border-l-4 border-red-500 text-red-700 text-sm rounded-lg shadow-sm">
                    <?= $message ?>
                </div>
            <?php endif; ?>

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

                <?php if ($tab !== 'utilisateurs'): ?>
                <!-- VUE TABLEAU ÉTUDIANTS -->
                <div id="view-table" class="overflow-x-auto">
                    <table class="w-full text-center border-collapse">
                        <thead>
                            <tr class="bg-gray-200 text-slate-500 text-[11px] uppercase tracking-wider">
                                <th class="py-4 px-4 font-semibold">Matricule</th>
                                <th class="py-4 px-4 font-semibold">Nom</th>
                                <th class="py-4 px-4 font-semibold">Prénom</th>
                                <th class="py-4 px-4 font-semibold">Date de naissance</th>
                                <th class="py-4 px-4 font-semibold">Sexe</th>
                                <th class="py-4 px-4 font-semibold">Téléphone</th>
                                <th class="py-4 px-4 font-semibold">Adresse</th>
                                <th class="py-4 px-4 font-semibold">Classe</th>
                                <th class="py-4 px-4 font-semibold">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-xs">
                            <?php if (empty($etudiants)): ?>
                                <tr><td colspan="9" class="py-6 text-gray-400">Aucun étudiant trouvé.</td></tr>
                            <?php else: foreach ($etudiants as $e): 
                                $jsonEtudiant = json_encode($e, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
                            ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="py-4 px-4 text-slate-500 whitespace-nowrap"><?= htmlspecialchars($e['matricule']) ?></td>
                                <td class="py-4 px-4 text-sm font-bold text-gray-900"><?= htmlspecialchars($e['nom']) ?></td>
                                <td class="py-4 px-4 text-sm font-bold text-gray-900"><?= htmlspecialchars($e['prenom']) ?></td>
                                <td class="py-4 px-4 text-slate-500"><?= htmlspecialchars(fmtDate($e['date_naissance'] ?? '')) ?></td>
                                <td class="py-4 px-4"><span class="inline-block bg-gray-100 text-gray-600 px-2.5 py-1 rounded-full text-[11px]"><?= htmlspecialchars($e['sexe'] ?? '-') ?></span></td>
                                <td class="py-4 px-4 text-slate-500 whitespace-nowrap"><?= htmlspecialchars($e['telephone'] ?? '-') ?></td>
                                <td class="py-4 px-4 text-slate-500 whitespace-nowrap"><?= htmlspecialchars($e['adresse'] ?? '-') ?></td>
                                <td class="py-4 px-4"><span class="inline-block bg-blue-50 text-blue-600 px-2.5 py-1 rounded-full font-medium text-[11px]"><?= htmlspecialchars($e['code_classe'] ?? '-') ?></span></td>
                                <td class="py-4 px-4 space-x-3 whitespace-nowrap">
                                    <button type="button" onclick='openEditDrawer(<?= $jsonEtudiant ?>)' class="text-slate-500 hover:text-blue-600 transition cursor-pointer" title="Modifier"><i class="fa-solid fa-pen"></i></button>
                                    <button type="button" onclick="openDeleteModalAsync(<?= $e['id'] ?>, '<?= htmlspecialchars($e['prenom'] . ' ' . $e['nom'], ENT_QUOTES) ?>', this.closest('tr'), 'supprimer_etudiant')" class="text-slate-500 hover:text-red-600 transition cursor-pointer" title="Supprimer"><i class="fa-regular fa-trash-can"></i></button>
                                </td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- VUE CARTES ÉTUDIANTS -->
                <div id="view-cards" class="hidden p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                        <?php foreach ($etudiants as $e): 
                            $jsonEtudiantCard = json_encode($e, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
                        ?>
                        <div class="bg-white border border-gray-100 rounded-xl shadow-md p-4">
                            <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                                <span class="text-sm text-gray-800"><?= htmlspecialchars($e['matricule']) ?></span>
                                <div class="flex items-center space-x-3 text-slate-500">
                                    <button type="button" onclick='openEditDrawer(<?= $jsonEtudiantCard ?>)' class="hover:text-blue-600 transition cursor-pointer" title="Modifier"><i class="fa-solid fa-pen text-xs"></i></button>
                                    <button type="button" onclick="openDeleteModalAsync(<?= $e['id'] ?>, '<?= htmlspecialchars($e['prenom'] . ' ' . $e['nom'], ENT_QUOTES) ?>', this.closest('div.bg-white'), 'supprimer_etudiant')" class="hover:text-red-600 transition cursor-pointer" title="Supprimer"><i class="fa-regular fa-trash-can text-xs"></i></button>
                                </div>
                            </div>
                            <div class="pt-3 space-y-3">
                                <div>
                                    <p class="text-xs text-slate-500">Nom & Prénom</p>
                                    <p class="text-sm font-bold text-gray-900"><?= htmlspecialchars($e['nom'] . ' ' . $e['prenom']) ?></p>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-500">Téléphone</p>
                                    <p class="text-xs text-slate-600"><?= htmlspecialchars($e['telephone'] ?? '-') ?></p>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-500 mb-1">Classe</p>
                                    <span class="inline-block bg-blue-50 text-blue-700 px-2.5 py-1 rounded-full text-[11px] font-medium"><?= htmlspecialchars($e['code_classe'] ?? '-') ?></span>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php else: ?>
                <!-- VUE TABLEAU COMPTES -->
                <div id="view-table" class="overflow-x-auto">
                    <table class="w-full text-center border-collapse">
                        <thead>
                            <tr class="bg-gray-200 text-slate-500 text-[11px] uppercase tracking-wider">
                                <th class="py-4 px-4 font-semibold">ID utilisateur</th>
                                <th class="py-4 px-4 font-semibold">E-mail</th>
                                <th class="py-4 px-4 font-semibold">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-xs">
                            <?php if (empty($utilisateurs)): ?>
                                <tr><td colspan="3" class="py-6 text-gray-400">Aucun utilisateur trouvé.</td></tr>
                            <?php else: foreach ($utilisateurs as $u): ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="py-5 px-4 text-sm text-gray-800"><?= htmlspecialchars($u['id']) ?></td>
                                <td class="py-5 px-4 font-bold text-gray-900"><?= htmlspecialchars($u['email']) ?></td>
                                <td class="py-5 px-4 space-x-3 whitespace-nowrap">
                                    <button type="button" onclick="openDeleteModalAsync(<?= $u['id'] ?>, '<?= htmlspecialchars($u['email'], ENT_QUOTES) ?>', this.closest('tr'), 'supprimer_utilisateur')" class="text-slate-500 hover:text-red-600 transition cursor-pointer" title="Supprimer"><i class="fa-regular fa-trash-can"></i></button>
                                </td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- VUE CARTES COMPTES -->
                <div id="view-cards" class="hidden p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                        <?php if (empty($utilisateurs)): ?>
                            <p class="text-gray-400 text-xs">Aucun utilisateur trouvé.</p>
                        <?php else: foreach ($utilisateurs as $u): ?>
                        <div class="bg-white border border-gray-100 rounded-xl shadow-md p-4">
                            <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                                <span class="text-xs font-bold text-gray-500">ID : #<?= $u['id'] ?></span>
                                <div class="flex items-center space-x-3 text-slate-500">
                                    <button type="button" onclick="openDeleteModalAsync(<?= $u['id'] ?>, '<?= htmlspecialchars($u['email'], ENT_QUOTES) ?>', this.closest('div.bg-white'), 'supprimer_utilisateur')" class="hover:text-red-600 transition cursor-pointer" title="Supprimer"><i class="fa-regular fa-trash-can text-xs"></i></button>
                                </div>
                            </div>
                            <div class="pt-3 space-y-2">
                                <div>
                                    <p class="text-xs text-slate-500">Adresse E-mail</p>
                                    <p class="text-sm font-bold text-gray-900"><?= htmlspecialchars($u['email']) ?></p>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <div class="flex justify-between items-center px-6 py-4 border-t border-gray-100 text-xs text-slate-500">
                    <span><?= $debutAffichage ?>–<?= $finAffichage ?> sur <?= $totalTotal ?></span>
                    
                    <?php if ($totalTotal > 5): ?>
                    <div class="flex items-center space-x-3">
                        <span>Page <?= $pageActuelleNum ?> sur <?= $totalPages ?></span>
                        <div class="flex space-x-1">
                            <?php if ($pageActuelleNum > 1): ?>
                                <a href="etudiants.php?tab=<?= $tab ?>&q=<?= urlencode($recherche) ?>&page=<?= $pageActuelleNum - 1 ?>" class="w-8 h-8 flex items-center justify-center border border-gray-200 rounded-lg text-gray-600 hover:bg-gray-100 transition"><i class="fa-solid fa-chevron-left text-[10px]"></i></a>
                            <?php else: ?>
                                <span class="w-8 h-8 flex items-center justify-center border border-gray-200 rounded-lg text-gray-300 cursor-not-allowed"><i class="fa-solid fa-chevron-left text-[10px]"></i></span>
                            <?php endif; ?>

                            <?php if ($pageActuelleNum < $totalPages): ?>
                                <a href="etudiants.php?tab=<?= $tab ?>&q=<?= urlencode($recherche) ?>&page=<?= $pageActuelleNum + 1 ?>" class="w-8 h-8 flex items-center justify-center border border-gray-200 rounded-lg text-gray-600 hover:bg-gray-100 transition"><i class="fa-solid fa-chevron-right text-[10px]"></i></a>
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
                <h3 id="drawer-title" class="font-bold text-base text-gray-800">Nouvel étudiant</h3>
                <button onclick="toggleDrawer(false)" class="text-gray-400 hover:text-gray-600 text-xl font-bold cursor-pointer">&times;</button>
            </div>

            <form id="addStudentForm" action="etudiants.php?tab=<?= $tab ?>" method="POST" class="p-6 space-y-4" onsubmit="return validateForm(event)">
                <input type="hidden" id="form-action" name="action" value="ajouter_etudiant">
                <input type="hidden" id="etudiant_id" name="id" value="">
                
                <div id="fields-etudiant" class="space-y-4">
                    <h4 class="text-xs font-bold text-red-600 uppercase tracking-wider">Information étudiant</h4>

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Matricule*</label>
                        <input type="text" id="matricule" name="matricule" placeholder="ESP221-2024-007" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600">
                        <p id="error-matricule" class="text-xs text-red-500 mt-1 hidden"></p>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Nom*</label>
                            <input type="text" id="nom" name="nom" placeholder="Diallo" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600">
                            <p id="error-nom" class="text-xs text-red-500 mt-1 hidden"></p>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Prénom*</label>
                            <input type="text" id="prenom" name="prenom" placeholder="Mamadou" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600">
                            <p id="error-prenom" class="text-xs text-red-500 mt-1 hidden"></p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Date de naissance</label>
                        <input type="date" id="date_naissance" name="date_naissance" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm bg-white focus:outline-none focus:border-red-600">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Sexe</label>
                        <select id="sexe" name="sexe" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm bg-white focus:outline-none focus:border-red-600">
                            <option value="Masculin">Masculin</option>
                            <option value="Féminin">Féminin</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Téléphone*</label>
                        <input type="text" id="telephone" name="telephone" placeholder="+221 77 123 45 67" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600">
                        <p id="error-telephone" class="text-xs text-red-500 mt-1 hidden"></p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Adresse</label>
                        <input type="text" id="adresse" name="adresse" placeholder="Ville, Quartier" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Classe*</label>
                        <select id="classe_id" name="classe_id" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm bg-white focus:outline-none focus:border-red-600">
                            <option value="">Sélectionner une classe</option>
                            <?php foreach ($classes as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p id="error-classe" class="text-xs text-red-500 mt-1 hidden"></p>
                    </div>
                </div>

                <div id="fields-utilisateur" class="space-y-4 hidden">
                    <h4 class="text-xs font-bold text-red-600 uppercase tracking-wider">Information compte utilisateur</h4>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Adresse E-mail*</label>
                        <input type="email" id="user_email" name="email" placeholder="utilisateur@esp221.sn" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Mot de passe*</label>
                        <input type="password" id="user_password" name="password" placeholder="••••••••" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600">
                    </div>
                </div>

                <div class="pt-4 flex items-center justify-end space-x-3 border-t border-gray-100">
                    <button type="button" onclick="toggleDrawer(false)" class="px-4 py-2.5 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition cursor-pointer">
                        Annuler
                    </button>
                    <button type="submit" id="submit-btn" class="px-4 py-2.5 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition shadow-sm cursor-pointer">
                        Ajouter
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

            msgEl.textContent = `Voulez-vous vraiment supprimer "${itemName}" ?`;

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

                        const isStudent = actionType === 'supprimer_etudiant';
                        const isUser = actionType === 'supprimer_utilisateur';
                        const restoreAction = isStudent ? 'restaurer_etudiant' : (isUser ? 'restaurer_utilisateur' : null);

                        showToast("Élément supprimé avec succès.", "error", restoreAction ? () => {
                            fetch('action.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({ action: restoreAction, data: deletedData })
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
                        } : null);
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

        function openAddStudentDrawer() {
            document.getElementById('drawer-title').textContent = "Nouvel étudiant";
            document.getElementById('form-action').value = "ajouter_etudiant";
            document.getElementById('etudiant_id').value = "";
            document.getElementById('submit-btn').textContent = "Ajouter";
            
            document.getElementById('fields-etudiant').classList.remove('hidden');
            document.getElementById('fields-utilisateur').classList.add('hidden');
            document.getElementById('addStudentForm').reset();
            toggleDrawer(true);
        }

        function openAddUserDrawer() {
            document.getElementById('drawer-title').textContent = "Nouvel utilisateur";
            document.getElementById('form-action').value = "ajouter_utilisateur";
            document.getElementById('submit-btn').textContent = "Créer le compte";
            
            document.getElementById('fields-etudiant').classList.add('hidden');
            document.getElementById('fields-utilisateur').classList.remove('hidden');
            document.getElementById('addStudentForm').reset();
            toggleDrawer(true);
        }

        function openEditDrawer(etudiant) {
            document.getElementById('drawer-title').textContent = "Modifier l'étudiant";
            document.getElementById('form-action').value = "modifier_etudiant";
            document.getElementById('etudiant_id').value = etudiant.id;
            document.getElementById('submit-btn').textContent = "Modifier";

            document.getElementById('fields-etudiant').classList.remove('hidden');
            document.getElementById('fields-utilisateur').classList.add('hidden');

            document.getElementById('matricule').value = etudiant.matricule || '';
            document.getElementById('nom').value = etudiant.nom || '';
            document.getElementById('prenom').value = etudiant.prenom || '';
            document.getElementById('date_naissance').value = etudiant.date_naissance || '';
            document.getElementById('sexe').value = etudiant.sexe || 'Masculin';
            document.getElementById('telephone').value = etudiant.telephone || '';
            document.getElementById('adresse').value = etudiant.adresse || '';
            document.getElementById('classe_id').value = etudiant.classe_id || '';

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
            const actionVal = document.getElementById('form-action').value;
            if (actionVal === 'ajouter_utilisateur') return true;

            let isValid = true;
            const matricule = document.getElementById('matricule');
            const nom = document.getElementById('nom');
            const prenom = document.getElementById('prenom');
            const telephone = document.getElementById('telephone');
            const classe = document.getElementById('classe_id');

            const errMatricule = document.getElementById('error-matricule');
            const errNom = document.getElementById('error-nom');
            const errPrenom = document.getElementById('error-prenom');
            const errTelephone = document.getElementById('error-telephone');
            const errClasse = document.getElementById('error-classe');

            [matricule, nom, prenom, telephone, classe].forEach(el => el.classList.remove('border-red-500'));
            [errMatricule, errNom, errPrenom, errTelephone, errClasse].forEach(el => {
                if(el) {
                    el.classList.add('hidden');
                    el.textContent = '';
                }
            });

            if (!matricule.value.trim()) {
                matricule.classList.add('border-red-500');
                errMatricule.textContent = "Le matricule est obligatoire.";
                errMatricule.classList.remove('hidden');
                isValid = false;
            }
            if (!nom.value.trim()) {
                nom.classList.add('border-red-500');
                errNom.textContent = "Le nom est obligatoire.";
                errNom.classList.remove('hidden');
                isValid = false;
            }
            if (!prenom.value.trim()) {
                prenom.classList.add('border-red-500');
                errPrenom.textContent = "Le prénom est obligatoire.";
                errPrenom.classList.remove('hidden');
                isValid = false;
            }

            const phoneVal = telephone.value.trim();
            const phoneRegex = /^(?:\+221)?\s*(?:30|33|70|75|76|77|78)\s*\d{3}\s*\d{2}\s*\d{2}$/;

            if (!phoneVal) {
                telephone.classList.add('border-red-500');
                errTelephone.textContent = "Le numéro de téléphone est obligatoire.";
                errTelephone.classList.remove('hidden');
                isValid = false;
            } else if (!phoneRegex.test(phoneVal)) {
                telephone.classList.add('border-red-500');
                errTelephone.textContent = "Format invalide (ex: +221 77 123 45 67).";
                errTelephone.classList.remove('hidden');
                isValid = false;
            }

            if (!classe.value) {
                classe.classList.add('border-red-500');
                errClasse.textContent = "Veuillez sélectionner une classe.";
                errClasse.classList.remove('hidden');
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
                <?php if ($_GET['success'] == '3'): ?>
                    showToast("Utilisateur créé avec succès.", "success");
                <?php elseif ($_GET['success'] == '2'): ?>
                    showToast("Étudiant modifié avec succès.", "success");
                <?php elseif ($_GET['success'] == '1'): ?>
                    showToast("Étudiant ajouté avec succès.", "success");
                <?php endif; ?>
            });
        </script>
    <?php endif; ?>

</body>
</html>