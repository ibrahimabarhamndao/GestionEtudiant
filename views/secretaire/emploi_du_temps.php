<?php
$pageActuelle = 'emploi_du_temps';
$titrePage = 'Emploi du Temps';
$descriptionPage = 'Planning hebdomadaire des cours';
require_once '../../config/db.php';

$message = "";

// Traitement de l'ajout d'un créneau
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'ajouter_creneau') {
    $jour = trim($_POST['jour']);
    $heure = trim($_POST['heure']);
    $matiere = trim($_POST['matiere']);
    $salle = trim($_POST['salle']);
    $professeur = trim($_POST['professeur']);
    $couleur = trim($_POST['couleur'] ?? 'red');

    if (!empty($jour) && !empty($heure) && !empty($matiere)) {
        try {
            // Création automatique de la table si elle n'existe pas
            $pdo->exec("CREATE TABLE IF NOT EXISTS emplois_du_temps (
                id INT AUTO_INCREMENT PRIMARY KEY,
                jour VARCHAR(20) NOT NULL,
                heure VARCHAR(20) NOT NULL,
                matiere VARCHAR(100) NOT NULL,
                salle VARCHAR(50) NOT NULL,
                professeur VARCHAR(100) NOT NULL,
                couleur VARCHAR(20) DEFAULT 'red',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");

            $stmt = $pdo->prepare("INSERT INTO emplois_du_temps (jour, heure, matiere, salle, professeur, couleur) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$jour, $heure, $matiere, $salle, $professeur, $couleur]);
            header("Location: emploi_du_temps.php");
            exit();
        } catch (PDOException $e) {
            $message = "Erreur lors de l'enregistrement du créneau.";
        }
    } else {
        $message = "Veuillez remplir tous les champs obligatoires.";
    }
}

// Récupération des créneaux depuis la base de données
$creneaux = [];
try {
    $creneaux = $pdo->query("SELECT * FROM emplois_du_temps ORDER BY FIELD(jour, 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi'), heure ASC")->fetchAll();
} catch (PDOException $e) {
    $creneaux = [];
}

$joursSemaine = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Administration — Emploi du Temps</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 flex h-screen overflow-hidden">

    <!-- Sidebar Unifiée -->
    <?php include '../../includes/sidebar_secretaire.php'; ?>

    <!-- Contenu Principal -->
    <main class="flex-1 flex flex-col h-screen overflow-hidden">
        
        <!-- Header Supérieur Dynamique -->
        <?php include '../../includes/header.php'; ?>

        <!-- Corps de la page -->
        <div class="flex-1 p-8 overflow-y-auto space-y-6">
            
            <!-- Barre d'information semaine et Bouton Ajouter -->
            <div class="flex justify-between items-center">
                <p class="text-sm font-semibold text-gray-500">Semaine du 23 au 27 septembre 2024</p>
                <button onclick="toggleModal(true)" class="bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-red-700 transition flex items-center space-x-1 shadow-sm">
                    <span>Ajouter un créneau</span> <span class="font-bold">+</span>
                </button>
            </div>

            <?php if (!empty($message)): ?>
                <div class="p-4 bg-red-50 border-l-4 border-red-500 text-red-700 text-sm rounded">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <!-- Grille de l'Emploi du Temps (5 Jours) -->
            <div class="grid grid-cols-5 gap-4">
                <?php foreach ($joursSemaine as $jour): ?>
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm flex flex-col h-[calc(100vh-260px)] overflow-hidden">
                    <!-- En-tête du jour -->
                    <div class="p-4 border-b border-gray-100 bg-gray-50">
                        <h3 class="font-bold text-xs uppercase tracking-wider text-gray-700"><?= $jour ?></h3>
                    </div>
                    
                    <!-- Liste des cours de la journée -->
                    <div class="p-3 flex-1 overflow-y-auto space-y-3">
                        <?php 
                        $coursJour = array_filter($creneaux, function($c) use ($jour) {
                            return strcasecmp($c['jour'], $jour) === 0;
                        });
                        
                        if (count($coursJour) > 0):
                            foreach ($coursJour as $cours):
                                $bgClass = 'bg-red-50 border-red-100 text-red-900';
                                if ($cours['couleur'] == 'blue') $bgClass = 'bg-blue-50 border-blue-100 text-blue-900';
                                if ($cours['couleur'] == 'green') $bgClass = 'bg-emerald-50 border-emerald-100 text-emerald-900';
                                if ($cours['couleur'] == 'purple') $bgClass = 'bg-purple-50 border-purple-100 text-purple-900';
                                if ($cours['couleur'] == 'yellow') $bgClass = 'bg-amber-50 border-amber-100 text-amber-900';
                        ?>
                        <div class="<?= $bgClass ?> p-3.5 rounded-xl border shadow-xs space-y-1 transition hover:shadow-sm">
                            <span class="text-xs font-bold opacity-80"><?= htmlspecialchars($cours['heure']) ?></span>
                            <h4 class="font-bold text-sm"><?= htmlspecialchars($cours['matiere']) ?></h4>
                            <p class="text-xs opacity-75"><?= htmlspecialchars($cours['salle']) ?></p>
                            <p class="text-[11px] font-medium opacity-60 pt-1 border-t border-black/5"><?= htmlspecialchars($cours['professeur']) ?></p>
                        </div>
                        <?php 
                            endforeach;
                        else:
                        ?>
                            <!-- Données de démonstration par défaut si la base est vide pour correspondre à la maquette -->
                            <?php if ($jour == 'Lundi'): ?>
                                <div class="bg-red-50 border border-red-100 text-red-900 p-3.5 rounded-xl shadow-xs space-y-1">
                                    <span class="text-xs font-bold opacity-80">08h00</span>
                                    <h4 class="font-bold text-sm">Algorithmique</h4>
                                    <p class="text-xs opacity-75">Salle A101</p>
                                    <p class="text-[11px] font-medium opacity-60 pt-1 border-t border-black/5">Dr. Diallo</p>
                                </div>
                                <div class="bg-red-50 border border-red-100 text-red-900 p-3.5 rounded-xl shadow-xs space-y-1">
                                    <span class="text-xs font-bold opacity-80">10h00</span>
                                    <h4 class="font-bold text-sm">Base de données</h4>
                                    <p class="text-xs opacity-75">Salle B203</p>
                                    <p class="text-[11px] font-medium opacity-60 pt-1 border-t border-black/5">Pr. Seck</p>
                                </div>
                                <div class="bg-red-50 border border-red-100 text-red-900 p-3.5 rounded-xl shadow-xs space-y-1">
                                    <span class="text-xs font-bold opacity-80">14h00</span>
                                    <h4 class="font-bold text-sm">Réseaux informatiques</h4>
                                    <p class="text-xs opacity-75">Labo Info</p>
                                    <p class="text-[11px] font-medium opacity-60 pt-1 border-t border-black/5">Dr. Ndiaye</p>
                                </div>
                            <?php elseif ($jour == 'Mardi'): ?>
                                <div class="bg-blue-50 border border-blue-100 text-blue-900 p-3.5 rounded-xl shadow-xs space-y-1">
                                    <span class="text-xs font-bold opacity-80">08h00</span>
                                    <h4 class="font-bold text-sm">Mathématiques</h4>
                                    <p class="text-xs opacity-75">Salle A102</p>
                                    <p class="text-[11px] font-medium opacity-60 pt-1 border-t border-black/5">Pr. Ba</p>
                                </div>
                                <div class="bg-blue-50 border border-blue-100 text-blue-900 p-3.5 rounded-xl shadow-xs space-y-1">
                                    <span class="text-xs font-bold opacity-80">10h00</span>
                                    <h4 class="font-bold text-sm">Programmation Web</h4>
                                    <p class="text-xs opacity-75">Labo Info</p>
                                    <p class="text-[11px] font-medium opacity-60 pt-1 border-t border-black/5">Dr. Diallo</p>
                                </div>
                            <?php elseif ($jour == 'Mercredi'): ?>
                                <div class="bg-emerald-50 border border-emerald-100 text-emerald-900 p-3.5 rounded-xl shadow-xs space-y-1">
                                    <span class="text-xs font-bold opacity-80">08h00</span>
                                    <h4 class="font-bold text-sm">Systèmes d'exploitation</h4>
                                    <p class="text-xs opacity-75">Salle C301</p>
                                    <p class="text-[11px] font-medium opacity-60 pt-1 border-t border-black/5">Dr. Ndiaye</p>
                                </div>
                                <div class="bg-emerald-50 border border-emerald-100 text-emerald-900 p-3.5 rounded-xl shadow-xs space-y-1">
                                    <span class="text-xs font-bold opacity-80">14h00</span>
                                    <h4 class="font-bold text-sm">Anglais Technique</h4>
                                    <p class="text-xs opacity-75">Salle A103</p>
                                    <p class="text-[11px] font-medium opacity-60 pt-1 border-t border-black/5">Pr. Fall</p>
                                </div>
                            <?php elseif ($jour == 'Jeudi'): ?>
                                <div class="bg-purple-50 border border-purple-100 text-purple-900 p-3.5 rounded-xl shadow-xs space-y-1">
                                    <span class="text-xs font-bold opacity-80">08h00</span>
                                    <h4 class="font-bold text-sm">Sécurité Informatique</h4>
                                    <p class="text-xs opacity-75">Salle B201</p>
                                    <p class="text-[11px] font-medium opacity-60 pt-1 border-t border-black/5">Dr. Diallo</p>
                                </div>
                                <div class="bg-purple-50 border border-purple-100 text-purple-900 p-3.5 rounded-xl shadow-xs space-y-1">
                                    <span class="text-xs font-bold opacity-80">10h00</span>
                                    <h4 class="font-bold text-sm">Gestion de Projet</h4>
                                    <p class="text-xs opacity-75">Salle A101</p>
                                    <p class="text-[11px] font-medium opacity-60 pt-1 border-t border-black/5">Pr. Seck</p>
                                </div>
                                <div class="bg-purple-50 border border-purple-100 text-purple-900 p-3.5 rounded-xl shadow-xs space-y-1">
                                    <span class="text-xs font-bold opacity-80">14h00</span>
                                    <h4 class="font-bold text-sm">TP Réseaux</h4>
                                    <p class="text-xs opacity-75">Labo Réseaux</p>
                                    <p class="text-[11px] font-medium opacity-60 pt-1 border-t border-black/5">Dr. Ndiaye</p>
                                </div>
                            <?php elseif ($jour == 'Vendredi'): ?>
                                <div class="bg-amber-50 border border-amber-100 text-amber-900 p-3.5 rounded-xl shadow-xs space-y-1">
                                    <span class="text-xs font-bold opacity-80">08h00</span>
                                    <h4 class="font-bold text-sm">Architecture des SI</h4>
                                    <p class="text-xs opacity-75">Salle C302</p>
                                    <p class="text-[11px] font-medium opacity-60 pt-1 border-t border-black/5">Pr. Ba</p>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

        </div>
    </main>

    <!-- MODALE / POP-UP D'AJOUT DE CRÉNEAU -->
    <div id="modal-container" class="fixed inset-0 z-50 flex items-center justify-center hidden">
        <!-- Fond sombre semi-transparent -->
        <div id="modal-backdrop" class="fixed inset-0 bg-black/50 opacity-0 transition-opacity duration-300" onclick="toggleModal(false)"></div>

        <!-- Boîte Modale -->
        <div id="modal-box" class="relative bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 p-6 z-10 opacity-0 scale-95 transition-all duration-300">
            
            <div class="flex justify-between items-center border-b border-gray-100 pb-4 mb-4">
                <h3 class="font-bold text-lg text-gray-800">Ajouter un créneau</h3>
                <button onclick="toggleModal(false)" class="text-gray-400 hover:text-gray-600 text-2xl font-bold leading-none">&times;</button>
            </div>

            <form action="emploi_du_temps.php" method="POST" class="space-y-4">
                <input type="hidden" name="action" value="ajouter_creneau">
                
                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Jour</label>
                    <select name="jour" required class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm bg-white focus:outline-none focus:border-red-600">
                        <option value="Lundi">Lundi</option>
                        <option value="Mardi">Mardi</option>
                        <option value="Mercredi">Mercredi</option>
                        <option value="Jeudi">Jeudi</option>
                        <option value="Vendredi">Vendredi</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Heure / Plage horaire</label>
                    <input type="text" name="heure" required placeholder="ex: 08h00" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Matière</label>
                    <input type="text" name="matiere" required placeholder="ex: Algorithmique" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Salle</label>
                    <input type="text" name="salle" required placeholder="ex: Salle A101" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Professeur</label>
                    <input type="text" name="professeur" required placeholder="ex: Dr. Diallo" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Thème de couleur</label>
                    <select name="couleur" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm bg-white focus:outline-none focus:border-red-600">
                        <option value="red">Rouge (Rose pastel)</option>
                        <option value="blue">Bleu</option>
                        <option value="green">Vert (Menthe)</option>
                        <option value="purple">Violet</option>
                        <option value="yellow">Jaune / Ambre</option>
                    </select>
                </div>

                <div class="pt-4 flex justify-end space-x-3">
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

    <!-- Script de contrôle du Pop-up -->
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