<?php
$pageActuelle = 'etudiants';
require_once '../../config/db.php';

$message = "";
$tab = $_GET['tab'] ?? 'etudiants'; // Gère l'onglet actif ('etudiants' ou 'comptes')

// Traitement de l'ajout d'un étudiant
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'ajouter_etudiant') {
    $matricule = trim($_POST['matricule']);
    $nom = trim($_POST['nom']);
    $prenom = trim($_POST['prenom']);
    $date_naissance = trim($_POST['date_naissance']);
    $sexe = trim($_POST['sexe']);
    $telephone = trim($_POST['telephone']);
    $adresse = trim($_POST['adresse']);
    $classe_id = trim($_POST['classe_id']);

    if (!empty($matricule) && !empty($nom) && !empty($prenom) && !empty($classe_id)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO etudiants (matricule, nom, prenom, date_naissance, sexe, telephone, adresse, classe_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$matricule, $nom, $prenom, $date_naissance, $sexe, $telephone, $adresse, $classe_id]);
            header("Location: etudiants.php?tab=etudiants");
            exit();
        } catch (PDOException $e) {
            $message = "Erreur : Ce matricule existe déjà.";
        }
    } else {
        $message = "Veuillez remplir tous les champs obligatoires.";
    }
}

// Traitement de l'ajout d'un compte utilisateur
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'ajouter_compte') {
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $sexe = trim($_POST['sexe']);
    $telephone = trim($_POST['telephone']);
    $adresse = trim($_POST['adresse']);
    $etudiant_id = !empty($_POST['etudiant_id']) ? $_POST['etudiant_id'] : null;

    if (!empty($email) && !empty($_POST['password'])) {
        try {
            $stmt = $pdo->prepare("INSERT INTO users (email, password, sexe, telephone, adresse, etudiant_id, role) VALUES (?, ?, ?, ?, ?, ?, 'etudiant')");
            $stmt->execute([$email, $password, $sexe, $telephone, $adresse, $etudiant_id]);
            header("Location: etudiants.php?tab=comptes");
            exit();
        } catch (PDOException $e) {
            $message = "Erreur : Cet e-mail est déjà utilisé.";
        }
    } else {
        $message = "Veuillez remplir les champs e-mail et mot de passe.";
    }
}

// Récupération des listes
$queryEtudiants = "SELECT etudiants.*, classes.code_classe FROM etudiants LEFT JOIN classes ON etudiants.classe_id = classes.id ORDER BY etudiants.id DESC";
$etudiants = $pdo->query($queryEtudiants)->fetchAll();

$queryComptes = "SELECT users.*, etudiants.nom, etudiants.prenom FROM users LEFT JOIN etudiants ON users.etudiant_id = etudiants.id ORDER BY users.id DESC";
$comptes = $pdo->query($queryComptes)->fetchAll();

