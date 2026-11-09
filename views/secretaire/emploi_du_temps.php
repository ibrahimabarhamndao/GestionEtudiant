<?php
$pageActuelle = 'emploi_du_temps';
$titrePage = 'Emploi du Temps';
$descriptionPage = 'Planning hebdomadaire des cours';

require_once '../../config/db.php';

$message = '';
$jours = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi'];

// ==========================================
// CRÉATION DE LA TABLE
// ==========================================

try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS emploi_du_temps (
            id INT AUTO_INCREMENT PRIMARY KEY,
            classe_id INT NOT NULL,
            jour VARCHAR(20) NOT NULL,
            heure_debut VARCHAR(10) NOT NULL,
            matiere VARCHAR(150) NOT NULL,
            salle VARCHAR(50) NOT NULL,
            enseignant VARCHAR(150) NOT NULL,
            details TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
} catch (PDOException $e) {
    error_log("Erreur table emploi_du_temps : " . $e->getMessage());
}

// ==========================================
// FONCTIONS UTILITAIRES
// ==========================================

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// Normalise une heure saisie : "8h", "8:30", "08H00" => "08h00" / "08h30"
function normaliserHeure($valeur) {
    $v = strtolower(trim($valeur));
    $v = str_replace(':', 'h', $v);
    if (preg_match('/^(\d{1,2})h(\d{2})?$/', $v, $m)) {
        return str_pad($m[1], 2, '0', STR_PAD_LEFT) . 'h' . ($m[2] ?? '00');
    }
    return $v;
}

// Vérifie si une valeur (classe, enseignant ou salle) est déjà prise au même jour et à la même heure
function creneauEnConflit($pdo, $colonne, $valeur, $jour, $heure, $idExclu) {
    if (!in_array($colonne, ['classe_id', 'enseignant', 'salle'], true)) {
        return false;
    }
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM emploi_du_temps
        WHERE $colonne = ? AND jour = ? AND heure_debut = ? AND id <> ?
    ");
    $stmt->execute([$valeur, $jour, $heure, $idExclu]);
    return (int)$stmt->fetchColumn() > 0;
}

// Classes CSS d'un champ du formulaire (rouge s'il y a une erreur)
function classesChamp($erreur) {
    return 'w-full px-3 py-2 border rounded-lg text-sm focus:outline-none focus:border-red-600 '
        . ($erreur !== '' ? 'border-red-500' : 'border-gray-200');
}

// ==========================================
// SUPPRESSION ET RESTAURATION AJAX
// ==========================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {

    header('Content-Type: application/json; charset=utf-8');

    $data = json_decode(file_get_contents('php://input'), true);

    try {

        if (!is_array($data)) {
            throw new Exception("Données invalides.");
        }

        $action = $data['action'] ?? '';

        // SUPPRESSION
        if ($action === 'supprimer_creneau') {

            $id = (int)($data['id'] ?? 0);

            if ($id <= 0) {
                throw new Exception("Identifiant invalide.");
            }

            $stmt = $pdo->prepare("SELECT * FROM emploi_du_temps WHERE id = ?");
            $stmt->execute([$id]);
            $creneau = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$creneau) {
                throw new Exception("Ce créneau n'existe plus.");
            }

            $stmt = $pdo->prepare("DELETE FROM emploi_du_temps WHERE id = ?");
            $stmt->execute([$id]);

            echo json_encode([
                'success' => true,
                'message' => 'Créneau supprimé avec succès.',
                'data' => $creneau
            ]);
            exit();
        }

        // RESTAURATION
        if ($action === 'restaurer_creneau') {

            $creneau = $data['data'] ?? null;

            if (!is_array($creneau) || empty($creneau['id'])) {
                throw new Exception("Données de restauration invalides.");
            }

            $stmt = $pdo->prepare("SELECT id FROM emploi_du_temps WHERE id = ?");
            $stmt->execute([(int)$creneau['id']]);

            if ($stmt->fetch()) {
                throw new Exception("Ce créneau existe déjà.");
            }

            $stmt = $pdo->prepare("
                INSERT INTO emploi_du_temps
                    (id, classe_id, jour, heure_debut, matiere, salle, enseignant, details)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                (int)$creneau['id'],
                (int)$creneau['classe_id'],
                $creneau['jour'],
                $creneau['heure_debut'],
                $creneau['matiere'],
                $creneau['salle'],
                $creneau['enseignant'],
                $creneau['details'] ?? ''
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'Créneau restauré avec succès.'
            ]);
            exit();
        }

        throw new Exception("Action inconnue.");

    // PDOException doit être attrapée AVANT Exception (sinon elle n'est jamais atteinte)
    } catch (PDOException $e) {

        error_log("Erreur AJAX emploi du temps : " . $e->getMessage());

        http_response_code(500);

        echo json_encode([
            'success' => false,
            'message' => "Une erreur est survenue lors de l'opération."
        ]);
        exit();

    } catch (Exception $e) {

        http_response_code(400);

        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
        exit();
    }
}

