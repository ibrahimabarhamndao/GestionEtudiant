<?php
$pageActuelle = 'emploi_du_temps'; 
$titrePage = 'Emploi du Temps'; 
$descriptionPage = 'Planning hebdomadaire des cours'; 
require_once '../../config/db.php';

$message = "";

// Traitement de l'ajout d'un créneau d'emploi du temps
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'ajouter_creneau') {
    $classe_id = trim($_POST['classe_id']);
    $jour = trim($_POST['jour']);
    $heure_debut = trim($_POST['heure_debut']);
    $matiere = trim($_POST['matiere']);
    $salle = trim($_POST['salle']);
    $enseignant = trim($_POST['enseignant']);
    $details = trim($_POST['details']);

    if (!empty($classe_id) && !empty($jour) && !empty($heure_debut) && !empty($matiere)) {
        try {
            // Création automatique de la table si elle n'existe pas
            $pdo->exec("CREATE TABLE IF NOT EXISTS emploi_du_temps (
                id INT AUTO_INCREMENT PRIMARY KEY,
                classe_id INT NOT NULL,
                jour VARCHAR(20) NOT NULL,
                heure_debut VARCHAR(10) NOT NULL,
                matiere VARCHAR(150) NOT NULL,
                salle VARCHAR(50) NOT NULL,
                enseignant VARCHAR(150) NOT NULL,
                details TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");

            $stmt = $pdo->prepare("INSERT INTO emploi_du_temps (classe_id, jour, heure_debut, matiere, salle, enseignant, details) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$classe_id, $jour, $heure_debut, $matiere, $salle, $enseignant, $details]);
            header("Location: emploi_du_temps.php");
            exit();
        } catch (PDOException $e) {
            $message = "Erreur lors de l'enregistrement du créneau.";
        }
    } else {
        $message = "Veuillez remplir les champs obligatoires.";
    }
}

// Récupération des classes pour le formulaire
$classes = [];
try {
    $classes = $pdo->query("SELECT * FROM classes ORDER BY nom ASC")->fetchAll();
} catch (PDOException $e) {
    $classes = [];
}

// Couleur de chaque jour (fond, bordure, accent gauche)
$couleurs = [
    'Lundi'    => 'bg-red-50 border-red-100 border-l-red-400',
    'Mardi'    => 'bg-blue-50 border-blue-100 border-l-blue-400',
    'Mercredi' => 'bg-emerald-50 border-emerald-100 border-l-emerald-400',
    'Jeudi'    => 'bg-purple-50 border-purple-100 border-l-purple-400',
    'Vendredi' => 'bg-amber-50 border-amber-100 border-l-amber-400',
];

// Créneaux enregistrés, regroupés par jour
$planning = array_fill_keys(array_keys($couleurs), []);
$creneaux = [];
try {
    $creneaux = $pdo->query("SELECT * FROM emploi_du_temps ORDER BY heure_debut ASC")->fetchAll();
} catch (PDOException $e) {
    $creneaux = [];
}
foreach ($creneaux as $c) {
    if (isset($planning[$c['jour']])) {
        $planning[$c['jour']][] = [$c['heure_debut'], $c['matiere'], $c['salle'], $c['enseignant']];
    }
}

// Données de démonstration conformes à la maquette (si aucun créneau)
if (count($creneaux) === 0) {
    $planning = [
        'Lundi' => [
            ['08h00', 'Algorithmique',         'Salle A101', 'Dr. Diallo'],
            ['10h00', 'Base de données',       'Salle B203', 'Pr. Seck'],
            ['14h00', 'Réseaux informatiques', 'Labo Info',  'Dr. Ndiaye'],
        ],
        'Mardi' => [
            ['08h00', 'Mathématiques',      'Salle A102', 'Pr. Ba'],
            ['10h00', 'Programmation Web',  'Labo Info',  'Dr. Diallo'],
        ],
        'Mercredi' => [
            ['08h00', 'Systèmes d\'exploitation', 'Salle C301', 'Dr. Ndiaye'],
            ['14h00', 'Anglais Technique',        'Salle A103', 'Pr. Fall'],
        ],
        'Jeudi' => [
            ['08h00', 'Sécurité Informatique', 'Salle B201',    'Dr. Diallo'],
            ['10h00', 'Gestion de Projet',     'Salle A101',    'Pr. Seck'],
            ['14h00', 'TP Réseaux',            'Labo Réseaux',  'Dr. Ndiaye'],
        ],
        'Vendredi' => [
            ['08h00', 'Architecture des SI', 'Salle C302', 'Pr. Ba'],
        ],
    ];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Administration — Emploi du Temps</title>
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
        <div class="flex-1 p-8 overflow-y-auto space-y-8">
            
            <?php if (!empty($message)): ?>
                <div class="p-4 bg-red-50 border-l-4 border-red-500 text-red-700 text-sm rounded-lg shadow-sm">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <!-- Barre d'outils : Semaine (gauche) + Bouton Ajouter (droite) -->
            <div class="flex items-center justify-between">
                <span class="text-xs text-slate-500">Semaine du 23 au 27 septembre 2024</span>
                <button onclick="toggleModal(true)" class="bg-red-600 text-white px-5 py-2.5 rounded-lg text-sm font-semibold hover:bg-red-700 transition flex items-center space-x-2 shadow-sm">
                    <span>Ajouter un créneau</span>
                    <i class="fa-solid fa-plus text-xs"></i>
                </button>
            </div>

            <!-- Grille de l'emploi du temps (5 jours) -->
            <div class="grid grid-cols-5 gap-4 items-start">
                <?php foreach ($planning as $jour => $cours): ?>
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="px-4 py-3 border-b border-gray-200">
                        <h3 class="font-bold text-[11px] uppercase tracking-wider text-gray-800"><?= htmlspecialchars($jour) ?></h3>
                    </div>
                    <div class="p-3 space-y-3">
                        <?php foreach ($cours as [$heure, $matiere, $salle, $enseignant]): ?>
                        <div class="p-3 border border-l-2 rounded-lg space-y-1 group <?= $couleurs[$jour] ?>">
                            <div class="flex justify-between items-start text-xs font-bold text-slate-500">
                                <span><?= htmlspecialchars($heure) ?></span>
                                <div class="space-x-1 opacity-0 group-hover:opacity-100 transition">
                                    <button class="text-gray-400 hover:text-blue-600" title="Modifier"><i class="fa-regular fa-pen-to-square text-[10px]"></i></button>
                                    <button class="text-gray-400 hover:text-red-600" title="Supprimer"><i class="fa-regular fa-trash-can text-[10px]"></i></button>
                                </div>
                            </div>
                            <h4 class="font-bold text-xs text-gray-900"><?= htmlspecialchars($matiere) ?></h4>
                            <p class="text-[11px] text-slate-500"><?= htmlspecialchars($salle) ?></p>
                            <p class="text-[11px] text-slate-500"><?= htmlspecialchars($enseignant) ?></p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>

    <!-- MODALE / POP-UP D'AJOUT DE CRÉNEAU -->
    <div id="modal-container" class="fixed inset-0 z-50 flex items-center justify-center hidden">
        <div id="modal-backdrop" class="fixed inset-0 bg-black/50 opacity-0 transition-opacity duration-300" onclick="toggleModal(false)"></div>

        <div id="modal-box" class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 p-6 z-10 opacity-0 scale-95 transition-all duration-300 space-y-4">
            
            <div class="flex justify-between items-center border-b border-gray-100 pb-3">
                <h3 class="font-bold text-base text-gray-800">Ajouter un créneau horaire</h3>
                <button onclick="toggleModal(false)" class="text-gray-400 hover:text-gray-600 text-xl font-bold">&times;</button>
            </div>

            <form action="emploi_du_temps.php" method="POST" class="space-y-3">
                <input type="hidden" name="action" value="ajouter_creneau">
                
                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Classe</label>
                    <select name="classe_id"  class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm bg-white focus:outline-none focus:border-red-600">
                        <option value="">Sélectionner une classe</option>
                        <?php foreach ($classes as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nom']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Jour</label>
                        <select name="jour"  class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm bg-white focus:outline-none focus:border-red-600">
                            <option value="Lundi">Lundi</option>
                            <option value="Mardi">Mardi</option>
                            <option value="Mercredi">Mercredi</option>
                            <option value="Jeudi">Jeudi</option>
                            <option value="Vendredi">Vendredi</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Heure de début</label>
                        <input type="text" name="heure_debut"  placeholder="ex: 08h00" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Matière</label>
                    <input type="text" name="matiere"  placeholder="ex: Algorithmique" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600">
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Salle / Lieu</label>
                        <input type="text" name="salle"  placeholder="ex: Salle A101" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Enseignant</label>
                        <input type="text" name="enseignant"  placeholder="ex: Dr. Diallo" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Détails / Remarques</label>
                    <textarea name="details" rows="2" placeholder="ex: Informations sur le laboratoire..." class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600"></textarea>
                </div>

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