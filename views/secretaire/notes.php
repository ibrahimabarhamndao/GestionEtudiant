<?php
$pageActuelle = 'notes';
$titrePage = 'Notes & Évaluations';
$descriptionPage = 'Suivi et consultation des notes des étudiants';

require_once '../../config/db.php';

$message = '';
$recherche = trim($_GET['q'] ?? '');

$limiteParPage = 5;
$pageActuelleNum = max(1, (int)($_GET['page'] ?? 1));

$typesValides = ['CC', 'Examen', 'Projet'];

function fmtNote($v) {
    return rtrim(rtrim(number_format((float)$v, 2, '.', ''), '0'), '.');
}

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

$champs = ['etudiant_id', 'matiere', 'type', 'valeur', 'general'];
$erreurs = array_fill_keys($champs, '');

// Valeurs affichées dans le formulaire (conservées en cas d'erreur)
$ancien = [
    'action'      => 'ajouter_note',
    'id'          => '',
    'etudiant_id' => '',
    'matiere'     => '',
    'type'        => 'CC',
    'valeur'      => '',
];
$aDesErreurs = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])
    && in_array($_POST['action'], ['ajouter_note', 'modifier_note'], true)) {

    $actionPost = $_POST['action'];
    $id = (int)($_POST['id'] ?? 0);
    $etudiant_id = (int)($_POST['etudiant_id'] ?? 0);
    $matiere = trim($_POST['matiere'] ?? '');
    $type = trim($_POST['type'] ?? '');
    $valeurSaisie = trim((string)($_POST['valeur'] ?? ''));
    $valeur = str_replace(',', '.', $valeurSaisie);

    $ancien = [
        'action'      => $actionPost,
        'id'          => $id > 0 ? $id : '',
        'etudiant_id' => $etudiant_id > 0 ? $etudiant_id : '',
        'matiere'     => $matiere,
        'type'        => $type,
        'valeur'      => $valeurSaisie,
    ];

    try {

        // --- Étudiant ---
        if ($etudiant_id <= 0) {
            $erreurs['etudiant_id'] = "Veuillez sélectionner un étudiant.";
        } else {
            $check = $pdo->prepare("SELECT id FROM etudiants WHERE id = ?");
            $check->execute([$etudiant_id]);
            if (!$check->fetch(PDO::FETCH_ASSOC)) {
                $erreurs['etudiant_id'] = "L'étudiant sélectionné n'existe pas.";
            }
        }

        // --- Matière ---
        if ($matiere === '') {
            $erreurs['matiere'] = "La matière est obligatoire.";
        } elseif (mb_strlen($matiere) < 2) {
            $erreurs['matiere'] = "La matière doit contenir au moins 2 caractères.";
        } elseif (mb_strlen($matiere) > 100) {
            $erreurs['matiere'] = "La matière ne doit pas dépasser 100 caractères.";
        }

        // --- Type d'évaluation ---
        if (!in_array($type, $typesValides, true)) {
            $erreurs['type'] = "Veuillez choisir un type d'évaluation valide (CC, Examen ou Projet).";
        }

        // --- Note ---
        if ($valeur === '') {
            $erreurs['valeur'] = "La note est obligatoire.";
        } elseif (!preg_match('/^-?\d+(\.\d+)?$/', $valeur)) {
            $erreurs['valeur'] = "La note doit être un nombre (ex : 15 ou 15.5).";
        } elseif ((float)$valeur < 0 || (float)$valeur > 20) {
            $erreurs['valeur'] = "La note doit être comprise entre 0 et 20.";
        }

        // --- Modification : la note doit exister ---
        if ($actionPost === 'modifier_note') {
            if ($id <= 0) {
                $erreurs['general'] = "Identifiant de note invalide.";
            } else {
                $checkNote = $pdo->prepare("SELECT id FROM notes WHERE id = ?");
                $checkNote->execute([$id]);
                if (!$checkNote->fetch(PDO::FETCH_ASSOC)) {
                    $erreurs['general'] = "La note à modifier est introuvable.";
                }
            }
        }

        // --- Enregistrement si aucune erreur ---
        if (implode('', $erreurs) === '') {

            if ($actionPost === 'ajouter_note') {

                $stmt = $pdo->prepare("
                    INSERT INTO notes (etudiant_id, matiere, type, valeur)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$etudiant_id, $matiere, $type, (float)$valeur]);

                header("Location: notes.php?success=1");
                exit();
            }

            $stmt = $pdo->prepare("
                UPDATE notes
                SET etudiant_id = ?, matiere = ?, type = ?, valeur = ?
                WHERE id = ?
            ");
            $stmt->execute([$etudiant_id, $matiere, $type, (float)$valeur, $id]);

            header("Location: notes.php?success=2");
            exit();
        }

    } catch (PDOException $e) {

        error_log("Erreur CRUD notes : " . $e->getMessage());
        $erreurs['general'] = "Erreur lors de l'enregistrement de la note.";
    }

    $aDesErreurs = implode('', $erreurs) !== '';
}