// ==========================================
// AJOUT ET MODIFICATION (avec validation)
// ==========================================

$champs = ['classe_id', 'jour', 'heure_debut', 'matiere', 'salle', 'enseignant', 'details', 'general'];
$erreurs = array_fill_keys($champs, '');

// Valeurs affichées dans le formulaire (conservées en cas d'erreur)
$ancien = [
    'action'      => 'ajouter_creneau',
    'id'          => '',
    'classe_id'   => '',
    'jour'        => $jours[0],
    'heure_debut' => '',
    'matiere'     => '',
    'salle'       => '',
    'enseignant'  => '',
    'details'     => '',
];
$aDesErreurs = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])
    && in_array($_POST['action'], ['ajouter_creneau', 'modifier_creneau'], true)) {

    $actionPost = $_POST['action'];

    $id          = (int)($_POST['id'] ?? 0);
    $classe_id   = (int)($_POST['classe_id'] ?? 0);
    $jour        = trim($_POST['jour'] ?? '');
    $heure_debut = normaliserHeure($_POST['heure_debut'] ?? '');
    $matiere     = trim($_POST['matiere'] ?? '');
    $salle       = trim($_POST['salle'] ?? '');
    $enseignant  = trim($_POST['enseignant'] ?? '');
    $details     = trim($_POST['details'] ?? '');

    $ancien = [
        'action'      => $actionPost,
        'id'          => $id > 0 ? $id : '',
        'classe_id'   => $classe_id > 0 ? $classe_id : '',
        'jour'        => $jour,
        'heure_debut' => $heure_debut,
        'matiere'     => $matiere,
        'salle'       => $salle,
        'enseignant'  => $enseignant,
        'details'     => $details,
    ];

    try {

        // --- Classe ---
        if ($classe_id <= 0) {
            $erreurs['classe_id'] = "Veuillez sélectionner une classe.";
        } else {
            $check = $pdo->prepare("SELECT id FROM classes WHERE id = ?");
            $check->execute([$classe_id]);
            if (!$check->fetch(PDO::FETCH_ASSOC)) {
                $erreurs['classe_id'] = "La classe sélectionnée n'existe pas.";
            }
        }

        // --- Jour ---
        if (!in_array($jour, $jours, true)) {
            $erreurs['jour'] = "Veuillez choisir un jour valide (du lundi au vendredi).";
        }

        // --- Heure de début ---
        if ($heure_debut === '') {
            $erreurs['heure_debut'] = "L'heure de début est obligatoire.";
        } elseif (!preg_match('/^([01]\d|2[0-3])h[0-5]\d$/', $heure_debut)) {
            $erreurs['heure_debut'] = "Format invalide. Exemple attendu : 08h00.";
        }

        // --- Matière ---
        if ($matiere === '') {
            $erreurs['matiere'] = "La matière est obligatoire.";
        } elseif (mb_strlen($matiere) < 2) {
            $erreurs['matiere'] = "La matière doit contenir au moins 2 caractères.";
        } elseif (mb_strlen($matiere) > 150) {
            $erreurs['matiere'] = "La matière ne doit pas dépasser 150 caractères.";
        }

        // --- Salle / Enseignant / Détails (facultatifs, longueur limitée) ---
        if (mb_strlen($salle) > 50) {
            $erreurs['salle'] = "La salle ne doit pas dépasser 50 caractères.";
        }
        if (mb_strlen($enseignant) > 150) {
            $erreurs['enseignant'] = "Le nom de l'enseignant ne doit pas dépasser 150 caractères.";
        }
        if (mb_strlen($details) > 500) {
            $erreurs['details'] = "Les détails ne doivent pas dépasser 500 caractères.";
        }

        // --- Modification : le créneau doit exister ---
        if ($actionPost === 'modifier_creneau') {
            if ($id <= 0) {
                $erreurs['general'] = "Identifiant de créneau invalide.";
            } else {
                $check = $pdo->prepare("SELECT id FROM emploi_du_temps WHERE id = ?");
                $check->execute([$id]);
                if (!$check->fetch(PDO::FETCH_ASSOC)) {
                    $erreurs['general'] = "Le créneau à modifier est introuvable.";
                }
            }
        }

        // --- Conflits d'horaire (même jour + même heure) ---
        if ($erreurs['jour'] === '' && $erreurs['heure_debut'] === '') {

            $idExclu = ($actionPost === 'modifier_creneau') ? $id : 0;

            if ($erreurs['classe_id'] === '' && creneauEnConflit($pdo, 'classe_id', $classe_id, $jour, $heure_debut, $idExclu)) {
                $erreurs['heure_debut'] = "Cette classe a déjà un cours le $jour à $heure_debut.";
            }
            if ($enseignant !== '' && $erreurs['enseignant'] === '' && creneauEnConflit($pdo, 'enseignant', $enseignant, $jour, $heure_debut, $idExclu)) {
                $erreurs['enseignant'] = "Cet enseignant a déjà un cours le $jour à $heure_debut.";
            }
            if ($salle !== '' && $erreurs['salle'] === '' && creneauEnConflit($pdo, 'salle', $salle, $jour, $heure_debut, $idExclu)) {
                $erreurs['salle'] = "Cette salle est déjà occupée le $jour à $heure_debut.";
            }
        }

        // --- Enregistrement si aucune erreur ---
        if (implode('', $erreurs) === '') {

            if ($actionPost === 'ajouter_creneau') {

                $stmt = $pdo->prepare("
                    INSERT INTO emploi_du_temps
                        (classe_id, jour, heure_debut, matiere, salle, enseignant, details)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$classe_id, $jour, $heure_debut, $matiere, $salle, $enseignant, $details]);

                header("Location: emploi_du_temps.php?success=1");
                exit();
            }

            $stmt = $pdo->prepare("
                UPDATE emploi_du_temps
                SET classe_id = ?, jour = ?, heure_debut = ?, matiere = ?,
                    salle = ?, enseignant = ?, details = ?
                WHERE id = ?
            ");
            $stmt->execute([$classe_id, $jour, $heure_debut, $matiere, $salle, $enseignant, $details, $id]);

            header("Location: emploi_du_temps.php?success=2");
            exit();
        }

    } catch (PDOException $e) {

        error_log("Erreur CRUD emploi du temps : " . $e->getMessage());
        $erreurs['general'] = "Erreur lors de l'enregistrement du créneau.";
    }

    $aDesErreurs = implode('', $erreurs) !== '';
}

