<?php
$pageActuelle = 'emploi_du_temps'; 
$titrePage = 'Emploi du Temps'; 
$descriptionPage = 'Planification hebdomadaire des cours'; 
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

// Récupération des classes pour le filtre
$classes = [];
try {
    $classes = $pdo->query("SELECT * FROM classes ORDER BY nom ASC")->fetchAll();
} catch (PDOException $e) {
    $classes = [];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
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
        <div class="flex-1 p-8 overflow-y-auto space-y-6">
            
            <?php if (!empty($message)): ?>
                <div class="p-4 bg-red-50 border-l-4 border-red-500 text-red-700 text-sm rounded-lg shadow-sm">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <!-- Barre d'outils supérieure : Semaine / Classe / Bouton Ajouter -->
            <div class="flex justify-between items-center bg-white p-4 rounded-2xl border border-gray-200 shadow-sm">
                <span class="text-sm font-medium text-gray-600">Semaine du 23 au 27 septembre 2024</span>
                
                <div class="flex items-center space-x-4">
                    <div class="flex items-center space-x-2">
                        <span class="text-xs font-semibold text-gray-400 uppercase">Classe</span>
                        <select class="px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm font-medium text-gray-800 focus:outline-none focus:border-red-600">
                            <option>Informatique L1 Groupe A</option>
                            <option>Informatique L2 Groupe A</option>
                            <option>Gestion L1 Groupe A</option>
                        </select>
                    </div>

                    <button onclick="toggleModal(true)" class="bg-red-600 text-white px-4 py-2.5 rounded-lg text-sm font-medium hover:bg-red-700 transition flex items-center space-x-2 shadow-sm">
                        <i class="fa-solid fa-plus text-xs"></i>
                        <span>Ajouter un créneau</span>
                    </button>
                </div>
            </div>

            <!-- Grille de l'Emploi du Temps (5 Jours) -->
            <div class="grid grid-cols-5 gap-4 items-start">
                
                <!-- LUNDI (3 cours) -->
                <div class="bg-white rounded-2xl border border-gray-200 p-4 space-y-4 shadow-sm">
                    <div class="flex justify-between items-center border-b border-gray-100 pb-3">
                        <h3 class="font-bold text-xs uppercase tracking-wider text-gray-800">Lundi</h3>
                        <span class="text-[11px] text-gray-400 font-medium">3 cours</span>
                    </div>
                    <div class="space-y-3">
                        <!-- Cours 1 -->
                        <div class="p-3 bg-red-50/60 border border-red-100 rounded-xl space-y-1.5 relative group">
                            <div class="flex justify-between items-start text-xs font-bold text-red-900">
                                <span>08h00</span>
                                <div class="space-x-1 opacity-0 group-hover:opacity-100 transition">
                                    <button class="text-gray-400 hover:text-blue-600"><i class="fa-regular fa-pen-to-square text-[10px]"></i></button>
                                    <button class="text-gray-400 hover:text-red-600"><i class="fa-regular fa-trash-can text-[10px]"></i></button>
                                </div>
                            </div>
                            <h4 class="font-bold text-xs text-gray-900">Algorithmique</h4>
                            <p class="text-[11px] text-gray-500">Salle A101</p>
                            <p class="text-[11px] text-gray-600 font-medium">Dr Diallo</p>
                        </div>
                        <!-- Cours 2 -->
                        <div class="p-3 bg-red-50/60 border border-red-100 rounded-xl space-y-1.5 relative group">
                            <div class="flex justify-between items-start text-xs font-bold text-red-900">
                                <span>10h00</span>
                                <div class="space-x-1 opacity-0 group-hover:opacity-100 transition">
                                    <button class="text-gray-400 hover:text-blue-600"><i class="fa-regular fa-pen-to-square text-[10px]"></i></button>
                                    <button class="text-gray-400 hover:text-red-600"><i class="fa-regular fa-trash-can text-[10px]"></i></button>
                                </div>
                            </div>
                            <h4 class="font-bold text-xs text-gray-900">Base de données</h4>
                            <p class="text-[11px] text-gray-500">Salle B203</p>
                            <p class="text-[11px] text-gray-600 font-medium">Pr. Seck</p>
                        </div>
                        <!-- Cours 3 -->
                        <div class="p-3 bg-red-50/60 border border-red-100 rounded-xl space-y-1.5 relative group">
                            <div class="flex justify-between items-start text-xs font-bold text-red-900">
                                <span>14h00</span>
                                <div class="space-x-1 opacity-0 group-hover:opacity-100 transition">
                                    <button class="text-gray-400 hover:text-blue-600"><i class="fa-regular fa-pen-to-square text-[10px]"></i></button>
                                    <button class="text-gray-400 hover:text-red-600"><i class="fa-regular fa-trash-can text-[10px]"></i></button>
                                </div>
                            </div>
                            <h4 class="font-bold text-xs text-gray-900">Réseaux informatiques</h4>
                            <p class="text-[11px] text-gray-500">Informations sur le laboratoire</p>
                            <p class="text-[11px] text-gray-600 font-medium">Dr Ndiaye</p>
                        </div>
                    </div>
                </div>

                <!-- MARDI (2 cours) -->
                <div class="bg-white rounded-2xl border border-gray-200 p-4 space-y-4 shadow-sm">
                    <div class="flex justify-between items-center border-b border-gray-100 pb-3">
                        <h3 class="font-bold text-xs uppercase tracking-wider text-gray-800">Mardi</h3>
                        <span class="text-[11px] text-gray-400 font-medium">2 cours</span>
                    </div>
                    <div class="space-y-3">
                        <!-- Cours 1 -->
                        <div class="p-3 bg-blue-50/60 border border-blue-100 rounded-xl space-y-1.5 relative group">
                            <div class="flex justify-between items-start text-xs font-bold text-blue-900">
                                <span>08h00</span>
                                <div class="space-x-1 opacity-0 group-hover:opacity-100 transition">
                                    <button class="text-gray-400 hover:text-blue-600"><i class="fa-regular fa-pen-to-square text-[10px]"></i></button>
                                    <button class="text-gray-400 hover:text-red-600"><i class="fa-regular fa-trash-can text-[10px]"></i></button>
                                </div>
                            </div>
                            <h4 class="font-bold text-xs text-gray-900">Mathématiques</h4>
                            <p class="text-[11px] text-gray-500">Salle A102</p>
                            <p class="text-[11px] text-gray-600 font-medium">Pr. Ba</p>
                        </div>
                        <!-- Cours 2 -->
                        <div class="p-3 bg-blue-50/60 border border-blue-100 rounded-xl space-y-1.5 relative group">
                            <div class="flex justify-between items-start text-xs font-bold text-blue-900">
                                <span>10h00</span>
                                <div class="space-x-1 opacity-0 group-hover:opacity-100 transition">
                                    <button class="text-gray-400 hover:text-blue-600"><i class="fa-regular fa-pen-to-square text-[10px]"></i></button>
                                    <button class="text-gray-400 hover:text-red-600"><i class="fa-regular fa-trash-can text-[10px]"></i></button>
                                </div>
                            </div>
                            <h4 class="font-bold text-xs text-gray-900">Programmation Web</h4>
                            <p class="text-[11px] text-gray-500">Informations sur le laboratoire</p>
                            <p class="text-[11px] text-gray-600 font-medium">Dr Diallo</p>
                        </div>
                    </div>
                </div>

                <!-- MERCREDI (2 cours) -->
                <div class="bg-white rounded-2xl border border-gray-200 p-4 space-y-4 shadow-sm">
                    <div class="flex justify-between items-center border-b border-gray-100 pb-3">
                        <h3 class="font-bold text-xs uppercase tracking-wider text-gray-800">Mercredi</h3>
                        <span class="text-[11px] text-gray-400 font-medium">2 cours</span>
                    </div>
                    <div class="space-y-3">
                        <!-- Cours 1 -->
                        <div class="p-3 bg-emerald-50/60 border border-emerald-100 rounded-xl space-y-1.5 relative group">
                            <div class="flex justify-between items-start text-xs font-bold text-emerald-900">
                                <span>08h00</span>
                                <div class="space-x-1 opacity-0 group-hover:opacity-100 transition">
                                    <button class="text-gray-400 hover:text-blue-600"><i class="fa-regular fa-pen-to-square text-[10px]"></i></button>
                                    <button class="text-gray-400 hover:text-red-600"><i class="fa-regular fa-trash-can text-[10px]"></i></button>
                                </div>
                            </div>
                            <h4 class="font-bold text-xs text-gray-900">Systèmes d'exploitation</h4>
                            <p class="text-[11px] text-gray-500">Salle C301</p>
                            <p class="text-[11px] text-gray-600 font-medium">Dr Ndiaye</p>
                        </div>
                        <!-- Cours 2 -->
                        <div class="p-3 bg-emerald-50/60 border border-emerald-100 rounded-xl space-y-1.5 relative group">
                            <div class="flex justify-between items-start text-xs font-bold text-emerald-900">
                                <span>14h00</span>
                                <div class="space-x-1 opacity-0 group-hover:opacity-100 transition">
                                    <button class="text-gray-400 hover:text-blue-600"><i class="fa-regular fa-pen-to-square text-[10px]"></i></button>
                                    <button class="text-gray-400 hover:text-red-600"><i class="fa-regular fa-trash-can text-[10px]"></i></button>
                                </div>
                            </div>
                            <h4 class="font-bold text-xs text-gray-900">Technique anglaise</h4>
                            <p class="text-[11px] text-gray-500">Salle A103</p>
                            <p class="text-[11px] text-gray-600 font-medium">Pr. Automne</p>
                        </div>
                    </div>
                </div>

                <!-- JEUDI (3 cours) -->
                <div class="bg-white rounded-2xl border border-gray-200 p-4 space-y-4 shadow-sm">
                    <div class="flex justify-between items-center border-b border-gray-100 pb-3">
                        <h3 class="font-bold text-xs uppercase tracking-wider text-gray-800">Jeudi</h3>
                        <span class="text-[11px] text-gray-400 font-medium">3 cours</span>
                    </div>
                    <div class="space-y-3">
                        <!-- Cours 1 -->
                        <div class="p-3 bg-purple-50/60 border border-purple-100 rounded-xl space-y-1.5 relative group">
                            <div class="flex justify-between items-start text-xs font-bold text-purple-900">
                                <span>08h00</span>
                                <div class="space-x-1 opacity-0 group-hover:opacity-100 transition">
                                    <button class="text-gray-400 hover:text-blue-600"><i class="fa-regular fa-pen-to-square text-[10px]"></i></button>
                                    <button class="text-gray-400 hover:text-red-600"><i class="fa-regular fa-trash-can text-[10px]"></i></button>
                                </div>
                            </div>
                            <h4 class="font-bold text-xs text-gray-900">Sécurité informatique</h4>
                            <p class="text-[11px] text-gray-500">Salle B201</p>
                            <p class="text-[11px] text-gray-600 font-medium">Dr Diallo</p>
                        </div>
                        <!-- Cours 2 -->
                        <div class="p-3 bg-purple-50/60 border border-purple-100 rounded-xl space-y-1.5 relative group">
                            <div class="flex justify-between items-start text-xs font-bold text-purple-900">
                                <span>10h00</span>
                                <div class="space-x-1 opacity-0 group-hover:opacity-100 transition">
                                    <button class="text-gray-400 hover:text-blue-600"><i class="fa-regular fa-pen-to-square text-[10px]"></i></button>
                                    <button class="text-gray-400 hover:text-red-600"><i class="fa-regular fa-trash-can text-[10px]"></i></button>
                                </div>
                            </div>
                            <h4 class="font-bold text-xs text-gray-900">Gestion de projet</h4>
                            <p class="text-[11px] text-gray-500">Salle A101</p>
                            <p class="text-[11px] text-gray-600 font-medium">Pr. Seck</p>
                        </div>
                        <!-- Cours 3 -->
                        <div class="p-3 bg-purple-50/60 border border-purple-100 rounded-xl space-y-1.5 relative group">
                            <div class="flex justify-between items-start text-xs font-bold text-purple-900">
                                <span>14h00</span>
                                <div class="space-x-1 opacity-0 group-hover:opacity-100 transition">
                                    <button class="text-gray-400 hover:text-blue-600"><i class="fa-regular fa-pen-to-square text-[10px]"></i></button>
                                    <button class="text-gray-400 hover:text-red-600"><i class="fa-regular fa-trash-can text-[10px]"></i></button>
                                </div>
                            </div>
                            <h4 class="font-bold text-xs text-gray-900">TP Réseaux</h4>
                            <p class="text-[11px] text-gray-500">Labo Réseaux</p>
                            <p class="text-[11px] text-gray-600 font-medium">Dr Ndiaye</p>
                        </div>
                    </div>
                </div>

                <!-- VENDREDI (1 cours) -->
                <div class="bg-white rounded-2xl border border-gray-200 p-4 space-y-4 shadow-sm">
                    <div class="flex justify-between items-center border-b border-gray-100 pb-3">
                        <h3 class="font-bold text-xs uppercase tracking-wider text-gray-800">Vendredi</h3>
                        <span class="text-[11px] text-gray-400 font-medium">1 cours</span>
                    </div>
                    <div class="space-y-3">
                        <!-- Cours 1 -->
                        <div class="p-3 bg-amber-50/60 border border-amber-100 rounded-xl space-y-1.5 relative group">
                            <div class="flex justify-between items-start text-xs font-bold text-amber-900">
                                <span>08h00</span>
                                <div class="space-x-1 opacity-0 group-hover:opacity-100 transition">
                                    <button class="text-gray-400 hover:text-blue-600"><i class="fa-regular fa-pen-to-square text-[10px]"></i></button>
                                    <button class="text-gray-400 hover:text-red-600"><i class="fa-regular fa-trash-can text-[10px]"></i></button>
                                </div>
                            </div>
                            <h4 class="font-bold text-xs text-gray-900">Architecture des SI</h4>
                            <p class="text-[11px] text-gray-500">Salle C302</p>
                            <p class="text-[11px] text-gray-600 font-medium">Pr. Ba</p>
                        </div>
                    </div>
                </div>

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
                    <select name="classe_id" required class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm bg-white focus:outline-none focus:border-red-600">
                        <option value="">Sélectionner une classe</option>
                        <?php foreach ($classes as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nom']) ?></option>
                        <?php endforeach; ?>
                        <!-- Option par défaut -->
                        <option value="1">Informatique L1 Groupe A</option>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-2">
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
                        <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Heure de début</label>
                        <input type="text" name="heure_debut" required placeholder="ex: 08h00" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Matière</label>
                    <input type="text" name="matiere" required placeholder="ex: Algorithmique" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600">
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Salle / Lieu</label>
                        <input type="text" name="salle" required placeholder="ex: Salle A101" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Enseignant</label>
                        <input type="text" name="enseignant" required placeholder="ex: Dr Diallo" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600">
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