$modeEdition = ($ancien['action'] === 'modifier_note');

// ==========================================
// RÉCUPÉRATION DES ÉTUDIANTS
// ==========================================

$etudiants = [];

try {

    $stmt = $pdo->query("
        SELECT id, nom, prenom, matricule
        FROM etudiants
        ORDER BY nom ASC
    ");

    $etudiants = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    error_log("Erreur récupération étudiants : " . $e->getMessage());
    $message = "Impossible de récupérer les étudiants.";

}

// ==========================================
// RECHERCHE ET PAGINATION
// ==========================================

$notes = [];
$toutesLesNotes = [];
$totalTotal = 0;
$totalPages = 1;
$debut = 0;

try {

    $where = "
        FROM notes n
        LEFT JOIN etudiants e ON n.etudiant_id = e.id
        WHERE (
            n.matiere LIKE :q1
            OR n.type LIKE :q2
            OR e.nom LIKE :q3
            OR e.prenom LIKE :q4
            OR e.matricule LIKE :q5
        )
    ";

    $params = [
        ':q1' => "%$recherche%",
        ':q2' => "%$recherche%",
        ':q3' => "%$recherche%",
        ':q4' => "%$recherche%",
        ':q5' => "%$recherche%"
    ];

    $stmtCount = $pdo->prepare("SELECT COUNT(*) " . $where);
    $stmtCount->execute($params);

    $totalTotal = (int)$stmtCount->fetchColumn();

    $totalPages = max(1, (int)ceil($totalTotal / $limiteParPage));

    if ($pageActuelleNum > $totalPages) {
        $pageActuelleNum = $totalPages;
    }

    $debut = ($pageActuelleNum - 1) * $limiteParPage;

    $sql = "
        SELECT n.*, e.nom, e.prenom, e.matricule
        " . $where . "
        ORDER BY n.id DESC
        LIMIT :limite OFFSET :debut
    ";

    $stmt = $pdo->prepare($sql);

    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value, PDO::PARAM_STR);
    }

    $stmt->bindValue(':limite', $limiteParPage, PDO::PARAM_INT);
    $stmt->bindValue(':debut', $debut, PDO::PARAM_INT);

    $stmt->execute();

    $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Vue "Cartes" (seule vue affichée sur mobile) : on applique aussi la recherche
    $stmtCartes = $pdo->prepare("
        SELECT n.*, e.nom, e.prenom, e.matricule
        " . $where . "
        ORDER BY n.id DESC
    ");
    $stmtCartes->execute($params);
    $toutesLesNotes = $stmtCartes->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    error_log("Erreur chargement notes : " . $e->getMessage());
    $message = "Erreur lors du chargement des notes.";

    $notes = [];
    $toutesLesNotes = [];
    $totalTotal = 0;
    $totalPages = 1;
    $debut = 0;

}

$debutAffichage = $totalTotal > 0 ? $debut + 1 : 0;
$finAffichage = min($debut + $limiteParPage, $totalTotal);

// ==========================================
// REGROUPEMENT PAR MATIÈRE
// ==========================================

$parMatiere = [];

foreach ($toutesLesNotes as $n) {
    $parMatiere[$n['matiere']][] = $n;
}

?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Administration — Notes</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="bg-gray-50 flex h-screen overflow-hidden font-sans">

<?php include '../../includes/sidebar_secretaire.php'; ?>