$modeEdition = ($ancien['action'] === 'modifier_creneau');

// ==========================================
// RÉCUPÉRATION DES CLASSES
// ==========================================

$classes = [];

try {
    $classes = $pdo->query("SELECT id, nom FROM classes ORDER BY nom ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erreur récupération classes : " . $e->getMessage());
    $message = "Impossible de récupérer les classes.";
}

// ==========================================
// COULEURS DES JOURS
// ==========================================

$couleurs = [
    'Lundi'    => 'bg-red-50 border-red-100 border-l-red-400',
    'Mardi'    => 'bg-blue-50 border-blue-100 border-l-blue-400',
    'Mercredi' => 'bg-emerald-50 border-emerald-100 border-l-emerald-400',
    'Jeudi'    => 'bg-purple-50 border-purple-100 border-l-purple-400',
    'Vendredi' => 'bg-amber-50 border-amber-100 border-l-amber-400',
];

// ==========================================
// RÉCUPÉRATION DES CRÉNEAUX
// ==========================================

$planning = array_fill_keys($jours, []);
$creneaux = [];

try {

    $creneaux = $pdo->query("SELECT * FROM emploi_du_temps ORDER BY heure_debut ASC")->fetchAll(PDO::FETCH_ASSOC);

    foreach ($creneaux as $c) {
        if (isset($planning[$c['jour']])) {
            $planning[$c['jour']][] = $c;
        }
    }

} catch (PDOException $e) {
    error_log("Erreur récupération planning : " . $e->getMessage());
    $message = "Impossible de récupérer le planning.";
}

// ==========================================
// DONNÉES DE DÉMONSTRATION
// ==========================================

