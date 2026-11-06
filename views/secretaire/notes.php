<?php
$pageActuelle = 'notes'; 
$titrePage = 'Notes & Évaluations'; 
$descriptionPage = 'Suivi et consultation des notes des étudiants'; 
require_once '../../config/db.php';

$message = "";

// Traitement de l'ajout d'une note
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'ajouter_note') {
    $etudiant_id = trim($_POST['etudiant_id']);
    $matiere = trim($_POST['matiere']);
    $type = trim($_POST['type']);
    $valeur = trim($_POST['valeur']);

    if (!empty($etudiant_id) && !empty($matiere) && !empty($type) && $valeur !== '') {
        try {
            // Vérification ou création de la table notes si elle n'existe pas
            $pdo->exec("CREATE TABLE IF NOT EXISTS notes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                etudiant_id INT NOT NULL,
                matiere VARCHAR(100) NOT NULL,
                type VARCHAR(50) NOT NULL,
                valeur DECIMAL(4,2) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");

            $stmt = $pdo->prepare("INSERT INTO notes (etudiant_id, matiere, type, valeur) VALUES (?, ?, ?, ?)");
            $stmt->execute([$etudiant_id, $matiere, $type, $valeur]);
            header("Location: notes.php");
            exit();
        } catch (PDOException $e) {
            $message = "Erreur lors de l'enregistrement de la note.";
        }
    } else {
        $message = "Veuillez remplir tous les champs obligatoires.";
    }
}

// Récupération des notes et des étudiants
$notes = [];
$etudiants = [];
try {
    $queryNotes = "SELECT notes.*, etudiants.nom, etudiants.prenom, etudiants.matricule 
                   FROM notes 
                   LEFT JOIN etudiants ON notes.etudiant_id = etudiants.id 
                   ORDER BY notes.id DESC";
    $notes = $pdo->query($queryNotes)->fetchAll();
} catch (PDOException $e) {
    $notes = [];
}

try {
    $etudiants = $pdo->query("SELECT * FROM etudiants ORDER BY nom ASC")->fetchAll();
} catch (PDOException $e) {
    $etudiants = [];
}

// Données de démonstration (si la table est vide)
if (count($notes) === 0) {
    $notes = [
        ['prenom' => 'Mamadou', 'nom' => 'Diallo', 'matricule' => 'ESP221-2024-001', 'matiere' => 'Développement Web PHP', 'type' => 'CC',     'valeur' => 15.5],
        ['prenom' => 'Aminata', 'nom' => 'Sall',   'matricule' => 'ESP221-2024-002', 'matiere' => 'Base de données SQL',    'type' => 'Examen', 'valeur' => 17],
    ];
}
$total = count($notes);

// Note affichée sans zéros inutiles (15.50 -> 15.5)
function fmtNote($v) {
    return rtrim(rtrim(number_format((float)$v, 2, '.', ''), '0'), '.');
}

// Regroupement des notes par matière (vue « Par Matière »)
$parMatiere = [];
foreach ($notes as $n) {
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

            <!-- Barre d'outils : Recherche (gauche) + Bouton Saisir (droite) -->
            <div class="flex items-center justify-between">
                <div class="relative w-80">
                    <input type="text" placeholder="Rechercher une note ou un étudiant..." class="w-full pl-4 pr-10 py-2.5 bg-white border border-gray-200 rounded-lg text-sm text-gray-700 placeholder-gray-400 focus:outline-none focus:border-red-600 transition">
                    <span class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-gray-800 pointer-events-none">
                        <i class="fa-solid fa-magnifying-glass text-sm"></i>
                    </span>
                </div>
                <button onclick="toggleDrawer(true)" class="bg-red-600 text-white px-5 py-2.5 rounded-lg text-sm font-semibold hover:bg-red-700 transition flex items-center space-x-2 shadow-sm">
                    <span>Saisir une note</span>
                    <i class="fa-solid fa-plus text-xs"></i>
                </button>
            </div>

            <!-- Carte conteneur principale -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                
                <!-- Sélecteur de vue (Tableau / Par Matière) -->
                <div class="flex justify-end px-4 py-3 border-b border-gray-100">
                    <div class="bg-gray-100 rounded-lg p-1 flex text-xs font-medium">
                        <button id="btn-table" type="button" onclick="setView('table')" class="px-3 py-1.5 rounded-md flex items-center space-x-1.5 transition bg-white text-red-600 shadow-sm">
                            <i class="fa-solid fa-table"></i>
                            <span>Tableau</span>
                        </button>
                        <button id="btn-cards" type="button" onclick="setView('cards')" class="px-3 py-1.5 rounded-md flex items-center space-x-1.5 transition text-gray-500 hover:text-gray-800">
                            <i class="fa-solid fa-book"></i>
                            <span>Par Matière</span>
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
                            <?php foreach ($notes as $n): ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="py-4 px-6 text-left">
                                    <span class="block font-bold text-gray-900"><?= htmlspecialchars(trim(($n['prenom'] ?? '') . ' ' . ($n['nom'] ?? 'Étudiant inconnu'))) ?></span>
                                    <span class="block text-xs text-slate-500"><?= htmlspecialchars($n['matricule'] ?? '') ?></span>
                                </td>
                                <td class="py-4 px-4 text-xs text-slate-600"><?= htmlspecialchars($n['matiere']) ?></td>
                                <td class="py-4 px-4">
                                    <span class="inline-block bg-gray-100 text-gray-600 px-2.5 py-1 rounded-full text-[11px] font-medium"><?= htmlspecialchars($n['type']) ?></span>
                                </td>
                                <td class="py-4 px-4 font-bold text-gray-900"><?= htmlspecialchars(fmtNote($n['valeur'])) ?>/20</td>
                                <td class="py-4 px-6 space-x-3 whitespace-nowrap">
                                    <button class="text-slate-500 hover:text-blue-600 transition" title="Modifier"><i class="fa-solid fa-pen"></i></button>
                                    <button class="text-slate-500 hover:text-red-600 transition" title="Supprimer"><i class="fa-regular fa-trash-can"></i></button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- VUE PAR MATIÈRE -->
                <div id="view-cards" class="hidden p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 items-start">
                        <?php foreach ($parMatiere as $matiere => $liste):
                            $somme = 0;
                            foreach ($liste as $x) { $somme += (float)$x['valeur']; }
                            $moyenne = $somme / count($liste);
                        ?>
                        <div class="bg-white border border-gray-100 rounded-xl shadow-md p-4">
                            <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                                <h4 class="text-sm font-bold text-gray-900 pr-3"><?= htmlspecialchars($matiere) ?></h4>
                                <span class="inline-block bg-gray-100 text-gray-600 px-2.5 py-1 rounded-full text-[11px] font-medium shrink-0"><?= count($liste) ?> note<?= count($liste) > 1 ? 's' : '' ?></span>
                            </div>
                            <div class="pt-3 space-y-3">
                                <div>
                                    <p class="text-xs text-slate-500">Moyenne</p>
                                    <p class="text-sm font-bold text-gray-900"><?= htmlspecialchars(fmtNote($moyenne)) ?>/20</p>
                                </div>
                                <div class="divide-y divide-gray-100">
                                    <?php foreach ($liste as $x): ?>
                                    <div class="flex items-center justify-between py-2">
                                        <div>
                                            <p class="text-xs font-bold text-gray-900"><?= htmlspecialchars(trim(($x['prenom'] ?? '') . ' ' . ($x['nom'] ?? 'Étudiant inconnu'))) ?></p>
                                            <p class="text-[11px] text-slate-500"><?= htmlspecialchars($x['matricule'] ?? '') ?></p>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <span class="inline-block bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full text-[11px] font-medium"><?= htmlspecialchars($x['type']) ?></span>
                                            <span class="text-xs font-bold text-gray-900"><?= htmlspecialchars(fmtNote($x['valeur'])) ?>/20</span>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

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

    <!-- DRAWER LATÉRAL DE SAISIE DE NOTE -->
    <div id="drawer-overlay" class="fixed inset-0 bg-black/50 z-50 hidden transition-opacity opacity-0" onclick="toggleDrawer(false)"></div>
    
    <div id="drawer" class="fixed top-0 right-0 h-full w-[420px] bg-white shadow-2xl z-50 transform translate-x-full transition-transform duration-300 ease-in-out flex flex-col justify-between overflow-y-auto">
        <div>
            <div class="p-6 border-b border-gray-100 flex justify-between items-center sticky top-0 bg-white z-10">
                <h3 class="font-bold text-base text-gray-800">Saisir une note</h3>
                <button onclick="toggleDrawer(false)" class="text-gray-400 hover:text-gray-600 text-xl font-bold">&times;</button>
            </div>

            <form action="notes.php" method="POST" class="p-6 space-y-4">
                <input type="hidden" name="action" value="ajouter_note">
                
                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Étudiant</label>
                    <select name="etudiant_id" required class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm bg-white focus:outline-none focus:border-red-600">
                        <option value="">Sélectionner un étudiant</option>
                        <?php foreach ($etudiants as $et): ?>
                            <option value="<?= $et['id'] ?>"><?= htmlspecialchars($et['prenom'] . ' ' . $et['nom']) ?> (<?= htmlspecialchars($et['matricule']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Matière / Module</label>
                    <input type="text" name="matiere" required placeholder="ex: Développement Web PHP" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Type d'évaluation</label>
                    <select name="type" required class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm bg-white focus:outline-none focus:border-red-600">
                        <option value="CC">Contrôle Continu (CC)</option>
                        <option value="Examen">Examen</option>
                        <option value="Projet">Projet</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Note (/20)</label>
                    <input type="number" step="0.25" min="0" max="20" name="valeur" required placeholder="ex: 15.5" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600">
                </div>

                <div class="pt-4 flex items-center justify-end space-x-3 border-t border-gray-100">
                    <button type="button" onclick="toggleDrawer(false)" class="px-4 py-2.5 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition">
                        Annuler
                    </button>
                    <button type="submit" class="px-4 py-2.5 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition shadow-sm">
                        Enregistrer
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

        // Bascule entre la vue Tableau et la vue Par Matière
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