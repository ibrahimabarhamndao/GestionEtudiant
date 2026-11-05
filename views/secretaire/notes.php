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

    $etudiants = $pdo->query("SELECT * FROM etudiants ORDER BY nom ASC")->fetchAll();
} catch (PDOException $e) {
    $notes = [];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
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

            <!-- Barre d'outils supérieure : Recherche + Vues + Bouton Saisir -->
            <div class="flex justify-between items-center">
                <div class="relative w-80">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400">
                        <i class="fa-solid fa-magnifying-glass text-xs"></i>
                    </span>
                    <input type="text" placeholder="Rechercher une note ou un étudiant..." class="w-full pl-10 pr-4 py-2.5 bg-white border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600 transition shadow-2xs">
                </div>

                <div class="flex items-center space-x-3">
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-1 flex text-xs font-medium">
                        <button class="px-3 py-1.5 bg-red-50 text-red-600 rounded-md shadow-2xs flex items-center space-x-1.5">
                            <i class="fa-solid fa-table"></i>
                            <span>Tableau</span>
                        </button>
                        <button class="px-3 py-1.5 text-gray-500 hover:text-gray-800 rounded-md flex items-center space-x-1.5 transition">
                            <i class="fa-solid fa-book"></i>
                            <span>Par Matière</span>
                        </button>
                    </div>

                    <button onclick="toggleDrawer(true)" class="bg-red-600 text-white px-4 py-2.5 rounded-lg text-sm font-medium hover:bg-red-700 transition flex items-center space-x-2 shadow-sm">
                        <i class="fa-solid fa-plus text-xs"></i>
                        <span>Saisir une note</span>
                    </button>
                </div>
            </div>

            <!-- Carte Conteneur Principale du Tableau -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 space-y-6">
                
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="text-gray-400 text-[11px] uppercase tracking-wider border-b border-gray-100">
                                <th class="py-3 px-4 font-semibold">Étudiant</th>
                                <th class="py-3 px-4 font-semibold">Matière / Module</th>
                                <th class="py-3 px-4 font-semibold">Type (CC / Examen)</th>
                                <th class="py-3 px-4 font-semibold">Note / 20</th>
                                <th class="py-3 px-4 font-semibold text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-sm text-gray-600">
                            <?php if (count($notes) > 0): ?>
                                <?php foreach ($notes as $n): ?>
                                <tr class="hover:bg-gray-50/80 transition">
                                    <td class="py-4 px-4 font-bold text-gray-900">
                                        <?= htmlspecialchars(($n['prenom'] ?? '') . ' ' . ($n['nom'] ?? 'Étudiant inconnu')) ?>
                                        <span class="block text-xs text-gray-400 font-normal"><?= htmlspecialchars($n['matricule'] ?? '') ?></span>
                                    </td>
                                    <td class="py-4 px-4 text-gray-600"><?= htmlspecialchars($n['matiere']) ?></td>
                                    <td class="py-4 px-4">
                                        <span class="bg-gray-100 text-gray-700 px-2.5 py-1 rounded-md text-xs font-medium"><?= htmlspecialchars($n['type']) ?></span>
                                    </td>
                                    <td class="py-4 px-4 font-bold text-gray-900"><?= htmlspecialchars($n['valeur']) ?>/20</td>
                                    <td class="py-4 px-4 text-right space-x-3">
                                        <button class="text-gray-400 hover:text-blue-600 transition" title="Modifier">
                                            <i class="fa-regular fa-pen-to-square"></i>
                                        </button>
                                        <button class="text-gray-400 hover:text-red-600 transition" title="Supprimer">
                                            <i class="fa-regular fa-trash-can"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <!-- Exemples de démonstration pour garder l'interface riche -->
                                <tr class="hover:bg-gray-50/80 transition">
                                    <td class="py-4 px-4 font-bold text-gray-900">
                                        Mamadou Diallo
                                        <span class="block text-xs text-gray-400 font-normal">ESP221-2024-001</span>
                                    </td>
                                    <td class="py-4 px-4 text-gray-600">Développement Web PHP</td>
                                    <td class="py-4 px-4"><span class="bg-gray-100 text-gray-700 px-2.5 py-1 rounded-md text-xs font-medium">CC</span></td>
                                    <td class="py-4 px-4 font-bold text-gray-900">15.5/20</td>
                                    <td class="py-4 px-4 text-right space-x-3">
                                        <button class="text-gray-400 hover:text-blue-600 transition" title="Modifier"><i class="fa-regular fa-pen-to-square"></i></button>
                                        <button class="text-gray-400 hover:text-red-600 transition" title="Supprimer"><i class="fa-regular fa-trash-can"></i></button>
                                    </td>
                                </tr>
                                <tr class="hover:bg-gray-50/80 transition">
                                    <td class="py-4 px-4 font-bold text-gray-900">
                                        Aminata Sall
                                        <span class="block text-xs text-gray-400 font-normal">ESP221-2024-002</span>
                                    </td>
                                    <td class="py-4 px-4 text-gray-600">Base de données SQL</td>
                                    <td class="py-4 px-4"><span class="bg-gray-100 text-gray-700 px-2.5 py-1 rounded-md text-xs font-medium">Examen</span></td>
                                    <td class="py-4 px-4 font-bold text-gray-900">17.0/20</td>
                                    <td class="py-4 px-4 text-right space-x-3">
                                        <button class="text-gray-400 hover:text-blue-600 transition" title="Modifier"><i class="fa-regular fa-pen-to-square"></i></button>
                                        <button class="text-gray-400 hover:text-red-600 transition" title="Supprimer"><i class="fa-regular fa-trash-can"></i></button>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pied de tableau : Pagination -->
                <div class="flex justify-between items-center pt-4 border-t border-gray-100 text-xs text-gray-500">
                    <span>1 – 2 sur 2</span>
                    <div class="flex items-center space-x-3">
                        <span>Page 1 sur 1</span>
                        <div class="flex space-x-1">
                            <button class="w-7 h-7 flex items-center justify-center border border-gray-200 rounded text-gray-300 cursor-not-allowed"><i class="fa-solid fa-chevron-left text-[10px]"></i></button>
                            <button class="w-7 h-7 flex items-center justify-center border border-gray-200 rounded text-gray-300 cursor-not-allowed"><i class="fa-solid fa-chevron-right text-[10px]"></i></button>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </main>

    <!-- DRAWER LATÉRAL DE SAISIE DE NOTE -->
    <div id="drawer-overlay" class="fixed inset-0 bg-black/50 z-50 hidden transition-opacity opacity-0" onclick="toggleDrawer(false)"></div>
    
    <div id="drawer" class="fixed top-0 right-0 h-full w-96 bg-white shadow-2xl z-50 transform translate-x-full transition-transform duration-300 ease-in-out flex flex-col justify-between">
        <div>
            <div class="p-6 border-b border-gray-100 flex justify-between items-center">
                <h3 class="font-bold text-base text-gray-800">Saisir une note</h3>
                <button onclick="toggleDrawer(false)" class="text-gray-400 hover:text-gray-600 text-xl font-bold">&times;</button>
            </div>

            <form action="notes.php" method="POST" class="p-6 space-y-4">
                <input type="hidden" name="action" value="ajouter_note">
                
                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Étudiant</label>
                    <select name="etudiant_id" required class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm bg-white focus:outline-none focus:border-red-600">
                        <option value="">Sélectionner un étudiant</option>
                        <?php foreach ($etudiants as $et): ?>
                            <option value="<?= $et['id'] ?>"><?= htmlspecialchars($et['prenom'] . ' ' . $et['nom']) ?> (<?= htmlspecialchars($et['matricule']) ?>)</option>
                        <?php endforeach; ?>
                        <!-- Option de secours si aucun étudiant en BD -->
                        <option value="1">Mamadou Diallo (ESP221-2024-001)</option>
                        <option value="2">Aminata Sall (ESP221-2024-002)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Matière / Module</label>
                    <input type="text" name="matiere" required placeholder="ex: Développement Web PHP" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Type d'évaluation</label>
                    <select name="type" required class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm bg-white focus:outline-none focus:border-red-600">
                        <option value="CC">Contrôle Continu (CC)</option>
                        <option value="Examen">Examen</option>
                        <option value="Projet">Projet</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Note (/20)</label>
                    <input type="number" step="0.25" min="0" max="20" name="valeur" required placeholder="ex: 15.5" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600">
                </div>

                <div class="pt-4 flex justify-end space-x-2">
                    <button type="button" onclick="toggleDrawer(false)" class="px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition">
                        Annuler
                    </button>
                    <button type="submit" class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition shadow-sm">
                        Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Script de contrôle du Drawer -->
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
    </script>
</body>
</html>