if (count($creneaux) === 0) {

    $demonstration = [
        'Lundi' => [
            ['08h00', 'Algorithmique', 'Salle A101', 'Dr. Diallo'],
            ['10h00', 'Base de données', 'Salle B203', 'Pr. Seck'],
            ['14h00', 'Réseaux informatiques', 'Labo Info', 'Dr. Ndiaye'],
        ],
        'Mardi' => [
            ['08h00', 'Mathématiques', 'Salle A102', 'Pr. Ba'],
            ['10h00', 'Programmation Web', 'Labo Info', 'Dr. Diallo'],
        ],
        'Mercredi' => [
            ['08h00', 'Systèmes d\'exploitation', 'Salle C301', 'Dr. Ndiaye'],
            ['14h00', 'Anglais Technique', 'Salle A103', 'Pr. Fall'],
        ],
        'Jeudi' => [
            ['08h00', 'Sécurité Informatique', 'Salle B201', 'Dr. Diallo'],
            ['10h00', 'Gestion de Projet', 'Salle A101', 'Pr. Seck'],
            ['14h00', 'TP Réseaux', 'Labo Réseaux', 'Dr. Ndiaye'],
        ],
        'Vendredi' => [
            ['08h00', 'Architecture des SI', 'Salle C302', 'Pr. Ba'],
        ],
    ];

    foreach ($demonstration as $jourDemo => $liste) {
        foreach ($liste as $cours) {
            $planning[$jourDemo][] = [
                'id' => null,
                'classe_id' => '',
                'jour' => $jourDemo,
                'heure_debut' => $cours[0],
                'matiere' => $cours[1],
                'salle' => $cours[2],
                'enseignant' => $cours[3],
                'details' => ''
            ];
        }
    }
}