<main class="flex-1 min-w-0 flex flex-col h-screen overflow-hidden">

    <?php include '../../includes/header.php'; ?>

    <div class="flex-1 p-4 sm:p-6 md:p-8 overflow-y-auto space-y-6">

        <?php if ($message !== ''): ?>
            <div class="p-4 bg-red-50 border-l-4 border-red-500 text-red-700 text-sm rounded-lg shadow-sm">
                <?= h($message) ?>
            </div>
        <?php endif; ?>

        <!-- BARRE D'OUTILS -->

        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-4">

            <form id="searchForm" action="notes.php" method="GET" class="relative w-full sm:w-80">

                <input
                    type="text"
                    id="searchInput"
                    name="q"
                    value="<?= h($recherche) ?>"
                    placeholder="Rechercher une note ou un étudiant..."
                    class="w-full pl-4 pr-10 py-2.5 bg-white border border-gray-200 rounded-lg text-sm text-gray-700 placeholder-gray-400 focus:outline-none focus:border-red-600 transition"
                    autocomplete="off"
                >

                <button type="submit"
                        class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-gray-800 hover:text-red-600">
                    <i class="fa-solid fa-magnifying-glass text-sm"></i>
                </button>

            </form>

            <button onclick="openAddDrawer()"
                    type="button"
                    class="bg-red-600 text-white px-5 py-2.5 rounded-lg text-sm font-semibold hover:bg-red-700 transition flex items-center space-x-2 shadow-sm justify-center shrink-0">

                <span>Saisir une note</span>
                <i class="fa-solid fa-plus text-xs"></i>

            </button>

        </div>

        <!-- CONTENU PRINCIPAL -->

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">

            <div class="hidden lg:flex justify-end px-4 py-3 border-b border-gray-100">

                <div class="bg-gray-100 rounded-lg p-1 flex text-xs font-medium">

                    <button id="btn-table"
                            type="button"
                            onclick="setView('table')"
                            class="px-3 py-1.5 rounded-md flex items-center space-x-1.5 transition bg-white text-red-600 shadow-sm">

                        <i class="fa-solid fa-table"></i>
                        <span>Tableau</span>

                    </button>

                    <button id="btn-cards"
                            type="button"
                            onclick="setView('cards')"
                            class="px-3 py-1.5 rounded-md flex items-center space-x-1.5 transition text-gray-500 hover:text-gray-800">

                        <i class="fa-solid fa-book"></i>
                        <span>Cartes</span>

                    </button>

                </div>

            </div>

            <!-- VUE TABLEAU -->

            <div id="view-table" class="overflow-x-auto">

                <table class="w-full text-center border-collapse">

                    <thead>
                        <tr class="bg-gray-200 text-slate-500 text-[11px] uppercase tracking-wider">

                            <th class="py-4 px-6 font-semibold text-left">Étudiant</th>
                            <th class="py-4 px-4 font-semibold">Matière / Module</th>
                            <th class="py-4 px-4 font-semibold">Type</th>
                            <th class="py-4 px-4 font-semibold">Note / 20</th>
                            <th class="py-4 px-6 font-semibold">Actions</th>

                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100 text-sm">

                    <?php if (empty($notes)): ?>

                        <tr>
                            <td colspan="5" class="py-6 text-gray-400 text-center text-xs">
                                Aucune note trouvée.
                            </td>
                        </tr>

                    <?php else: ?>

                        <?php foreach ($notes as $n): ?>

                            <?php
                            $noteJson = json_encode(
                                $n,
                                JSON_HEX_TAG |
                                JSON_HEX_APOS |
                                JSON_HEX_QUOT |
                                JSON_HEX_AMP
                            );
                            ?>

                            <tr class="hover:bg-gray-50 transition">

                                <td class="py-4 px-6 text-left">

                                    <span class="block font-bold text-gray-900">
                                        <?= h(trim(($n['prenom'] ?? '') . ' ' . ($n['nom'] ?? 'Étudiant inconnu'))) ?>
                                    </span>

                                    <span class="block text-xs text-slate-500">
                                        <?= h($n['matricule'] ?? '') ?>
                                    </span>

                                </td>

                                <td class="py-4 px-4 text-xs text-slate-600">
                                    <?= h($n['matiere']) ?>
                                </td>

                                <td class="py-4 px-4">

                                    <span class="inline-block bg-gray-100 text-gray-600 px-2.5 py-1 rounded-full text-[11px] font-medium">
                                        <?= h($n['type']) ?>
                                    </span>

                                </td>

                                <td class="py-4 px-4 font-bold text-gray-900">
                                    <?= h(fmtNote($n['valeur'])) ?>/20
                                </td>

                                <td class="py-4 px-6 space-x-3 whitespace-nowrap">

                                    <!-- BOUTON MODIFIER -->

                                    <button
                                        type="button"
                                        class="text-slate-500 hover:text-blue-600 transition btn-edit-note"
                                        data-note="<?= h($noteJson) ?>"
                                        title="Modifier">

                                        <i class="fa-solid fa-pen"></i>

                                    </button>

                                    <!-- BOUTON SUPPRIMER -->

                                    <button
                                        type="button"
                                        onclick="openDeleteModalAsync(<?= (int)$n['id'] ?>, <?= h(json_encode($n['matiere'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)) ?>, this.closest('tr'), 'supprimer_note')"
                                        class="text-slate-500 hover:text-red-600 transition"
                                        title="Supprimer">

                                        <i class="fa-regular fa-trash-can"></i>

                                    </button>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

            <!-- VUE PAR MATIÈRE -->

            <div id="view-cards" class="hidden p-4 sm:p-6">

                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 items-start">

                    <?php if (empty($parMatiere)): ?>

                        <p class="text-gray-400 text-sm col-span-full text-center py-6">
                            Aucune matière disponible.
                        </p>

                    <?php endif; ?>

                    <?php foreach ($parMatiere as $matiereCarte => $liste): ?>

                        <?php
                        $somme = 0;

                        foreach ($liste as $x) {
                            $somme += (float)$x['valeur'];
                        }

                        $moyenne = count($liste) > 0
                            ? $somme / count($liste)
                            : 0;
                        ?>

                        <div class="bg-white border border-gray-100 rounded-xl shadow-md p-4">

                            <div class="flex items-center justify-between pb-3 border-b border-gray-100">

                                <h4 class="text-sm font-bold text-gray-900 pr-3">
                                    <?= h($matiereCarte) ?>
                                </h4>

                                <span class="inline-block bg-gray-100 text-gray-600 px-2.5 py-1 rounded-full text-[11px] font-medium shrink-0">
                                    <?= count($liste) ?> note<?= count($liste) > 1 ? 's' : '' ?>
                                </span>

                            </div>

                            <div class="pt-3 space-y-3">

                                <div>
                                    <p class="text-xs text-slate-500">Moyenne</p>
                                    <p class="text-sm font-bold text-gray-900">
                                        <?= h(fmtNote($moyenne)) ?>/20
                                    </p>
                                </div>

                                <div class="divide-y divide-gray-100">

                                    <?php foreach ($liste as $x): ?>

                                        <div data-note-row class="flex items-center justify-between py-2 gap-2">

                                            <div class="min-w-0">

                                                <p class="text-xs font-bold text-gray-900">
                                                    <?= h(trim(($x['prenom'] ?? '') . ' ' . ($x['nom'] ?? 'Étudiant inconnu'))) ?>
                                                </p>

                                                <p class="text-[11px] text-slate-500">
                                                    <?= h($x['matricule'] ?? '') ?>
                                                </p>

                                            </div>

                                            <div class="flex items-center space-x-2 shrink-0">

                                                <span class="inline-block bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full text-[11px] font-medium">
                                                    <?= h($x['type']) ?>
                                                </span>

                                                <span class="text-xs font-bold text-gray-900">
                                                    <?= h(fmtNote($x['valeur'])) ?>/20
                                                </span>

                                                <?php
                                                $noteJsonCard = json_encode(
                                                    $x,
                                                    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
                                                );
                                                ?>

                                                <button
                                                    type="button"
                                                    class="p-1.5 text-slate-500 hover:text-blue-600 transition btn-edit-note"
                                                    data-note="<?= h($noteJsonCard) ?>"
                                                    title="Modifier">

                                                    <i class="fa-solid fa-pen text-xs"></i>

                                                </button>

                                                <button
                                                    type="button"
                                                    onclick="openDeleteModalAsync(<?= (int)$x['id'] ?>, <?= h(json_encode($x['matiere'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)) ?>, this.closest('[data-note-row]'), 'supprimer_note')"
                                                    class="p-1.5 text-slate-500 hover:text-red-600 transition"
                                                    title="Supprimer">

                                                    <i class="fa-regular fa-trash-can text-xs"></i>

                                                </button>

                                            </div>

                                        </div>

                                    <?php endforeach; ?>

                                </div>

                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>

            </div>

            <!-- PAGINATION -->

            <div class="flex flex-wrap gap-3 justify-between items-center px-4 sm:px-6 py-4 border-t border-gray-100 text-xs text-slate-500">

                <span>
                    <?= $debutAffichage ?>–<?= $finAffichage ?> sur <?= $totalTotal ?>
                </span>

                <?php if ($totalPages > 1): ?>

                    <div class="flex items-center space-x-3">

                        <span>
                            Page <?= $pageActuelleNum ?> sur <?= $totalPages ?>
                        </span>

                        <div class="flex space-x-1">

                            <?php if ($pageActuelleNum > 1): ?>

                                <a href="notes.php?q=<?= urlencode($recherche) ?>&page=<?= $pageActuelleNum - 1 ?>"
                                   class="w-8 h-8 flex items-center justify-center border border-gray-200 rounded-lg text-gray-600 hover:bg-gray-100">

                                    <i class="fa-solid fa-chevron-left text-[10px]"></i>

                                </a>

                            <?php else: ?>

                                <span class="w-8 h-8 flex items-center justify-center border border-gray-200 rounded-lg text-gray-300">
                                    <i class="fa-solid fa-chevron-left text-[10px]"></i>
                                </span>

                            <?php endif; ?>

                            <?php if ($pageActuelleNum < $totalPages): ?>

                                <a href="notes.php?q=<?= urlencode($recherche) ?>&page=<?= $pageActuelleNum + 1 ?>"
                                   class="w-8 h-8 flex items-center justify-center border border-gray-200 rounded-lg text-gray-600 hover:bg-gray-100">

                                    <i class="fa-solid fa-chevron-right text-[10px]"></i>

                                </a>

                            <?php else: ?>

                                <span class="w-8 h-8 flex items-center justify-center border border-gray-200 rounded-lg text-gray-300">
                                    <i class="fa-solid fa-chevron-right text-[10px]"></i>
                                </span>

                            <?php endif; ?>

                        </div>

                    </div>

                <?php endif; ?>

            </div>

        </div>

    </div>

