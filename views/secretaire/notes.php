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

// Récupération des notes avec le nom des étudiants si la table existe
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
            
            <!-- Barre de recherche et Bouton d'action -->
            <div class="flex justify-between items-center">
                <div class="relative w-96">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">🔍</span>
                    <input type="text" placeholder="Rechercher une note ou un étudiant..." class="w-full pl-10 pr-4 py-2 bg-white border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-red-600">
                </div>
                <div class="flex items-center space-x-3">
                    <div class="bg-white border border-gray-200 rounded-lg p-1 flex text-xs">
                        <button class="px-3 py-1 bg-red-50 text-red-600 font-semibold rounded">Tableau</button>
                        <button class="px-3 py-1 text-gray-500 hover:text-gray-800">Par Matière</button>
                    </div>
                    <button onclick="toggleDrawer(true)" class="bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-red-700 transition flex items-center space-x-1">
                        <span>Saisir une note</span> <span class="font-bold">+</span>
                    </button>
                </div>
            </div>

            <?php if (!empty($message)): ?>
                <div class="mb-4 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 text-sm rounded">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <!-- Tableau des Notes -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 text-gray-400 text-xs uppercase tracking-wider border-b border-gray-100">
                            <th class="p-4 font-semibold">Étudiant</th>
                            <th class="p-4 font-semibold">Matière / Module</th>
                            <th class="p-4 font-semibold">Type (CC / Examen)</th>
                            <th class="p-4 font-semibold">Note / 20</th>
                            <th class="p-4 font-semibold text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm text-gray-600">
                        <?php if (count($notes) > 0): ?>
                            <?php foreach ($notes as $n): ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="p-4 font-semibold text-gray-800">
                                    <?= htmlspecialchars(($n['prenom'] ?? '') . ' ' . ($n['nom'] ?? 'Étudiant inconnu')) ?>
                                    <span class="block text-xs text-gray-400"><?= htmlspecialchars($n['matricule'] ?? '') ?></span>
                                </td>
                                <td class="p-4 text-gray-600"><?= htmlspecialchars($n['matiere']) ?></td>
                                <td class="p-4"><span class="bg-gray-100 text-gray-700 px-2 py-0.5 rounded text-xs"><?= htmlspecialchars($n['type']) ?></span></td>
                                <td class="p-4 font-bold text-gray-800"><?= htmlspecialchars($n['valeur']) ?>/20</td>
                                <td class="p-4 text-right space-x-2">
                                    <button class="text-gray-400 hover:text-blue-600 transition" title="Modifier">✏️</button>
                                    <button class="text-gray-400 hover:text-red-600 transition" title="Supprimer">🗑️</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="p-12 text-center text-gray-400">
                                    <div class="flex flex-col items-center justify-center space-y-2">
                                        <span class="text-3xl">📊</span>
                                        <p class="text-sm font-medium">Aucune note enregistrée pour le moment.</p>
                                        <p class="text-xs text-gray-300">Cliquez sur « Saisir une note » pour commencer.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </main>

    <!-- DRAWER D'AJOUT DE NOTE -->
    <div id="drawer-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden transition-opacity opacity-0" onclick="toggleDrawer(false)"></div>
    
    <div id="drawer" class="fixed top-0 right-0 h-full w-96 bg-white shadow-2xl z-50 transform translate-x-full transition-transform duration-300 ease-in-out flex flex-col justify-between">
        <div>
            <div class="p-6 border-b border-gray-100 flex justify-between items-center">
                <h3 class="font-bold text-lg text-gray-800">Saisir une note</h3>
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

                <div class="pt-4">
                    <button type="submit" class="w-full bg-red-600 text-white py-2.5 rounded-lg text-sm font-medium hover:bg-red-700 transition">
                        Enregistrer la note
                    </button>
                </div>
            </form>
        </div>
    </div>

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