?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Administration — Emploi du Temps</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="bg-gray-50 flex h-screen overflow-hidden font-sans">

    <!-- SIDEBAR -->

    <?php include '../../includes/sidebar_secretaire.php'; ?>

    <!-- CONTENU PRINCIPAL -->

    <main class="flex-1 flex flex-col h-screen overflow-hidden">

        <?php include '../../includes/header.php'; ?>

        <div class="flex-1 p-8 overflow-y-auto space-y-8">

            <?php if (!empty($message)): ?>

                <div class="p-4 bg-red-50 border-l-4 border-red-500 text-red-700 text-sm rounded-lg shadow-sm">
                    <?= h($message) ?>
                </div>

            <?php endif; ?>

            <!-- BARRE D'OUTILS -->

            <div class="flex items-center justify-between">

                <span class="text-xs text-slate-500">
                    Planning hebdomadaire des cours
                </span>

                <button
                    type="button"
                    onclick="openAddModal()"
                    class="bg-red-600 text-white px-5 py-2.5 rounded-lg text-sm font-semibold hover:bg-red-700 transition flex items-center space-x-2 shadow-sm">

                    <span>Ajouter un créneau</span>
                    <i class="fa-solid fa-plus text-xs"></i>

                </button>

            </div>

            <!-- GRILLE DES CINQ JOURS -->

            <div class="grid grid-cols-5 gap-4 items-start">

                <?php foreach ($planning as $jourPlanning => $cours): ?>

                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">

                        <div class="px-4 py-3 border-b border-gray-200">

                            <h3 class="font-bold text-[11px] uppercase tracking-wider text-gray-800">
                                <?= h($jourPlanning) ?>
                            </h3>

                        </div>

                        <div class="p-3 space-y-3">

                            <?php foreach ($cours as $c): ?>

                                <?php
                                $jsonCreneau = json_encode(
                                    $c,
                                    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
                                );
                                ?>

                                <div
                                    class="p-3 border border-l-2 rounded-lg space-y-1 group <?= h($couleurs[$jourPlanning]) ?>"
                                    data-creneau-id="<?= (int)($c['id'] ?? 0) ?>">

                                    <div class="flex justify-between items-start text-xs font-bold text-slate-500">

                                        <span>
                                            <?= h($c['heure_debut']) ?>
                                        </span>

                                        <?php if (!empty($c['id'])): ?>

                                            <div class="space-x-1 opacity-0 group-hover:opacity-100 transition">

                                                <!-- MODIFIER -->

                                                <button
                                                    type="button"
                                                    class="text-gray-400 hover:text-blue-600 btn-edit-creneau"
                                                    data-creneau="<?= h($jsonCreneau) ?>"
                                                    title="Modifier">

                                                    <i class="fa-regular fa-pen-to-square text-[10px]"></i>

                                                </button>

                                                <!-- SUPPRIMER -->

                                                <button
                                                    type="button"
                                                    onclick="openDeleteModal(<?= (int)$c['id'] ?>, this.closest('[data-creneau-id]'), <?= h(json_encode($c['matiere'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)) ?>)"
                                                    class="text-gray-400 hover:text-red-600"
                                                    title="Supprimer">

                                                    <i class="fa-regular fa-trash-can text-[10px]"></i>

                                                </button>

                                            </div>

                                        <?php endif; ?>

                                    </div>

                                    <h4 class="font-bold text-xs text-gray-900">
                                        <?= h($c['matiere']) ?>
                                    </h4>

                                    <p class="text-[11px] text-slate-500">
                                        <?= h($c['salle']) ?>
                                    </p>

                                    <p class="text-[11px] text-slate-500">
                                        <?= h($c['enseignant']) ?>
                                    </p>

                                </div>

                            <?php endforeach; ?>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>

        </div>

    </main>

    <!-- MODALE AJOUT / MODIFICATION -->

    <div id="modal-container"
         class="fixed inset-0 z-50 flex items-center justify-center hidden">

        <div id="modal-backdrop"
             class="fixed inset-0 bg-black/50 opacity-0 transition-opacity duration-300"
             onclick="toggleModal(false)">
        </div>

        <div id="modal-box"
             class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 p-6 z-10 opacity-0 scale-95 transition-all duration-300 space-y-4 max-h-[90vh] overflow-y-auto">

            <div class="flex justify-between items-center border-b border-gray-100 pb-3">

                <h3 id="modal-title" class="font-bold text-base text-gray-800">
                    <?= $modeEdition ? 'Modifier le créneau horaire' : 'Ajouter un créneau horaire' ?>
                </h3>

                <button
                    type="button"
                    onclick="toggleModal(false)"
                    class="text-gray-400 hover:text-gray-600 text-xl font-bold">

                    &times;

                </button>

            </div>

            <form id="creneauForm"
                  action="emploi_du_temps.php"
                  method="POST"
                  class="space-y-3"
                  novalidate>

                <input type="hidden" name="action" id="form-action" value="<?= h($ancien['action']) ?>">
                <input type="hidden" name="id" id="creneau_id" value="<?= h($ancien['id']) ?>">

                <!-- Erreur générale -->
                <div id="err-general"
                     class="p-3 bg-red-50 border-l-4 border-red-500 text-red-700 text-xs rounded-lg <?= $erreurs['general'] === '' ? 'hidden' : '' ?>">
                    <?= h($erreurs['general']) ?>
                </div>

                <!-- Classe -->
                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">
                        Classe
                    </label>

                    <select name="classe_id" id="classe_id"
                            class="<?= classesChamp($erreurs['classe_id']) ?> bg-white">

                        <option value="">Sélectionner une classe</option>

                        <?php foreach ($classes as $c): ?>
                            <option value="<?= (int)$c['id'] ?>" <?= (string)$ancien['classe_id'] === (string)$c['id'] ? 'selected' : '' ?>>
                                <?= h($c['nom']) ?>
                            </option>
                        <?php endforeach; ?>

                    </select>

                    <p id="err-classe_id" class="mt-1 text-xs text-red-600 <?= $erreurs['classe_id'] === '' ? 'hidden' : '' ?>"><?= h($erreurs['classe_id']) ?></p>
                </div>

                <div class="grid grid-cols-2 gap-2">

                    <!-- Jour -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">
                            Jour
                        </label>

                        <select name="jour" id="jour"
                                class="<?= classesChamp($erreurs['jour']) ?> bg-white">

                            <?php foreach ($jours as $j): ?>
                                <option value="<?= h($j) ?>" <?= $ancien['jour'] === $j ? 'selected' : '' ?>>
                                    <?= h($j) ?>
                                </option>
                            <?php endforeach; ?>

                        </select>

                        <p id="err-jour" class="mt-1 text-xs text-red-600 <?= $erreurs['jour'] === '' ? 'hidden' : '' ?>"><?= h($erreurs['jour']) ?></p>
                    </div>

                    <!-- Heure -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">
                            Heure de début
                        </label>

                        <input type="text" name="heure_debut" id="heure_debut"
                               value="<?= h($ancien['heure_debut']) ?>"
                               placeholder="ex: 08h00"
                               class="<?= classesChamp($erreurs['heure_debut']) ?>">

                        <p id="err-heure_debut" class="mt-1 text-xs text-red-600 <?= $erreurs['heure_debut'] === '' ? 'hidden' : '' ?>"><?= h($erreurs['heure_debut']) ?></p>
                    </div>

                </div>

                <!-- Matière -->
                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">
                        Matière
                    </label>

                    <input type="text" name="matiere" id="matiere"
                           value="<?= h($ancien['matiere']) ?>"
                           placeholder="ex: Algorithmique"
                           class="<?= classesChamp($erreurs['matiere']) ?>">

                    <p id="err-matiere" class="mt-1 text-xs text-red-600 <?= $erreurs['matiere'] === '' ? 'hidden' : '' ?>"><?= h($erreurs['matiere']) ?></p>
                </div>

                <div class="grid grid-cols-2 gap-2">

                    <!-- Salle -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">
                            Salle / Lieu
                        </label>

                        <input type="text" name="salle" id="salle"
                               value="<?= h($ancien['salle']) ?>"
                               placeholder="ex: Salle A101"
                               class="<?= classesChamp($erreurs['salle']) ?>">

                        <p id="err-salle" class="mt-1 text-xs text-red-600 <?= $erreurs['salle'] === '' ? 'hidden' : '' ?>"><?= h($erreurs['salle']) ?></p>
                    </div>

                    <!-- Enseignant -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">
                            Enseignant
                        </label>

                        <input type="text" name="enseignant" id="enseignant"
                               value="<?= h($ancien['enseignant']) ?>"
                               placeholder="ex: Dr. Diallo"
                               class="<?= classesChamp($erreurs['enseignant']) ?>">

                        <p id="err-enseignant" class="mt-1 text-xs text-red-600 <?= $erreurs['enseignant'] === '' ? 'hidden' : '' ?>"><?= h($erreurs['enseignant']) ?></p>
                    </div>

                </div>

                <!-- Détails -->
                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">
                        Détails / Remarques
                    </label>

                    <textarea name="details" id="details" rows="2"
                              placeholder="ex: Informations sur le laboratoire..."
                              class="<?= classesChamp($erreurs['details']) ?>"><?= h($ancien['details']) ?></textarea>

                    <p id="err-details" class="mt-1 text-xs text-red-600 <?= $erreurs['details'] === '' ? 'hidden' : '' ?>"><?= h($erreurs['details']) ?></p>
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

    <!-- MODALE DE SUPPRESSION -->

    <div id="deleteModal"
         class="fixed inset-0 z-[55] hidden items-center justify-center bg-black/50 opacity-0 transition-opacity duration-300">

        <div id="deleteModalContent"
             class="bg-white rounded-2xl shadow-2xl w-full max-w-sm mx-4 p-6 scale-95 transition-transform duration-300">

            <div class="flex items-center space-x-3 mb-4">

                <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center text-red-600">

                    <i class="fa-solid fa-triangle-exclamation"></i>

                </div>

                <h3 class="font-bold text-gray-900 text-base">
                    Confirmation de suppression
                </h3>

            </div>

            <p id="deleteModalMessage" class="text-xs text-gray-600 mb-6">
                Voulez-vous vraiment supprimer ce créneau ?
            </p>

            <div class="flex justify-end space-x-3">

                <button
                    type="button"
                    onclick="closeDeleteModal()"
                    class="px-4 py-2 bg-gray-100 text-gray-700 text-xs font-semibold rounded-lg hover:bg-gray-200 transition">

                    Annuler

                </button>

                <button
                    type="button"
                    id="deleteConfirmBtn"
                    onclick="deleteCreneau()"
                    class="px-4 py-2 bg-red-600 text-white text-xs font-semibold rounded-lg hover:bg-red-700 transition">

                    Supprimer

                </button>

            </div>

        </div>

    </div>

    <!-- NOTIFICATIONS -->

    <div id="toastContainer"
         class="fixed bottom-6 right-6 z-[60] flex flex-col space-y-3">
    </div>

    <!-- JAVASCRIPT -->

    <script>