</main>

<!-- DRAWER AJOUT / MODIFICATION -->

<div id="drawer-overlay"
     class="fixed inset-0 bg-black/50 z-50 hidden transition-opacity opacity-0"
     onclick="toggleDrawer(false)">
</div>

<div id="drawer"
     class="fixed top-0 right-0 h-full w-full max-w-[420px] bg-white shadow-2xl z-50 transform translate-x-full transition-transform duration-300 ease-in-out flex flex-col overflow-y-auto">

    <div class="p-6 border-b border-gray-100 flex justify-between items-center sticky top-0 bg-white z-10">

        <h3 id="drawer-title" class="font-bold text-base text-gray-800">
            <?= $modeEdition ? 'Modifier la note' : 'Saisir une note' ?>
        </h3>

        <button type="button"
                onclick="toggleDrawer(false)"
                class="text-gray-400 hover:text-gray-600 text-xl font-bold">
            &times;
        </button>

    </div>

    <form id="noteForm" action="notes.php" method="POST" class="p-6 space-y-4" novalidate>

        <input type="hidden" id="form-action" name="action" value="<?= h($ancien['action']) ?>">
        <input type="hidden" id="note_id" name="id" value="<?= h($ancien['id']) ?>">

        <!-- Erreur générale -->
        <div id="err-general"
             class="p-3 bg-red-50 border-l-4 border-red-500 text-red-700 text-xs rounded-lg <?= $erreurs['general'] === '' ? 'hidden' : '' ?>">
            <?= h($erreurs['general']) ?>
        </div>

        <!-- Étudiant -->
        <div>

            <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">
                Étudiant*
            </label>

            <select id="etudiant_id"
                    name="etudiant_id"
                    class="<?= classesChamp($erreurs['etudiant_id']) ?> bg-white">

                <option value="">Sélectionner un étudiant</option>

                <?php foreach ($etudiants as $et): ?>

                    <option value="<?= (int)$et['id'] ?>" <?= (string)$ancien['etudiant_id'] === (string)$et['id'] ? 'selected' : '' ?>>
                        <?= h($et['prenom'] . ' ' . $et['nom']) ?>
                        (<?= h($et['matricule']) ?>)
                    </option>

                <?php endforeach; ?>

            </select>

            <p id="err-etudiant_id" class="mt-1 text-xs text-red-600 <?= $erreurs['etudiant_id'] === '' ? 'hidden' : '' ?>"><?= h($erreurs['etudiant_id']) ?></p>

        </div>

        <!-- Matière -->
        <div>

            <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">
                Matière / Module*
            </label>

            <input type="text"
                   id="matiere"
                   name="matiere"
                   value="<?= h($ancien['matiere']) ?>"
                   placeholder="ex: Développement Web PHP"
                   class="<?= classesChamp($erreurs['matiere']) ?>">

            <p id="err-matiere" class="mt-1 text-xs text-red-600 <?= $erreurs['matiere'] === '' ? 'hidden' : '' ?>"><?= h($erreurs['matiere']) ?></p>

        </div>

        <!-- Type -->
        <div>

            <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">
                Type d'évaluation*
            </label>

            <select id="type"
                    name="type"
                    class="<?= classesChamp($erreurs['type']) ?> bg-white">

                <option value="CC" <?= $ancien['type'] === 'CC' ? 'selected' : '' ?>>Contrôle Continu (CC)</option>
                <option value="Examen" <?= $ancien['type'] === 'Examen' ? 'selected' : '' ?>>Examen</option>
                <option value="Projet" <?= $ancien['type'] === 'Projet' ? 'selected' : '' ?>>Projet</option>

            </select>

            <p id="err-type" class="mt-1 text-xs text-red-600 <?= $erreurs['type'] === '' ? 'hidden' : '' ?>"><?= h($erreurs['type']) ?></p>

        </div>

        <!-- Note -->
        <div>

            <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">
                Note (/20)*
            </label>

            <input type="text"
                   inputmode="decimal"
                   id="valeur"
                   name="valeur"
                   value="<?= h($ancien['valeur']) ?>"
                   placeholder="ex: 15.5"
                   class="<?= classesChamp($erreurs['valeur']) ?>">

            <p id="err-valeur" class="mt-1 text-xs text-red-600 <?= $erreurs['valeur'] === '' ? 'hidden' : '' ?>"><?= h($erreurs['valeur']) ?></p>

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

