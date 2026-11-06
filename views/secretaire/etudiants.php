<?php
$pageActuelle = 'etudiants'; 
$titrePage = 'Étudiants & Comptes'; 
$descriptionPage = 'Gérez les inscriptions et comptes étudiants'; 
require_once '../../config/db.php';

$tab = $_GET['tab'] ?? 'etudiants';
$message = "";

// Traitement de l'ajout d'un étudiant
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'ajouter_etudiant') {
    $matricule = trim($_POST['matricule']);
    $nom = trim($_POST['nom']);
    $prenom = trim($_POST['prenom']);
    $date_naissance = trim($_POST['date_naissance']);
    $sexe = trim($_POST['sexe']);
    $telephone = trim($_POST['telephone']);
    $adresse = trim($_POST['adresse']);
    $classe_id = !empty($_POST['classe_id']) ? $_POST['classe_id'] : null;

    if (!empty($matricule) && !empty($nom) && !empty($prenom)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO etudiants (matricule, nom, prenom, date_naissance, sexe, telephone, adresse, classe_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$matricule, $nom, $prenom, $date_naissance, $sexe, $telephone, $adresse, $classe_id]);
            header("Location: etudiants.php?tab=etudiants");
            exit();
        } catch (PDOException $e) {
            $message = "Erreur : Ce matricule existe déjà.";
        }
    } else {
        $message = "Veuillez remplir les champs obligatoires (*).";
    }
}