// ==========================================
// NOTIFICATIONS
// ==========================================

function showToast(message, type = 'success', onUndo = null) {

    const container = document.getElementById('toastContainer');

    const isSuccess = type === 'success';
    const bgColor = isSuccess ? 'bg-[#00B67A]' : 'bg-[#DC2626]';

    const toast = document.createElement('div');

    toast.className = `flex items-center justify-between gap-4 ${bgColor} text-white px-6 py-4 rounded-2xl shadow-xl transform transition-all duration-300 translate-y-5 opacity-0 min-w-[300px]`;

    toast.innerHTML = `
        <div class="flex items-center space-x-3">
            <i class="${isSuccess ? 'fa-solid fa-check' : 'fa-solid fa-trash'} text-xl"></i>
            <span class="text-sm font-semibold"></span>
        </div>

        <div class="flex items-center space-x-3">

            ${onUndo ? `
                <button type="button"
                        class="toastUndoBtn text-xs bg-white/20 hover:bg-white/30 text-white font-semibold px-2.5 py-1 rounded-lg transition">
                    Annuler
                </button>
            ` : ''}

            <button type="button" class="toastCloseBtn text-white/80 hover:text-white">
                <i class="fa-solid fa-xmark"></i>
            </button>

        </div>
    `;

    toast.querySelector('span').textContent = message;

    container.appendChild(toast);

    setTimeout(() => {
        toast.classList.remove('translate-y-5', 'opacity-0');
    }, 50);

    const hideTimeout = setTimeout(() => {
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

            const restored = await onUndo();

            if (restored) {
                toast.remove();
            } else {
                button.disabled = false;
                button.textContent = 'Annuler';
            }

        });

    }

}

