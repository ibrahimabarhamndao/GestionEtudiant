<?php
$pageActuelle = 'etudiants'; 
$titrePage = 'Étudiants et comptes'; 
$descriptionPage = 'Gérez les inscriptions et comptes étudiants'; 
require_once '../../config/db.php';

// Gestion de l'onglet actif (par défaut 'etudiants')
$tab = $_GET['tab'] ?? 'etudiants';

// Traitement de l'ajout d'un étudiant
$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'ajouter_etudiant') {
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
        }
    }
}

// Récupération des effectifs et listes
$countEtudiants = 6;
$countUtilisateurs = 7;
$etudiants = [];
$utilisateurs = [];

try {
    $countEtudiants = $pdo->query("SELECT COUNT(*) FROM etudiants")->fetchColumn() ?: 6;
    $countUtilisateurs = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn() ?: 7;

    // Jointure étudiants -> classe & user
    $etudiants = $pdo->query("SELECT e.*, c.code_classe, u.email as user_email 
                              FROM etudiants e 
                              LEFT JOIN classes c ON e.classe_id = c.id 
                              LEFT JOIN users u ON e.user_id = u.id 
                              ORDER BY e.id DESC")->fetchAll(PDO::FETCH_ASSOC);

    // Jointure utilisateurs -> étudiant associé
    $utilisateurs = $pdo->query("SELECT u.*, e.matricule as etudiant_matricule 
                                 FROM users u 
                                 LEFT JOIN etudiants e ON e.user_id = u.id 
                                 ORDER BY u.id ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Si la BD n'est pas encore totalement peuplée
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
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

            <!-- Système d'onglets (Étudiants vs Utilisateurs) -->
            <div class="flex items-center space-x-3 border-b border-gray-200 pb-2">
                <a href="etudiants.php?tab=etudiants" class="px-4 py-2 rounded-xl text-sm font-semibold transition flex items-center space-x-2 <?= ($tab === 'etudiants') ? 'bg-red-50 text-red-600' : 'text-gray-500 hover:text-gray-800' ?>">
                    <span>Étudiants ( <?= count($etudiants) > 0 ? count($etudiants) : $countEtudiants ?> )</span>
                </a>
                <a href="etudiants.php?tab=utilisateurs" class="px-4 py-2 rounded-xl text-sm font-semibold transition flex items-center space-x-2 <?= ($tab === 'utilisateurs') ? 'bg-red-50 text-red-600' : 'text-gray-500 hover:text-gray-800' ?>">
                    <span>Utilisateurs ( <?= count($utilisateurs) > 0 ? count($utilisateurs) : $countUtilisateurs ?> )</span>
                </a>
            </div>

            <!-- Notice d'information sur l'onglet utilisateurs -->
            <?php if ($tab === 'utilisateurs'): ?>
                <p class="text-xs text-gray-400">Données de démonstration — un utilisateur peut être associé à un seul étudiant, et un étudiant à un seul compte.</p>
            <?php endif; ?>

            <!-- Barre d'outils supérieure : Recherche + Bouton Ajouter -->
            <div class="flex items-center space-x-3">
                <div class="relative w-80">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400">
                        <i class="fa-solid fa-magnifying-glass text-xs"></i>
                    </span>
                    <input type="text" placeholder="<?= ($tab === 'etudiants') ? 'Rechercher un étudiant...' : 'Rechercher un utilisateur...' ?>" class="w-full pl-10 pr-4 py-2.5 bg-white border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600 transition shadow-2xs">
                </div>
                
                <?php if ($tab === 'etudiants'): ?>
                    <button onclick="toggleModal(true)" class="bg-red-600 text-white px-4 py-2.5 rounded-lg text-sm font-medium hover:bg-red-700 transition flex items-center space-x-2 shadow-sm">
                        <i class="fa-solid fa-plus text-xs"></i>
                        <span>Ajouter un étudiant</span>
                    </button>
                <?php else: ?>
                    <button onclick="toggleModal(true)" class="bg-red-600 text-white px-4 py-2.5 rounded-lg text-sm font-medium hover:bg-red-700 transition flex items-center space-x-2 shadow-sm">
                        <i class="fa-solid fa-plus text-xs"></i>
                        <span>Ajouter un utilisateur</span>
                    </button>
                <?php endif; ?>
            </div>

            <!-- Carte Conteneur Principale -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 space-y-6">
                
                <!-- Sélecteur de vue (Tableau / Cartes) en haut à droite -->
                <div class="flex justify-end">
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-1 flex text-xs font-medium">
                        <button class="px-3 py-1.5 bg-red-50 text-red-600 rounded-md shadow-2xs flex items-center space-x-1.5">
                            <i class="fa-solid fa-table"></i>
                            <span>Tableau</span>
                        </button>
                        <button class="px-3 py-1.5 text-gray-500 hover:text-gray-800 rounded-md flex items-center space-x-1.5 transition">
                            <i class="fa-solid fa-border-all"></i>
                            <span>Cartes</span>
                        </button>
                    </div>
                </div>

                <!-- CONTENU ONGLET 1 : ÉTUDIANTS -->
                <?php if ($tab === 'etudiants'): ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="text-gray-400 text-[11px] uppercase tracking-wider border-b border-gray-100">
                                <th class="py-3 px-4 font-semibold">MATRICE</th>
                                <th class="py-3 px-4 font-semibold">NOM</th>
                                <th class="py-3 px-4 font-semibold">PRÉNOM</th>
                                <th class="py-3 px-4 font-semibold">DATE DE NAISSANCE</th>
                                <th class="py-3 px-4 font-semibold">SEXE</th>
                                <th class="py-3 px-4 font-semibold">TÉLÉPHONE</th>
                                <th class="py-3 px-4 font-semibold">ADRESSE</th>
                                <th class="py-3 px-4 font-semibold">CLASSE</th>
                                <th class="py-3 px-4 font-semibold">UTILISATEUR</th>
                                <th class="py-3 px-4 font-semibold text-right">ACTES</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-xs text-gray-600">
                            <?php if (count($etudiants) > 0): ?>
                                <?php foreach ($etudiants as $e): ?>
                                <tr class="hover:bg-gray-50/80 transition">
                                    <td class="py-4 px-4 text-gray-400 font-mono"><?= htmlspecialchars($e['matricule']) ?></td>
                                    <td class="py-4 px-4 font-bold text-gray-900"><?= htmlspecialchars($e['nom']) ?></td>
                                    <td class="py-4 px-4 font-medium text-gray-800"><?= htmlspecialchars($e['prenom']) ?></td>
                                    <td class="py-4 px-4 text-gray-500"><?= htmlspecialchars($e['date_naissance']) ?></td>
                                    <td class="py-4 px-4"><span class="bg-gray-100 text-gray-600 px-2 py-1 rounded-md text-[11px]"><?= htmlspecialchars($e['sexe']) ?></span></td>
                                    <td class="py-4 px-4 text-gray-600"><?= htmlspecialchars($e['telephone']) ?></td>
                                    <td class="py-4 px-4 text-gray-500"><?= htmlspecialchars($e['adresse']) ?></td>
                                    <td class="py-4 px-4"><span class="bg-blue-50 text-blue-600 px-2 py-1 rounded-md font-medium text-[11px]"><?= htmlspecialchars($e['code_classe'] ?? 'INFO-L1-A') ?></span></td>
                                    <td class="py-4 px-4 text-gray-400 text-[11px]"><?= htmlspecialchars($e['user_email'] ?? 'Non associé') ?></td>
                                    <td class="py-4 px-4 text-right space-x-2">
                                        <button class="text-gray-400 hover:text-blue-600 transition"><i class="fa-regular fa-pen-to-square"></i></button>
                                        <button class="text-gray-400 hover:text-red-600 transition"><i class="fa-regular fa-trash-can"></i></button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <!-- Données par défaut identiques à la maquette Étudiants -->
                                <tr class="hover:bg-gray-50/80 transition">
                                    <td class="py-4 px-4 text-gray-400 font-mono">ESP221-2024-001</td>
                                    <td class="py-4 px-4 font-bold text-gray-900">Diallo</td>
                                    <td class="py-4 px-4 font-medium text-gray-800">Mamadou</td>
                                    <td class="py-4 px-4 text-gray-500">15/03/2002</td>
                                    <td class="py-4 px-4"><span class="bg-gray-100 text-gray-600 px-2 py-1 rounded-md text-[11px]">Masculin</span></td>
                                    <td class="py-4 px-4 text-gray-600">+221 77 123 45 67</td>
                                    <td class="py-4 px-4 text-gray-500">Dakar, Médina</td>
                                    <td class="py-4 px-4"><span class="bg-blue-50 text-blue-600 px-2 py-1 rounded-md font-medium text-[11px]">INFO-L1-A</span></td>
                                    <td class="py-4 px-4 text-gray-400 text-[11px]">étudiant1@esp221.sn</td>
                                    <td class="py-4 px-4 text-right space-x-2">
                                        <button class="text-gray-400 hover:text-blue-600 transition"><i class="fa-regular fa-pen-to-square"></i></button>
                                        <button class="text-gray-400 hover:text-red-600 transition"><i class="fa-regular fa-trash-can"></i></button>
                                    </td>
                                </tr>
                                <tr class="hover:bg-gray-50/80 transition">
                                    <td class="py-4 px-4 text-gray-400 font-mono">ESP221-2024-002</td>
                                    <td class="py-4 px-4 font-bold text-gray-900">Sall</td>
                                    <td class="py-4 px-4 font-medium text-gray-800">Aminata</td>
                                    <td class="py-4 px-4 text-gray-500">22/07/2003</td>
                                    <td class="py-4 px-4"><span class="bg-gray-100 text-gray-600 px-2 py-1 rounded-md text-[11px]">Féminin</span></td>
                                    <td class="py-4 px-4 text-gray-600">+221 78 234 56 78</td>
                                    <td class="py-4 px-4 text-gray-500">Dakar, Plateau</td>
                                    <td class="py-4 px-4"><span class="bg-blue-50 text-blue-600 px-2 py-1 rounded-md font-medium text-[11px]">INFO-L1-A</span></td>
                                    <td class="py-4 px-4 text-gray-400 text-[11px]">étudiant2@esp221.sn</td>
                                    <td class="py-4 px-4 text-right space-x-2">
                                        <button class="text-gray-400 hover:text-blue-600 transition"><i class="fa-regular fa-pen-to-square"></i></button>
                                        <button class="text-gray-400 hover:text-red-600 transition"><i class="fa-regular fa-trash-can"></i></button>
                                    </td>
                                </tr>
                                <tr class="hover:bg-gray-50/80 transition">
                                    <td class="py-4 px-4 text-gray-400 font-mono">ESP221-2024-003</td>
                                    <td class="py-4 px-4 font-bold text-gray-900">Ndiaye</td>
                                    <td class="py-4 px-4 font-medium text-gray-800">Oumar</td>
                                    <td class="py-4 px-4 text-gray-500">08/11/2001</td>
                                    <td class="py-4 px-4"><span class="bg-gray-100 text-gray-600 px-2 py-1 rounded-md text-[11px]">Masculin</span></td>
                                    <td class="py-4 px-4 text-gray-600">+221 76 345 67 89</td>
                                    <td class="py-4 px-4 text-gray-500">Thiès, Centre</td>
                                    <td class="py-4 px-4"><span class="bg-blue-50 text-blue-600 px-2 py-1 rounded-md font-medium text-[11px]">INFO-L2-A</span></td>
                                    <td class="py-4 px-4 text-gray-400 text-[11px]">étudiant3@esp221.sn</td>
                                    <td class="py-4 px-4 text-right space-x-2">
                                        <button class="text-gray-400 hover:text-blue-600 transition"><i class="fa-regular fa-pen-to-square"></i></button>
                                        <button class="text-gray-400 hover:text-red-600 transition"><i class="fa-regular fa-trash-can"></i></button>
                                    </td>
                                </tr>
                                <tr class="hover:bg-gray-50/80 transition">
                                    <td class="py-4 px-4 text-gray-400 font-mono">ESP221-2024-004</td>
                                    <td class="py-4 px-4 font-bold text-gray-900">Faye</td>
                                    <td class="py-4 px-4 font-medium text-gray-800">Khady</td>
                                    <td class="py-4 px-4 text-gray-500">30/05/2002</td>
                                    <td class="py-4 px-4"><span class="bg-gray-100 text-gray-600 px-2 py-1 rounded-md text-[11px]">Féminin</span></td>
                                    <td class="py-4 px-4 text-gray-600">+221 70 456 78 90</td>
                                    <td class="py-4 px-4 text-gray-500">Dakar, Parcelles</td>
                                    <td class="py-4 px-4"><span class="bg-blue-50 text-blue-600 px-2 py-1 rounded-md font-medium text-[11px]">GEST-L1-A</span></td>
                                    <td class="py-4 px-4 text-gray-400 text-[11px]">étudiant4@esp221.sn</td>
                                    <td class="py-4 px-4 text-right space-x-2">
                                        <button class="text-gray-400 hover:text-blue-600 transition"><i class="fa-regular fa-pen-to-square"></i></button>
                                        <button class="text-gray-400 hover:text-red-600 transition"><i class="fa-regular fa-trash-can"></i></button>
                                    </td>
                                </tr>
                                <tr class="hover:bg-gray-50/80 transition">
                                    <td class="py-4 px-4 text-gray-400 font-mono">ESP221-2024-005</td>
                                    <td class="py-4 px-4 font-bold text-gray-900">Mbaye</td>
                                    <td class="py-4 px-4 font-medium text-gray-800">Serigne</td>
                                    <td class="py-4 px-4 text-gray-500">12/09/2000</td>
                                    <td class="py-4 px-4"><span class="bg-gray-100 text-gray-600 px-2 py-1 rounded-md text-[11px]">Masculin</span></td>
                                    <td class="py-4 px-4 text-gray-600">+221 77 567 89 01</td>
                                    <td class="py-4 px-4 text-gray-500">Saint-Louis</td>
                                    <td class="py-4 px-4"><span class="bg-blue-50 text-blue-600 px-2 py-1 rounded-md font-medium text-[11px]">INFO-L1-A</span></td>
                                    <td class="py-4 px-4 text-gray-400 text-[11px]">étudiant5@esp221.sn</td>
                                    <td class="py-4 px-4 text-right space-x-2">
                                        <button class="text-gray-400 hover:text-blue-600 transition"><i class="fa-regular fa-pen-to-square"></i></button>
                                        <button class="text-gray-400 hover:text-red-600 transition"><i class="fa-regular fa-trash-can"></i></button>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>

                <!-- CONTENU ONGLET 2 : UTILISATEURS -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="text-gray-400 text-[11px] uppercase tracking-wider border-b border-gray-100">
                                <th class="py-3 px-4 font-semibold">ID UTILISATEUR</th>
                                <th class="py-3 px-4 font-semibold">E-MAIL</th>
                                <th class="py-3 px-4 font-semibold">MOT DE PASSE</th>
                                <th class="py-3 px-4 font-semibold">SEXE</th>
                                <th class="py-3 px-4 font-semibold">TÉLÉPHONE</th>
                                <th class="py-3 px-4 font-semibold">ADRESSE</th>
                                <th class="py-3 px-4 font-semibold">ÉTUDIANT ASSOCIÉ</th>
                                <th class="py-3 px-4 font-semibold text-right">ACTES</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-xs text-gray-600">
                            <?php if (count($utilisateurs) > 0): ?>
                                <?php foreach ($utilisateurs as $u): ?>
                                <tr class="hover:bg-gray-50/80 transition">
                                    <td class="py-4 px-4 text-gray-400"><?= htmlspecialchars($u['id']) ?></td>
                                    <td class="py-4 px-4 font-bold text-gray-900"><?= htmlspecialchars($u['email']) ?></td>
                                    <td class="py-4 px-4 text-gray-400">••••••••</td>
                                    <td class="py-4 px-4"><span class="bg-gray-100 text-gray-600 px-2 py-1 rounded-md text-[11px]"><?= htmlspecialchars($u['sexe'] ?? 'Masculin') ?></span></td>
                                    <td class="py-4 px-4 text-gray-600"><?= htmlspecialchars($u['telephone'] ?? '+221 77 000 00 00') ?></td>
                                    <td class="py-4 px-4 text-gray-500"><?= htmlspecialchars($u['adresse'] ?? 'Dakar') ?></td>
                                    <td class="py-4 px-4">
                                        <?php if (!empty($u['etudiant_matricule'])): ?>
                                            <span class="bg-blue-50 text-blue-600 px-2.5 py-1 rounded-md font-medium text-[11px]"><?= htmlspecialchars($u['etudiant_matricule']) ?></span>
                                        <?php else: ?>
                                            <span class="bg-gray-100 text-gray-400 px-2.5 py-1 rounded-md text-[11px]">Aucun étudiant</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-4 px-4 text-right space-x-2">
                                        <button class="text-gray-400 hover:text-blue-600 transition"><i class="fa-regular fa-pen-to-square"></i></button>
                                        <button class="text-gray-400 hover:text-red-600 transition"><i class="fa-regular fa-trash-can"></i></button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <!-- Données par défaut identiques à la maquette Utilisateurs -->
                                <tr class="hover:bg-gray-50/80 transition">
                                    <td class="py-4 px-4 text-gray-400">0</td>
                                    <td class="py-4 px-4 font-bold text-gray-900">secretaire@esp221.sn</td>
                                    <td class="py-4 px-4 text-gray-400">••••••••</td>
                                    <td class="py-4 px-4"><span class="bg-gray-100 text-gray-600 px-2 py-1 rounded-md text-[11px]">Féminin</span></td>
                                    <td class="py-4 px-4 text-gray-600">+221 33 000 00 00</td>
                                    <td class="py-4 px-4 text-gray-500">Dakar, Plateau</td>
                                    <td class="py-4 px-4"><span class="bg-gray-100 text-gray-400 px-2.5 py-1 rounded-md text-[11px]">Aucun étudiant</span></td>
                                    <td class="py-4 px-4 text-right space-x-2">
                                        <button class="text-gray-400 hover:text-blue-600 transition"><i class="fa-regular fa-pen-to-square"></i></button>
                                    </td>
                                </tr>
                                <tr class="hover:bg-gray-50/80 transition">
                                    <td class="py-4 px-4 text-gray-400">1</td>
                                    <td class="py-4 px-4 font-bold text-gray-900">étudiant1@esp221.sn</td>
                                    <td class="py-4 px-4 text-gray-400">••••••••</td>
                                    <td class="py-4 px-4"><span class="bg-gray-100 text-gray-600 px-2 py-1 rounded-md text-[11px]">Masculin</span></td>
                                    <td class="py-4 px-4 text-gray-600">+221 77 123 45 67</td>
                                    <td class="py-4 px-4 text-gray-500">Dakar, Médina</td>
                                    <td class="py-4 px-4"><span class="bg-blue-50 text-blue-600 px-2.5 py-1 rounded-md font-medium text-[11px]">ESP221-2024-001</span></td>
                                    <td class="py-4 px-4 text-right space-x-2">
                                        <button class="text-gray-400 hover:text-blue-600 transition"><i class="fa-regular fa-pen-to-square"></i></button>
                                        <button class="text-gray-400 hover:text-red-600 transition"><i class="fa-regular fa-trash-can"></i></button>
                                    </td>
                                </tr>
                                <tr class="hover:bg-gray-50/80 transition">
                                    <td class="py-4 px-4 text-gray-400">2</td>
                                    <td class="py-4 px-4 font-bold text-gray-900">étudiant2@esp221.sn</td>
                                    <td class="py-4 px-4 text-gray-400">••••••••</td>
                                    <td class="py-4 px-4"><span class="bg-gray-100 text-gray-600 px-2 py-1 rounded-md text-[11px]">Féminin</span></td>
                                    <td class="py-4 px-4 text-gray-600">+221 78 234 56 78</td>
                                    <td class="py-4 px-4 text-gray-500">Dakar, Plateau</td>
                                    <td class="py-4 px-4"><span class="bg-blue-50 text-blue-600 px-2.5 py-1 rounded-md font-medium text-[11px]">ESP221-2024-002</span></td>
                                    <td class="py-4 px-4 text-right space-x-2">
                                        <button class="text-gray-400 hover:text-blue-600 transition"><i class="fa-regular fa-pen-to-square"></i></button>
                                        <button class="text-gray-400 hover:text-red-600 transition"><i class="fa-regular fa-trash-can"></i></button>
                                    </td>
                                </tr>
                                <tr class="hover:bg-gray-50/80 transition">
                                    <td class="py-4 px-4 text-gray-400">3</td>
                                    <td class="py-4 px-4 font-bold text-gray-900">étudiant3@esp221.sn</td>
                                    <td class="py-4 px-4 text-gray-400">••••••••</td>
                                    <td class="py-4 px-4"><span class="bg-gray-100 text-gray-600 px-2 py-1 rounded-md text-[11px]">Masculin</span></td>
                                    <td class="py-4 px-4 text-gray-600">+221 76 345 67 89</td>
                                    <td class="py-4 px-4 text-gray-500">Thiès, Centre</td>
                                    <td class="py-4 px-4"><span class="bg-blue-50 text-blue-600 px-2.5 py-1 rounded-md font-medium text-[11px]">ESP221-2024-003</span></td>
                                    <td class="py-4 px-4 text-right space-x-2">
                                        <button class="text-gray-400 hover:text-blue-600 transition"><i class="fa-regular fa-pen-to-square"></i></button>
                                        <button class="text-gray-400 hover:text-red-600 transition"><i class="fa-regular fa-trash-can"></i></button>
                                    </td>
                                </tr>
                                <tr class="hover:bg-gray-50/80 transition">
                                    <td class="py-4 px-4 text-gray-400">4</td>
                                    <td class="py-4 px-4 font-bold text-gray-900">étudiant4@esp221.sn</td>
                                    <td class="py-4 px-4 text-gray-400">••••••••</td>
                                    <td class="py-4 px-4"><span class="bg-gray-100 text-gray-600 px-2 py-1 rounded-md text-[11px]">Féminin</span></td>
                                    <td class="py-4 px-4 text-gray-600">+221 70 456 78 90</td>
                                    <td class="py-4 px-4 text-gray-500">Dakar, Parcelles</td>
                                    <td class="py-4 px-4"><span class="bg-blue-50 text-blue-600 px-2.5 py-1 rounded-md font-medium text-[11px]">ESP221-2024-004</span></td>
                                    <td class="py-4 px-4 text-right space-x-2">
                                        <button class="text-gray-400 hover:text-blue-600 transition"><i class="fa-regular fa-pen-to-square"></i></button>
                                        <button class="text-gray-400 hover:text-red-600 transition"><i class="fa-regular fa-trash-can"></i></button>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>

                <!-- Pied de tableau : Pagination -->
                <div class="flex justify-between items-center pt-4 border-t border-gray-100 text-xs text-gray-500">
                    <span>1 – 5 sur <?= ($tab === 'etudiants') ? '6' : '7' ?></span>
                    <div class="flex items-center space-x-3">
                        <span>Page 1 sur 2</span>
                        <div class="flex space-x-1">
                            <button class="w-7 h-7 flex items-center justify-center border border-gray-200 rounded text-gray-300 cursor-not-allowed"><i class="fa-solid fa-chevron-left text-[10px]"></i></button>
                            <button class="w-7 h-7 flex items-center justify-center border border-gray-200 rounded text-gray-700 hover:bg-gray-50 transition"><i class="fa-solid fa-chevron-right text-[10px]"></i></button>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </main>

    <!-- MODALE / POP-UP D'AJOUT -->
    <div id="modal-container" class="fixed inset-0 z-50 flex items-center justify-center hidden">
        <div id="modal-backdrop" class="fixed inset-0 bg-black/50 opacity-0 transition-opacity duration-300" onclick="toggleModal(false)"></div>

        <div id="modal-box" class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 p-6 z-10 opacity-0 scale-95 transition-all duration-300 space-y-4">
            
            <div class="flex justify-between items-center border-b border-gray-100 pb-3">
                <h3 class="font-bold text-base text-gray-800"><?= ($tab === 'etudiants') ? 'Ajouter un étudiant' : 'Ajouter un utilisateur' ?></h3>
                <button onclick="toggleModal(false)" class="text-gray-400 hover:text-gray-600 text-xl font-bold">&times;</button>
            </div>

            <form action="etudiants.php?tab=<?= $tab ?>" method="POST" class="space-y-3">
                <input type="hidden" name="action" value="ajouter_etudiant">
                
                <?php if ($tab === 'etudiants'): ?>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Matricule</label>
                        <input type="text" name="matricule" required placeholder="ex: ESP221-2024-007" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600">
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Nom</label>
                            <input type="text" name="nom" required placeholder="Nom" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Prénom</label>
                            <input type="text" name="prenom" required placeholder="Prénom" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Date de naissance</label>
                            <input type="date" name="date_naissance" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Sexe</label>
                            <select name="sexe" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600 bg-white">
                                <option value="Masculin">Masculin</option>
                                <option value="Féminin">Féminin</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Téléphone</label>
                        <input type="text" name="telephone" placeholder="+221 77 000 00 00" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600">
                    </div>
                <?php else: ?>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">E-mail</label>
                        <input type="email" name="email" required placeholder="etudiant@esp221.sn" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Mot de passe</label>
                        <input type="password" name="password" required class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600">
                    </div>
                <?php endif; ?>

                <div class="pt-2 flex justify-end space-x-2">
                    <button type="button" onclick="toggleModal(false)" class="px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition">
                        Annuler
                    </button>
                    <button type="submit" class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition shadow-sm">
                        Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Script Pop-up -->
    <script>
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
    </script>
</body>
</html>