// Récupération des données
$etudiants = [];
$utilisateurs = [];
$classes = [];
try {
    $etudiants = $pdo->query("SELECT e.*, c.code_classe, c.nom as nom_classe, u.email as user_email 
                              FROM etudiants e 
                              LEFT JOIN classes c ON e.classe_id = c.id 
                              LEFT JOIN users u ON e.user_id = u.id 
                              ORDER BY e.id DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $etudiants = [];
}

try {
    $utilisateurs = $pdo->query("SELECT u.*, e.matricule as etudiant_matricule 
                                 FROM users u 
                                 LEFT JOIN etudiants e ON e.user_id = u.id 
                                 ORDER BY u.id ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $utilisateurs = [];
}

try {
    $classes = $pdo->query("SELECT * FROM classes ORDER BY nom ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $classes = [];
}

// Données de démonstration conformes aux maquettes (si les tables sont vides)
if (count($etudiants) === 0) {
    $etudiants = [
        ['matricule' => 'ESP221-2024-001', 'nom' => 'Diallo', 'prenom' => 'Mamadou', 'date_naissance' => '15/03/2002', 'sexe' => 'Masculin', 'telephone' => '+221 77 123 45 67', 'adresse' => 'Dakar, Médina',  'code_classe' => 'INFO-L1-A', 'user_email' => 'etudiant1@esp221.sn'],
        ['matricule' => 'ESP221-2024-002', 'nom' => 'Sall',   'prenom' => 'Aminata', 'date_naissance' => '22/07/2003', 'sexe' => 'Féminin',  'telephone' => '+221 78 234 56 78', 'adresse' => 'Dakar, Plateau', 'code_classe' => 'INFO-L1-A', 'user_email' => 'etudiant2@esp221.sn'],
        ['matricule' => 'ESP221-2024-003', 'nom' => 'Ndiaye', 'prenom' => 'Oumar',   'date_naissance' => '08/11/2001', 'sexe' => 'Masculin', 'telephone' => '+221 76 345 67 89', 'adresse' => 'Thiès, Centre',  'code_classe' => 'INFO-L2-A', 'user_email' => 'etudiant3@esp221.sn'],
        ['matricule' => 'ESP221-2024-004', 'nom' => 'Faye',   'prenom' => 'Khady',   'date_naissance' => '15/03/2002', 'sexe' => 'Masculin', 'telephone' => '+221 77 123 45 67', 'adresse' => 'Dakar, Médina',  'code_classe' => 'INFO-L1-A', 'user_email' => null],
        ['matricule' => 'ESP221-2024-005', 'nom' => 'Mbaye',  'prenom' => 'Serigne', 'date_naissance' => '15/03/2002', 'sexe' => 'Masculin', 'telephone' => '+221 77 123 45 67', 'adresse' => 'Dakar, Médina',  'code_classe' => 'INFO-L1-A', 'user_email' => null],
    ];
}
if (count($utilisateurs) === 0) {
    $utilisateurs = [];
    for ($i = 0; $i <= 5; $i++) {
        $utilisateurs[] = ['id' => $i, 'email' => 'secretaire@esp221.sn', 'sexe' => 'Féminin', 'adresse' => 'Dakar, Plateau', 'telephone' => '+221 33 000 00 00', 'etudiant_matricule' => null];
    }
}

// Mise en forme de la date (AAAA-MM-JJ -> JJ/MM/AAAA)
function fmtDate($d) {
    if (empty($d)) return '-';
    if (preg_match('/^\d{4}-\d{2}-\d{2}/', $d)) return date('d/m/Y', strtotime($d));
    return $d;
}

$total = ($tab === 'utilisateurs') ? count($utilisateurs) : count($etudiants);
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

            <!-- Système d'onglets -->
            <div class="flex items-center space-x-2 border-b border-gray-200 pb-2">
                <a href="etudiants.php?tab=etudiants" class="px-3 py-1.5 rounded-lg text-xs font-semibold transition <?= ($tab !== 'utilisateurs') ? 'bg-red-50 text-red-600' : 'text-slate-500 hover:text-gray-800' ?>">
                    Étudiants
                </a>
                <a href="etudiants.php?tab=utilisateurs" class="px-3 py-1.5 rounded-lg text-xs font-semibold transition <?= ($tab === 'utilisateurs') ? 'bg-red-50 text-red-600' : 'text-slate-500 hover:text-gray-800' ?>">
                    Comptes
                </a>
            </div>

            <!-- Barre d'outils : Recherche (gauche) + Bouton Ajouter (droite) -->
            <div class="flex items-center justify-between">
                <div class="relative w-80">
                    <input type="text" placeholder="<?= ($tab !== 'utilisateurs') ? 'Rechercher un étudiant...' : 'Rechercher un utilisateur...' ?>" class="w-full pl-4 pr-10 py-2.5 bg-white border border-gray-200 rounded-lg text-sm text-gray-700 placeholder-gray-400 focus:outline-none focus:border-red-600 transition">
                    <span class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-gray-800 pointer-events-none">
                        <i class="fa-solid fa-magnifying-glass text-sm"></i>
                    </span>
                </div>
                <button onclick="toggleDrawer(true)" class="bg-red-600 text-white px-5 py-2.5 rounded-lg text-sm font-semibold hover:bg-red-700 transition flex items-center space-x-2 shadow-sm">
                    <span><?= ($tab !== 'utilisateurs') ? 'Ajouter un étudiant' : 'Ajouter un utilisateur' ?></span>
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

                <?php if ($tab !== 'utilisateurs'): ?>

                <!-- VUE TABLEAU : ÉTUDIANTS -->
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
                                <th class="py-4 px-4 font-semibold">Utilisateur</th>
                                <th class="py-4 px-4 font-semibold">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-xs">
                            <?php foreach ($etudiants as $e): ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="py-4 px-4 text-slate-500 whitespace-nowrap"><?= htmlspecialchars($e['matricule']) ?></td>
                                <td class="py-4 px-4 text-sm font-bold text-gray-900"><?= htmlspecialchars($e['nom']) ?></td>
                                <td class="py-4 px-4 text-sm font-bold text-gray-900"><?= htmlspecialchars($e['prenom']) ?></td>
                                <td class="py-4 px-4 text-slate-500"><?= htmlspecialchars(fmtDate($e['date_naissance'] ?? '')) ?></td>
                                <td class="py-4 px-4"><span class="inline-block bg-gray-100 text-gray-600 px-2.5 py-1 rounded-full text-[11px]"><?= htmlspecialchars($e['sexe'] ?? '-') ?></span></td>
                                <td class="py-4 px-4 text-slate-500 whitespace-nowrap"><?= htmlspecialchars($e['telephone'] ?? '-') ?></td>
                                <td class="py-4 px-4 text-slate-500 whitespace-nowrap"><?= htmlspecialchars($e['adresse'] ?? '-') ?></td>
                                <td class="py-4 px-4"><span class="inline-block bg-blue-50 text-blue-600 px-2.5 py-1 rounded-full font-medium text-[11px]"><?= htmlspecialchars($e['code_classe'] ?? '-') ?></span></td>
                                <td class="py-4 px-4 text-slate-500 whitespace-nowrap"><?= htmlspecialchars($e['user_email'] ?? 'Non associé') ?></td>
                                <td class="py-4 px-4 space-x-3 whitespace-nowrap">
                                    <button class="text-slate-500 hover:text-blue-600 transition" title="Modifier"><i class="fa-solid fa-pen"></i></button>
                                    <button class="text-slate-500 hover:text-red-600 transition" title="Supprimer"><i class="fa-regular fa-trash-can"></i></button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- VUE CARTES : ÉTUDIANTS -->
                <div id="view-cards" class="hidden p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                        <?php foreach ($etudiants as $e): ?>
                        <div class="bg-white border border-gray-100 rounded-xl shadow-md p-4">
                            <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                                <span class="text-sm text-gray-800"><?= htmlspecialchars($e['matricule']) ?></span>
                                <div class="flex items-center space-x-3 text-slate-500">
                                    <button class="hover:text-blue-600 transition" title="Modifier"><i class="fa-solid fa-pen text-xs"></i></button>
                                    <button class="hover:text-red-600 transition" title="Supprimer"><i class="fa-regular fa-trash-can text-xs"></i></button>
                                </div>
                            </div>
                            <div class="pt-3 space-y-3">
                                <div>
                                    <p class="text-xs text-slate-500">Nom</p>
                                    <p class="text-sm font-bold text-gray-900"><?= htmlspecialchars($e['nom']) ?></p>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-500">Prénom</p>
                                    <p class="text-sm font-bold text-gray-900"><?= htmlspecialchars($e['prenom']) ?></p>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-500">Date de naissance</p>
                                    <p class="text-xs text-slate-600"><?= htmlspecialchars(fmtDate($e['date_naissance'] ?? '')) ?></p>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-500 mb-1">Sexe</p>
                                    <span class="inline-block bg-gray-100 text-gray-600 px-2.5 py-1 rounded-full text-[11px]"><?= htmlspecialchars($e['sexe'] ?? '-') ?></span>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-500">Téléphone</p>
                                    <p class="text-xs text-slate-600"><?= htmlspecialchars($e['telephone'] ?? '-') ?></p>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-500">Adresse</p>
                                    <p class="text-xs text-slate-600"><?= htmlspecialchars($e['adresse'] ?? '-') ?></p>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-500 mb-1">Classe</p>
                                    <span class="inline-block bg-blue-50 text-blue-700 px-2.5 py-1 rounded-full text-[11px] font-medium"><?= htmlspecialchars($e['code_classe'] ?? '-') ?></span>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-500">Utilisateur</p>
                                    <p class="text-xs text-slate-600"><?= htmlspecialchars($e['user_email'] ?? 'Non associé') ?></p>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <?php else: ?>

                <!-- VUE TABLEAU : COMPTES -->
                <div id="view-table" class="overflow-x-auto">
                    <table class="w-full text-center border-collapse">
                        <thead>
                            <tr class="bg-gray-200 text-slate-500 text-[11px] uppercase tracking-wider">
                                <th class="py-4 px-4 font-semibold">ID utilisateur</th>
                                <th class="py-4 px-4 font-semibold">E-mail</th>
                                <th class="py-4 px-4 font-semibold">Mot de passe</th>
                                <th class="py-4 px-4 font-semibold">Sexe</th>
                                <th class="py-4 px-4 font-semibold">Adresse</th>
                                <th class="py-4 px-4 font-semibold">Téléphone</th>
                                <th class="py-4 px-4 font-semibold">Associé</th>
                                <th class="py-4 px-4 font-semibold">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-xs">
                            <?php foreach ($utilisateurs as $u): ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="py-5 px-4 text-sm text-gray-800"><?= htmlspecialchars($u['id']) ?></td>
                                <td class="py-5 px-4 font-bold text-gray-900"><?= htmlspecialchars($u['email']) ?></td>
                                <td class="py-5 px-4 text-slate-500">••••••••</td>
                                <td class="py-5 px-4"><span class="inline-block bg-gray-100 text-gray-600 px-2.5 py-1 rounded-full text-[11px]"><?= htmlspecialchars($u['sexe'] ?? '-') ?></span></td>
                                <td class="py-5 px-4 font-bold text-slate-600 whitespace-nowrap"><?= htmlspecialchars($u['adresse'] ?? '-') ?></td>
                                <td class="py-5 px-4 text-slate-500 whitespace-nowrap"><?= htmlspecialchars($u['telephone'] ?? '-') ?></td>
                                <td class="py-5 px-4"><span class="inline-block bg-gray-100 text-gray-600 px-2.5 py-1 rounded-full text-[11px]"><?= htmlspecialchars($u['etudiant_matricule'] ?? 'Aucun') ?></span></td>
                                <td class="py-5 px-4 space-x-3 whitespace-nowrap">
                                    <button class="text-slate-500 hover:text-blue-600 transition" title="Modifier"><i class="fa-solid fa-pen"></i></button>
                                    <button class="text-slate-500 hover:text-red-600 transition" title="Supprimer"><i class="fa-regular fa-trash-can"></i></button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- VUE CARTES : COMPTES -->
                <div id="view-cards" class="hidden p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                        <?php foreach ($utilisateurs as $u): ?>
                        <div class="bg-white border border-gray-100 rounded-xl shadow-md p-4">
                            <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                                <span class="text-sm text-gray-800">#<?= htmlspecialchars($u['id']) ?></span>
                                <div class="flex items-center space-x-3 text-slate-500">
                                    <button class="hover:text-blue-600 transition" title="Modifier"><i class="fa-solid fa-pen text-xs"></i></button>
                                    <button class="hover:text-red-600 transition" title="Supprimer"><i class="fa-regular fa-trash-can text-xs"></i></button>
                                </div>
                            </div>
                            <div class="pt-3 space-y-3">
                                <div>
                                    <p class="text-xs text-slate-500">E-mail</p>
                                    <p class="text-sm font-bold text-gray-900"><?= htmlspecialchars($u['email']) ?></p>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-500 mb-1">Sexe</p>
                                    <span class="inline-block bg-gray-100 text-gray-600 px-2.5 py-1 rounded-full text-[11px]"><?= htmlspecialchars($u['sexe'] ?? '-') ?></span>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-500">Adresse</p>
                                    <p class="text-xs text-slate-600"><?= htmlspecialchars($u['adresse'] ?? '-') ?></p>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-500">Téléphone</p>
                                    <p class="text-xs text-slate-600"><?= htmlspecialchars($u['telephone'] ?? '-') ?></p>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-500 mb-1">Associé</p>
                                    <span class="inline-block bg-gray-100 text-gray-600 px-2.5 py-1 rounded-full text-[11px]"><?= htmlspecialchars($u['etudiant_matricule'] ?? 'Aucun') ?></span>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <?php endif; ?>

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

    <!-- DRAWER LATÉRAL -->
    <div id="drawer-overlay" class="fixed inset-0 bg-black/50 z-50 hidden transition-opacity opacity-0" onclick="toggleDrawer(false)"></div>
    
    <div id="drawer" class="fixed top-0 right-0 h-full w-[440px] bg-white shadow-2xl z-50 transform translate-x-full transition-transform duration-300 ease-in-out flex flex-col justify-between overflow-y-auto">
        <div>
            <div class="p-6 border-b border-gray-100 flex justify-between items-center sticky top-0 bg-white z-10">
                <h3 class="font-bold text-base text-gray-800">Nouvelle etudiant</h3>
                <button onclick="toggleDrawer(false)" class="text-gray-400 hover:text-gray-600 text-xl font-bold">&times;</button>
            </div>

            <form action="etudiants.php?tab=etudiants" method="POST" class="p-6 space-y-4">
                <input type="hidden" name="action" value="ajouter_etudiant">
                
                <h4 class="text-xs font-bold text-red-600 uppercase tracking-wider">Information etudiant</h4>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Matricule</label>
                    <input type="text" name="matricule" required placeholder="ESP221-2024-007" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Nom*</label>
                        <input type="text" name="nom" required placeholder="Diallo" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Prénom*</label>
                        <input type="text" name="prenom" required placeholder="Mamadou" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Date de naissance</label>
                    <input type="date" name="date_naissance" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm bg-white focus:outline-none focus:border-red-600">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Sexe</label>
                    <select name="sexe" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm bg-white focus:outline-none focus:border-red-600">
                        <option value="Masculin">Masculin</option>
                        <option value="Féminin">Féminin</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Téléphone</label>
                    <input type="text" name="telephone" placeholder="+221 77 000 00 00" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Adresse</label>
                    <input type="text" name="adresse" placeholder="Ville, Quartier" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Classe*</label>
                    <select name="classe_id" required class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm bg-white focus:outline-none focus:border-red-600">
                        <option value="">Sélectionner une classe</option>
                        <?php foreach ($classes as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nom']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <p class="text-[11px] text-gray-400 italic pt-1">
                    Le compte utilisateur est facultatif. Créez-le ou associez-le depuis l'onglet Utilisateurs.
                </p>

                <div class="pt-4 flex items-center justify-end space-x-3 border-t border-gray-100">
                    <button type="button" onclick="toggleDrawer(false)" class="px-4 py-2.5 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition">
                        Annuler
                    </button>
                    <button type="submit" class="px-4 py-2.5 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition shadow-sm">
                        Ajouter l'étudiant
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