// ==========================================
// OUVERTURE / FERMETURE MODALE AJOUT
// ==========================================

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

// ==========================================
// VALIDATION DES CHAMPS (côté navigateur)
// ==========================================

const JOURS_VALIDES = <?= json_encode($jours, JSON_UNESCAPED_UNICODE) ?>;
const CHAMPS_FORM = ['classe_id', 'jour', 'heure_debut', 'matiere', 'salle', 'enseignant', 'details'];

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

// "8h", "8:30", "08H00" => "08h00" / "08h30"
function normaliserHeure(valeur) {

    let v = valeur.trim().toLowerCase().replace(':', 'h');
    const m = v.match(/^(\d{1,2})h(\d{2})?$/);

    if (!m) return v;

    return m[1].padStart(2, '0') + 'h' + (m[2] || '00');

}

function validerFormulaire() {

    effacerErreurs();

    let valide = true;
    const erreur = (champ, texte) => { setErreur(champ, texte); valide = false; };

    const classe = document.getElementById('classe_id').value;
    const jour = document.getElementById('jour').value;
    const heureInput = document.getElementById('heure_debut');
    const heure = normaliserHeure(heureInput.value);
    const matiere = document.getElementById('matiere').value.trim();
    const salle = document.getElementById('salle').value.trim();
    const enseignant = document.getElementById('enseignant').value.trim();
    const details = document.getElementById('details').value.trim();

    if (classe === '') {
        erreur('classe_id', "Veuillez sélectionner une classe.");
    }

    if (!JOURS_VALIDES.includes(jour)) {
        erreur('jour', "Veuillez choisir un jour valide (du lundi au vendredi).");
    }

    if (heure === '') {
        erreur('heure_debut', "L'heure de début est obligatoire.");
    } else if (!/^([01]\d|2[0-3])h[0-5]\d$/.test(heure)) {
        erreur('heure_debut', "Format invalide. Exemple attendu : 08h00.");
    } else {
        heureInput.value = heure; // affiche l'heure normalisée
    }

    if (matiere === '') {
        erreur('matiere', "La matière est obligatoire.");
    } else if (matiere.length < 2) {
        erreur('matiere', "La matière doit contenir au moins 2 caractères.");
    } else if (matiere.length > 150) {
        erreur('matiere', "La matière ne doit pas dépasser 150 caractères.");
    }

    if (salle.length > 50) {
        erreur('salle', "La salle ne doit pas dépasser 50 caractères.");
    }

    if (enseignant.length > 150) {
        erreur('enseignant', "Le nom de l'enseignant ne doit pas dépasser 150 caractères.");
    }

    if (details.length > 500) {
        erreur('details', "Les détails ne doivent pas dépasser 500 caractères.");
    }

    return valide;

}