$classes = $pdo->query("SELECT * FROM classes ORDER BY code_classe ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Administration — Étudiants & Comptes</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 flex h-screen overflow-hidden">

    <!-- Sidebar Unifiée -->
    <?php include '../../includes/sidebar_secretaire.php'; ?>

    <!-- Contenu Principal -->
    <main class="flex-1 flex flex-col h-screen overflow-hidden">
        
        <!-- Header Supérieur -->
        <header class="bg-white border-b border-gray-100 px-8 py-4 flex justify-between items-center">
            <div>
                <h2 class="text-xl font-bold text-gray-800">Étudiants & Comptes</h2>
                <p class="text-xs text-gray-500">Gérez les inscriptions et comptes étudiants</p>
            </div>
            <div class="flex items-center space-x-4">
                <div class="relative">
                    <span class="absolute -top-1 -right-1 bg-red-600 text-white text-[10px] w-4 h-4 rounded-full flex items-center justify-center font-bold">2</span>
                    <button class="p-2 text-gray-400 hover:text-gray-600 bg-gray-50 rounded-full">🔔</button>
                </div>
                <div class="flex items-center space-x-2 border-l pl-4 border-gray-200">
                    <div class="w-8 h-8 bg-red-600 text-white rounded-full flex items-center justify-center font-bold text-sm">S</div>
                    <div>
                        <p class="text-xs font-bold text-gray-800">Secrétaire</p>
                        <p class="text-[10px] text-gray-500">Secrétaire</p>
                    </div>
                </div>
            </div>
        </header>

        <!-- Corps de la page -->
        <div class="flex-1 p-8 overflow-y-auto">
            
            <!-- Onglets (Étudiants / Comptes) -->
            <div class="flex space-x-4 border-b border-gray-200 mb-6">
                <a href="etudiants.php?tab=etudiants" class="pb-3 text-sm font-semibold border-b-2 transition <?= $tab == 'etudiants' ? 'border-red-600 text-red-600' : 'border-transparent text-gray-400 hover:text-gray-600' ?>">
                    Étudiants
                </a>
                <a href="etudiants.php?tab=comptes" class="pb-3 text-sm font-semibold border-b-2 transition <?= $tab == 'comptes' ? 'border-red-600 text-red-600' : 'border-transparent text-gray-400 hover:text-gray-600' ?>">
                    Comptes
                </a>
            </div>

            <!-- Barre de recherche et Bouton Ajouter / Vues -->
            <div class="flex justify-between items-center mb-6">
                <div class="relative w-96">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">🔍</span>
                    <input type="text" placeholder="<?= $tab == 'etudiants' ? 'Rechercher un étudiant...' : 'Rechercher un compte...' ?>" class="w-full pl-10 pr-4 py-2 bg-white border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600">
                </div>
                <div class="flex items-center space-x-3">
                    <div class="bg-white border border-gray-200 rounded-lg p-1 flex text-xs">
                        <button class="px-3 py-1 bg-red-50 text-red-600 font-semibold rounded">Tableau</button>
                        <button class="px-3 py-1 text-gray-500 hover:text-gray-800">Cartes</button>
                    </div>
                    <?php if ($tab == 'etudiants'): ?>
                        <button onclick="toggleDrawer('drawer-etudiant', true)" class="bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-red-700 transition flex items-center space-x-1">
                            <span>Ajouter un étudiant</span> <span class="font-bold">+</span>
                        </button>
                    <?php else: ?>
                        <button onclick="toggleDrawer('drawer-compte', true)" class="bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-red-700 transition flex items-center space-x-1">
                            <span>Nouvelle utilisateur</span> <span class="font-bold">+</span>
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($message)): ?>
                <div class="mb-4 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 text-sm rounded">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <!-- TABLEAU ÉTUDIANTS -->
            <?php if ($tab == 'etudiants'): ?>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 text-gray-400 text-xs uppercase tracking-wider border-b border-gray-100">
                            <th class="p-4 font-semibold">Matricule</th>
                            <th class="p-4 font-semibold">Nom</th>
                            <th class="p-4 font-semibold">Prénom</th>
                            <th class="p-4 font-semibold">Date de naissance</th>
                            <th class="p-4 font-semibold">Sexe</th>
                            <th class="p-4 font-semibold">Téléphone</th>
                            <th class="p-4 font-semibold">Adresse</th>
                            <th class="p-4 font-semibold">Classe</th>
                            <th class="p-4 font-semibold text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm text-gray-600">
                        <?php if (count($etudiants) > 0): ?>
                            <?php foreach ($etudiants as $e): ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="p-4 font-bold text-gray-700"><span class="bg-gray-100 px-2.5 py-1 rounded-md text-xs"><?= htmlspecialchars($e['matricule']) ?></span></td>
                                <td class="p-4 font-semibold text-gray-800"><?= htmlspecialchars($e['nom']) ?></td>
                                <td class="p-4 text-gray-800"><?= htmlspecialchars($e['prenom']) ?></td>
                                <td class="p-4 text-gray-500"><?= htmlspecialchars($e['date_naissance']) ?></td>
                                <td class="p-4"><span class="bg-gray-100 text-gray-700 px-2 py-0.5 rounded text-xs"><?= htmlspecialchars($e['sexe']) ?></span></td>
                                <td class="p-4 text-gray-500"><?= htmlspecialchars($e['telephone']) ?></td>
                                <td class="p-4 text-gray-500"><?= htmlspecialchars($e['adresse']) ?></td>
                                <td class="p-4"><span class="bg-blue-50 text-blue-600 px-2.5 py-1 rounded-md text-xs font-bold"><?= htmlspecialchars($e['code_classe'] ?? 'N/A') ?></span></td>
                                <td class="p-4 text-right space-x-2">
                                    <button class="text-gray-400 hover:text-blue-600 transition" title="Modifier">✏️</button>
                                    <button class="text-gray-400 hover:text-red-600 transition" title="Supprimer">🗑️</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="9" class="p-8 text-center text-gray-400">Aucun étudiant enregistré.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- TABLEAU COMPTES -->
            <?php else: ?>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 text-gray-400 text-xs uppercase tracking-wider border-b border-gray-100">
                            <th class="p-4 font-semibold">ID Utilisateur</th>
                            <th class="p-4 font-semibold">E-mail</th>
                            <th class="p-4 font-semibold">Mot de passe</th>
                            <th class="p-4 font-semibold">Sexe</th>
                            <th class="p-4 font-semibold">Adresse</th>
                            <th class="p-4 font-semibold">Téléphone</th>
                            <th class="p-4 font-semibold">Associé</th>
                            <th class="p-4 font-semibold text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm text-gray-600">
                        <?php if (count($comptes) > 0): ?>
                            <?php foreach ($comptes as $c): ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="p-4 font-bold text-gray-700"><?= htmlspecialchars($c['id']) ?></td>
                                <td class="p-4 font-semibold text-gray-800"><?= htmlspecialchars($c['email']) ?></td>
                                <td class="p-4 text-gray-400">••••••••</td>
                                <td class="p-4"><span class="bg-gray-100 text-gray-700 px-2 py-0.5 rounded text-xs"><?= htmlspecialchars($c['sexe']) ?></span></td>
                                <td class="p-4 text-gray-500"><?= htmlspecialchars($c['adresse']) ?></td>
                                <td class="p-4 text-gray-500"><?= htmlspecialchars($c['telephone']) ?></td>
                                <td class="p-4"><span class="bg-gray-100 text-gray-600 px-2 py-1 rounded text-xs"><?= $c['nom'] ? htmlspecialchars($c['nom'].' '.$c['prenom']) : 'Aucun' ?></span></td>
                                <td class="p-4 text-right space-x-2">
                                    <button class="text-gray-400 hover:text-blue-600 transition">✏️</button>
                                    <button class="text-gray-400 hover:text-red-600 transition">🗑️</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="8" class="p-8 text-center text-gray-400">Aucun compte utilisateur enregistré.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- DRAWER AJOUT ÉTUDIANT -->
    <div id="overlay-etudiant" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden transition-opacity opacity-0" onclick="toggleDrawer('drawer-etudiant', false)"></div>
    <div id="drawer-etudiant" class="fixed top-0 right-0 h-full w-[420px] bg-white shadow-2xl z-50 transform translate-x-full transition-transform duration-300 ease-in-out flex flex-col justify-between overflow-y-auto">
        <div>
            <div class="p-6 border-b border-gray-100 flex justify-between items-center">
                <h3 class="font-bold text-lg text-gray-800">Nouvelle étudiant</h3>
                <button onclick="toggleDrawer('drawer-etudiant', false)" class="text-gray-400 hover:text-gray-600 text-xl font-bold">&times;</button>
            </div>
            <form action="etudiants.php?tab=etudiants" method="POST" class="p-6 space-y-4">
                <input type="hidden" name="action" value="ajouter_etudiant">
                <p class="text-xs font-bold text-red-600 uppercase tracking-wider mb-2">Information Étudiant</p>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Matricule</label>
                    <input type="text" name="matricule" value="ESP221-2024-00<? = rand(6,9) ?>" required class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600">
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Nom*</label>
                        <input type="text" name="nom" required placeholder="Nom" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Prénom*</label>
                        <input type="text" name="prenom" required placeholder="Prénom" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Date de naissance</label>
                    <input type="date" name="date_naissance" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Sexe</label>
                    <select name="sexe" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm bg-white focus:outline-none focus:border-red-600">
                        <option value="Masculin">Masculin</option>
                        <option value="Féminin">Féminin</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Téléphone</label>
                    <input type="text" name="telephone" value="+221 77 000 00 00" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Adresse</label>
                    <input type="text" name="adresse" placeholder="Ville, Quartier" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Classe*</label>
                    <select name="classe_id" required class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm bg-white focus:outline-none focus:border-red-600">
                        <option value="">Sélectionner une classe</option>
                        <?php foreach ($classes as $cls): ?>
                            <option value="<?= $cls['id'] ?>"><?= htmlspecialchars($cls['nom']) ?> (<?= htmlspecialchars($cls['code_classe']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <p class="text-[11px] text-gray-400 italic">Le compte utilisateur est facultatif. Créez-le ou associez-le depuis l'onglet Comptes.</p>
                <div class="pt-4 flex space-x-2">
                    <button type="button" onclick="toggleDrawer('drawer-etudiant', false)" class="w-1/2 bg-white border border-gray-200 text-gray-700 py-2.5 rounded-lg text-sm font-medium hover:bg-gray-50 transition">Annuler</button>
                    <button type="submit" class="w-1/2 bg-red-600 text-white py-2.5 rounded-lg text-sm font-medium hover:bg-red-700 transition">Ajouter</button>
                </div>
            </form>
        </div>
    </div>

    <!-- DRAWER NOUVELLE UTILISATEUR (COMPTE) -->
    <div id="overlay-compte" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden transition-opacity opacity-0" onclick="toggleDrawer('drawer-compte', false)"></div>
    <div id="drawer-compte" class="fixed top-0 right-0 h-full w-[420px] bg-white shadow-2xl z-50 transform translate-x-full transition-transform duration-300 ease-in-out flex flex-col justify-between overflow-y-auto">
        <div>
            <div class="p-6 border-b border-gray-100 flex justify-between items-center">
                <h3 class="font-bold text-lg text-gray-800">Nouvelle utilisateur</h3>
                <button onclick="toggleDrawer('drawer-compte', false)" class="text-gray-400 hover:text-gray-600 text-xl font-bold">&times;</button>
            </div>
            <form action="etudiants.php?tab=comptes" method="POST" class="p-6 space-y-4">
                <input type="hidden" name="action" value="ajouter_compte">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">E-mail*</label>
                    <input type="email" name="email" required placeholder="etudiant@esp221.sn" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Mot de passe*</label>
                    <input type="password" name="password" required placeholder="Au moins 4 caractères" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Sexe</label>
                    <select name="sexe" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm bg-white focus:outline-none focus:border-red-600">
                        <option value="Masculin">Masculin</option>
                        <option value="Féminin">Féminin</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Téléphone</label>
                    <input type="text" name="telephone" value="+221 76 252 21 22" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Adresse</label>
                    <input type="text" name="adresse" placeholder="Medina" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Étudiant associé (facultatif)</label>
                    <select name="etudiant_id" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm bg-white focus:outline-none focus:border-red-600">
                        <option value="">Aucun étudiant</option>
                        <?php foreach ($etudiants as $et): ?>
                            <option value="<?= $et['id'] ?>"><?= htmlspecialchars($et['nom'] . ' ' . $et['prenom']) ?> (<?= htmlspecialchars($et['matricule']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="pt-4 flex space-x-2">
                    <button type="button" onclick="toggleDrawer('drawer-compte', false)" class="w-1/2 bg-white border border-gray-200 text-gray-700 py-2.5 rounded-lg text-sm font-medium hover:bg-gray-50 transition">Annuler</button>
                    <button type="submit" class="w-1/2 bg-red-600 text-white py-2.5 rounded-lg text-sm font-medium hover:bg-red-700 transition">Ajouter l'utilisateur</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleDrawer(drawerId, open) {
            const drawer = document.getElementById(drawerId);
            const overlayId = drawerId === 'drawer-etudiant' ? 'overlay-etudiant' : 'overlay-compte';
            const overlay = document.getElementById(overlayId);
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
    </script>
</body>
</html>