<!-- MODALE SUPPRESSION -->

<div id="deleteModal"
     class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center opacity-0 transition-opacity duration-300">

    <div id="deleteModalContent"
         class="bg-white rounded-2xl p-6 w-[400px] max-w-[90%] shadow-2xl transform scale-95 transition-transform duration-300">

        <div class="flex items-center space-x-3 mb-4">

            <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center text-red-600 font-bold">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>

            <h3 class="font-bold text-gray-900 text-base">
                Confirmation de suppression
            </h3>

        </div>

        <p id="deleteModalMessage"
           class="text-xs text-gray-600 mb-6 leading-relaxed">
            Voulez-vous vraiment effectuer cette suppression ?
        </p>

        <div class="flex justify-end space-x-3">

            <button type="button"
                    onclick="closeDeleteModal()"
                    class="px-4 py-2 bg-gray-100 text-gray-700 text-xs font-semibold rounded-lg hover:bg-gray-200 transition">
                Annuler
            </button>

            <button id="deleteModalConfirmBtn"
                    type="button"
                    class="px-4 py-2 bg-red-600 text-white text-xs font-semibold rounded-lg hover:bg-red-700 transition shadow-sm">
                Supprimer
            </button>

        </div>

    </div>

</div>

<!-- NOTIFICATIONS -->