document.getElementById('creneauForm').addEventListener('submit', function(e) {

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

    document.getElementById('classe_id').value = '';
    document.getElementById('jour').value = JOURS_VALIDES[0];
    document.getElementById('heure_debut').value = '';
    document.getElementById('matiere').value = '';
    document.getElementById('salle').value = '';
    document.getElementById('enseignant').value = '';
    document.getElementById('details').value = '';

    effacerErreurs();

}

// ==========================================
// AJOUT
// ==========================================

function openAddModal() {

    viderFormulaire();

    document.getElementById('modal-title').textContent = "Ajouter un créneau horaire";
    document.getElementById('form-action').value = "ajouter_creneau";
    document.getElementById('creneau_id').value = "";
    document.getElementById('submit-btn').textContent = "Enregistrer";

    toggleModal(true);

}

// ==========================================
// MODIFICATION
// ==========================================

function openEditModal(creneau) {

    if (!creneau || !creneau.id) {
        alert("Impossible de récupérer les informations du créneau.");
        return;
    }

    viderFormulaire();

    document.getElementById('modal-title').textContent = "Modifier le créneau horaire";
    document.getElementById('form-action').value = "modifier_creneau";
    document.getElementById('creneau_id').value = creneau.id;

    document.getElementById('classe_id').value = String(creneau.classe_id);
    document.getElementById('jour').value = creneau.jour;
    document.getElementById('heure_debut').value = creneau.heure_debut;
    document.getElementById('matiere').value = creneau.matiere;
    document.getElementById('salle').value = creneau.salle ?? '';
    document.getElementById('enseignant').value = creneau.enseignant ?? '';
    document.getElementById('details').value = creneau.details ?? '';

    document.getElementById('submit-btn').textContent = "Modifier";

    toggleModal(true);

}

// ÉCOUTEUR DES BOUTONS MODIFIER

document.addEventListener('click', function(e) {

    const button = e.target.closest('.btn-edit-creneau');

    if (!button) return;

    try {

        const creneau = JSON.parse(button.dataset.creneau);

        openEditModal(creneau);

    } catch (error) {

        console.error("Erreur chargement créneau :", error);
        alert("Impossible de charger les informations du créneau.");

    }

});

// ==========================================
// RÉOUVERTURE DE LA MODALE SI ERREURS SERVEUR
// ==========================================

<?php if ($aDesErreurs): ?>

document.addEventListener('DOMContentLoaded', () => toggleModal(true));

<?php endif; ?>

// ==========================================
// SUPPRESSION
// ==========================================

let creneauToDeleteId = null;
let creneauElement = null;
let deletedCreneauData = null;
let deleteReloadTimer = null;

function openDeleteModal(id, element, matiere) {

    creneauToDeleteId = Number(id);
    creneauElement = element;
    deletedCreneauData = null;

    const modal = document.getElementById('deleteModal');
    const content = document.getElementById('deleteModalContent');
    const message = document.getElementById('deleteModalMessage');
    const button = document.getElementById('deleteConfirmBtn');

    message.textContent = `Voulez-vous vraiment supprimer le créneau "${matiere}" ?`;

    button.disabled = false;
    button.textContent = 'Supprimer';

    modal.classList.remove('hidden');
    modal.classList.add('flex');

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
        modal.classList.remove('flex');
    }, 300);

}

// ==========================================
// SUPPRESSION AJAX
// ==========================================

async function deleteCreneau() {

    const button = document.getElementById('deleteConfirmBtn');

    button.disabled = true;
    button.textContent = 'Suppression...';

    try {

        const response = await fetch('emploi_du_temps.php', {

            method: 'POST',

            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },

            body: JSON.stringify({
                action: 'supprimer_creneau',
                id: creneauToDeleteId
            })

        });

        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error(data.message || "La suppression a échoué.");
        }

        deletedCreneauData = data.data;

        closeDeleteModal();

        if (creneauElement) {
            creneauElement.remove();
        }

        showToast(
            data.message || "Créneau supprimé avec succès.",
            'error',
            restoreCreneau
        );

        clearTimeout(deleteReloadTimer);

        deleteReloadTimer = setTimeout(() => {
            location.reload();
        }, 5000);

    } catch (error) {

        console.error(error);

        alert(error.message || "Erreur lors de la suppression.");

        button.disabled = false;
        button.textContent = 'Supprimer';

    }

}

// ==========================================
// RESTAURATION
// ==========================================

async function restoreCreneau() {

    if (!deletedCreneauData) {
        alert("Aucune donnée disponible pour la restauration.");
        return false;
    }

    try {

        const response = await fetch('emploi_du_temps.php', {

            method: 'POST',

            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },

            body: JSON.stringify({
                action: 'restaurer_creneau',
                data: deletedCreneauData
            })

        });

        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error(data.message || "La restauration a échoué.");
        }

        clearTimeout(deleteReloadTimer);

        deletedCreneauData = null;

        showToast("Créneau restauré avec succès.", "success");

        setTimeout(() => {
            location.reload();
        }, 700);

        return true;

    } catch (error) {

        console.error(error);

        alert(error.message || "Erreur lors de la restauration.");

        return false;

    }

}

// ==========================================
// NOTIFICATIONS APRÈS AJOUT / MODIFICATION
// ==========================================

<?php if (isset($_GET['success'])): ?>

document.addEventListener('DOMContentLoaded', () => {

    <?php if ($_GET['success'] == '1'): ?>

        showToast("Créneau ajouté avec succès.", "success");

    <?php elseif ($_GET['success'] == '2'): ?>

        showToast("Créneau modifié avec succès.", "success");

    <?php endif; ?>

});

<?php endif; ?>

    </script>

</body>
</html>