<div id="toastContainer"
     class="fixed bottom-4 right-4 sm:bottom-6 sm:right-6 z-[60] flex flex-col space-y-3">
</div>

<script>

// ==========================================
// NOTIFICATIONS
// ==========================================

function showToast(message, type = 'success', onUndo = null) {

    const container = document.getElementById('toastContainer');

    const isSuccess = type === 'success';
    const bgColor = isSuccess ? 'bg-[#00B67A]' : 'bg-[#DC2626]';

    const toast = document.createElement('div');

    toast.className = `flex items-center justify-between gap-4 ${bgColor} text-white px-6 py-4 rounded-2xl shadow-xl transform transition-all duration-300 translate-y-5 opacity-0 w-[calc(100vw-2rem)] sm:w-auto sm:min-w-[300px]`;

    const iconSvg = isSuccess
        ? `<i class="fa-solid fa-check text-xl"></i>`
        : `<i class="fa-solid fa-trash text-xl"></i>`;

    toast.innerHTML = `
        <div class="flex items-center space-x-3">
            ${iconSvg}
            <span class="text-sm font-semibold"></span>
        </div>

        <div class="flex items-center space-x-3">

            ${onUndo ? `
                <button type="button"
                        class="toastUndoBtn text-xs bg-white/20 hover:bg-white/30 text-white font-semibold px-2.5 py-1 rounded-lg transition">
                    Annuler
                </button>
            ` : ''}

            <button type="button"
                    class="toastCloseBtn text-white/80 hover:text-white">
                <i class="fa-solid fa-xmark"></i>
            </button>

        </div>
    `;

    toast.querySelector('span').textContent = message;
    container.appendChild(toast);

    setTimeout(() => {
        toast.classList.remove('translate-y-5', 'opacity-0');
    }, 50);

    let hideTimeout = setTimeout(() => {
        toast.classList.add('opacity-0', 'translate-y-5');
        setTimeout(() => toast.remove(), 300);
    }, 5000);

    toast.querySelector('.toastCloseBtn').addEventListener('click', () => {
        clearTimeout(hideTimeout);
        toast.remove();
    });

    if (onUndo) {

        toast.querySelector('.toastUndoBtn').addEventListener('click', async () => {

            clearTimeout(hideTimeout);

            const button = toast.querySelector('.toastUndoBtn');
            button.disabled = true;
            button.textContent = 'Restauration...';

            await onUndo();

            toast.remove();

        });

    }

}

// ==========================================
// VALIDATION DES CHAMPS (côté navigateur)
// ==========================================

const TYPES_VALIDES = <?= json_encode($typesValides) ?>;
const CHAMPS_FORM = ['etudiant_id', 'matiere', 'type', 'valeur'];

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

    const etudiant = document.getElementById('etudiant_id').value;
    const matiere = document.getElementById('matiere').value.trim();
    const type = document.getElementById('type').value;
    const valeur = document.getElementById('valeur').value.trim().replace(',', '.');

    if (etudiant === '') {
        erreur('etudiant_id', "Veuillez sélectionner un étudiant.");
    }

    if (matiere === '') {
        erreur('matiere', "La matière est obligatoire.");
    } else if (matiere.length < 2) {
        erreur('matiere', "La matière doit contenir au moins 2 caractères.");
    } else if (matiere.length > 100) {
        erreur('matiere', "La matière ne doit pas dépasser 100 caractères.");
    }

    if (!TYPES_VALIDES.includes(type)) {
        erreur('type', "Veuillez choisir un type d'évaluation valide (CC, Examen ou Projet).");
    }

    if (valeur === '') {
        erreur('valeur', "La note est obligatoire.");
    } else if (!/^-?\d+(\.\d+)?$/.test(valeur)) {
        erreur('valeur', "La note doit être un nombre (ex : 15 ou 15.5).");
    } else if (parseFloat(valeur) < 0 || parseFloat(valeur) > 20) {
        erreur('valeur', "La note doit être comprise entre 0 et 20.");
    }

    return valide;

}

document.getElementById('noteForm').addEventListener('submit', function(e) {

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

    document.getElementById('etudiant_id').value = '';
    document.getElementById('matiere').value = '';
    document.getElementById('type').value = 'CC';
    document.getElementById('valeur').value = '';

    effacerErreurs();

}

// ==========================================
// MODIFICATION : GESTION DU BOUTON EDIT
// ==========================================

function openEditDrawer(note) {

    if (!note || !note.id) {
        alert("Impossible de récupérer les informations de cette note.");
        return;
    }

    viderFormulaire();

    document.getElementById('drawer-title').textContent = "Modifier la note";
    document.getElementById('form-action').value = "modifier_note";
    document.getElementById('note_id').value = note.id;
    document.getElementById('submit-btn').textContent = "Modifier";

    document.getElementById('etudiant_id').value = String(note.etudiant_id);
    document.getElementById('matiere').value = note.matiere ?? '';
    document.getElementById('type').value = note.type ?? 'CC';
    document.getElementById('valeur').value = note.valeur !== null && note.valeur !== undefined
        ? String(parseFloat(note.valeur))
        : '';

    toggleDrawer(true);

}

// Écouteur unique pour tous les boutons Modifier
document.addEventListener('click', function(e) {

    const button = e.target.closest('.btn-edit-note');

    if (!button) return;

    try {

        const note = JSON.parse(button.dataset.note);

        openEditDrawer(note);

    } catch (error) {

        console.error("Erreur récupération note :", error);
        alert("Impossible de charger les informations de cette note.");

    }

});

// ==========================================
// MODALE DE SUPPRESSION
// ==========================================

let targetToDeleteId = null;
let rowElement = null;
let deletedData = null;
let currentDeleteAction = null;
let deleteReloadTimer = null;

function openDeleteModalAsync(id, itemName, element, actionType) {

    targetToDeleteId = Number(id);
    rowElement = element;
    currentDeleteAction = actionType;
    deletedData = null;

    if (!targetToDeleteId || targetToDeleteId <= 0) {
        alert("Identifiant de note invalide.");
        return;
    }

    const modal = document.getElementById('deleteModal');
    const msgEl = document.getElementById('deleteModalMessage');
    const confirmBtn = document.getElementById('deleteModalConfirmBtn');
    const content = document.getElementById('deleteModalContent');

    msgEl.textContent = `Voulez-vous vraiment supprimer la note "${itemName}" ?`;

    confirmBtn.disabled = false;
    confirmBtn.textContent = 'Supprimer';
    confirmBtn.onclick = deleteNote;

    modal.classList.remove('hidden');

    setTimeout(() => {
        modal.classList.remove('opacity-0');
        content.classList.remove('scale-95');
        content.classList.add('scale-100');
    }, 10);

}

function closeDeleteModal() {

    const modal = document.getElementById('deleteModal');
    const content = document.getElementById('deleteModalContent');

    modal.classList.add('opacity-0');
    content.classList.remove('scale-100');
    content.classList.add('scale-95');

    setTimeout(() => {
        modal.classList.add('hidden');
    }, 300);

}

// ==========================================
// SUPPRESSION
// ==========================================

async function deleteNote() {

    const confirmBtn = document.getElementById('deleteModalConfirmBtn');

    confirmBtn.disabled = true;
    confirmBtn.textContent = 'Suppression...';

    try {

        const response = await fetch('action.php', {

            method: 'POST',

            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },

            body: JSON.stringify({
                action: currentDeleteAction,
                id: targetToDeleteId
            })

        });

        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error(data.message || "La suppression a échoué.");
        }

        deletedData = data.data;

        if (!deletedData) {
            throw new Error("Les données nécessaires à la restauration n'ont pas été retournées.");
        }

        closeDeleteModal();

        if (rowElement) {
            rowElement.remove();
        }

        showToast(
            data.message || "Note supprimée avec succès.",
            'error',
            restoreNote
        );

        clearTimeout(deleteReloadTimer);

        deleteReloadTimer = setTimeout(() => {
            location.reload();
        }, 5000);

    } catch (error) {

        console.error("Erreur suppression :", error);

        alert(error.message || "Une erreur est survenue pendant la suppression.");

        confirmBtn.disabled = false;
        confirmBtn.textContent = 'Supprimer';

    }

}

// ==========================================
// RESTAURATION
// ==========================================

async function restoreNote() {

    if (!deletedData) {
        alert("Aucune donnée disponible pour la restauration.");
        return;
    }

    try {

        const response = await fetch('action.php', {

            method: 'POST',

            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },

            body: JSON.stringify({
                action: 'restaurer_note',
                data: deletedData
            })

        });

        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error(data.message || "La restauration a échoué.");
        }

        clearTimeout(deleteReloadTimer);
        deletedData = null;

        showToast("Note restaurée avec succès.", "success");

        setTimeout(() => {
            location.reload();
        }, 700);

    } catch (error) {

        console.error("Erreur restauration :", error);

        alert(error.message || "Erreur lors de la restauration.");

    }

}

// ==========================================
// RECHERCHE
// ==========================================

let searchTimeout;

const searchInput = document.getElementById('searchInput');
const searchForm = document.getElementById('searchForm');

if (searchInput && searchForm) {

    searchInput.addEventListener('input', function() {

        clearTimeout(searchTimeout);

        searchTimeout = setTimeout(() => {
            searchForm.submit();
        }, 400);

    });

}

// ==========================================
// DRAWER
// ==========================================

function toggleDrawer(open) {

    const drawer = document.getElementById('drawer');
    const overlay = document.getElementById('drawer-overlay');

    if (open) {

        overlay.classList.remove('hidden');

        setTimeout(() => {
            overlay.classList.remove('opacity-0');
        }, 10);

        drawer.classList.remove('translate-x-full');

    } else {

        overlay.classList.add('opacity-0');
        drawer.classList.add('translate-x-full');

        setTimeout(() => {
            overlay.classList.add('hidden');
        }, 300);

    }

}

// ==========================================
// AJOUT
// ==========================================

function openAddDrawer() {

    viderFormulaire();

    document.getElementById('drawer-title').textContent = "Saisir une note";
    document.getElementById('form-action').value = "ajouter_note";
    document.getElementById('note_id').value = "";
    document.getElementById('submit-btn').textContent = "Enregistrer";

    toggleDrawer(true);

}

// ==========================================
// RÉOUVERTURE DU DRAWER SI ERREURS SERVEUR
// ==========================================

<?php if ($aDesErreurs): ?>

document.addEventListener('DOMContentLoaded', () => toggleDrawer(true));

<?php endif; ?>

// ==========================================
// CHANGEMENT DE VUE
// ==========================================

function setView(mode) {

    const isTable = mode === 'table';

    const viewTable = document.getElementById('view-table');
    const viewCards = document.getElementById('view-cards');

    viewTable.classList.toggle('hidden', !isTable);
    viewCards.classList.toggle('hidden', isTable);

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

    <?php if ($_GET['success'] == '1'): ?>

        showToast("Note ajoutée avec succès.", "success");

    <?php elseif ($_GET['success'] == '2'): ?>

        showToast("Note modifiée avec succès.", "success");

    <?php endif; ?>

});

</script>

<?php endif; ?>

</body>
</html>