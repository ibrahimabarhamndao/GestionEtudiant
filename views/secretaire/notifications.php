<?php
$pageActuelle = 'notifications'; 
$titrePage = 'Notifications'; 
$descriptionPage = 'Gérez les notifications diffusées'; 
require_once '../../config/db.php';

$erreurs = ['titre' => '', 'description' => '', 'general' => ''];
$ancien = ['titre' => '', 'description' => '', 'type' => 'rappel'];

// Fonction utilitaire : "Il y a X ..." à partir de created_at
function tempsEcoule($date) {
    $diff = time() - strtotime($date);
    if ($diff < 60) return "À l'instant";
    if ($diff < 3600) return "Il y a " . floor($diff / 60) . " min";
    if ($diff < 86400) return "Il y a " . floor($diff / 3600) . " h";
    return "Il y a " . floor($diff / 86400) . " j";
}

// Création automatique de la table si elle n'existe pas
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        titre VARCHAR(150) NOT NULL,
        description TEXT NOT NULL,
        type VARCHAR(50) DEFAULT 'rappel',
        lu TINYINT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
} catch (PDOException $e) {}

// Traitement de l'ajout d'une notification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'ajouter_notification') {
    $titre = trim($_POST['titre'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $type = trim($_POST['type'] ?? 'rappel'); // 'validation' ou 'rappel'

    // On conserve la saisie pour la réafficher dans le formulaire
    $ancien = ['titre' => $titre, 'description' => $description, 'type' => $type];

    if ($titre === '') {
        $erreurs['titre'] = "Le titre de la notification est obligatoire.";
    } elseif (mb_strlen($titre) > 150) {
        $erreurs['titre'] = "Le titre ne doit pas dépasser 150 caractères.";
    }

    if ($description === '') {
        $erreurs['description'] = "La description est obligatoire.";
    }

    if (!in_array($type, ['rappel', 'validation'], true)) {
        $erreurs['general'] = "Type de notification invalide.";
    }

    if ($erreurs['titre'] === '' && $erreurs['description'] === '' && $erreurs['general'] === '') {
        try {
            $stmt = $pdo->prepare("INSERT INTO notifications (titre, description, type, lu) VALUES (?, ?, ?, 0)");
            $stmt->execute([$titre, $description, $type]);
            header("Location: notifications.php");
            exit();
        } catch (PDOException $e) {
            $erreurs['general'] = "Erreur lors de l'enregistrement de la notification.";
        }
    }
}
$aDesErreurs = ($erreurs['titre'] !== '' || $erreurs['description'] !== '' || $erreurs['general'] !== '');

// Récupération des notifications
$notifications = [];
$nonLusCount = 0;
try {
    $notifications = $pdo->query("SELECT * FROM notifications ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
    $nonLusCount = (int) $pdo->query("SELECT COUNT(*) FROM notifications WHERE lu = 0")->fetchColumn();
} catch (PDOException $e) {
    $notifications = [];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Administration — Notifications</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 flex h-screen overflow-hidden font-sans">

    <?php include '../../includes/sidebar_secretaire.php'; ?>

    <main class="flex-1 min-w-0 flex flex-col h-screen overflow-hidden">
        
        <?php include '../../includes/header.php'; ?>

        <div class="flex-1 p-4 sm:p-6 md:p-8 overflow-y-auto space-y-6">
            
            <div class="flex justify-between items-center">
                <span id="compteur-non-lus" class="text-sm font-medium text-gray-500"><?= $nonLusCount ?> non lu(s)</span>
                <button onclick="toggleModal(true)" class="bg-red-600 text-white px-4 py-2.5 rounded-lg text-sm font-medium hover:bg-red-700 transition flex items-center space-x-2 shadow-sm cursor-pointer">
                    <span>Ajouter une notification</span>
                    <span class="font-bold">+</span>
                </button>
            </div>

            <!-- Liste des Notifications -->
            <div id="liste-notifications" class="space-y-3">
                <?php foreach ($notifications as $n): 
                    $isValidation = ($n['type'] === 'validation');
                ?>
                    <div class="notif-card bg-white p-5 rounded-2xl border border-gray-200 shadow-sm flex items-center justify-between hover:border-gray-300 transition-all duration-300"
                         data-id="<?= (int)$n['id'] ?>">
                        <div class="flex items-center space-x-4">
                            <?php if ($isValidation): ?>
                                <div class="w-10 h-10 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm">
                                    <i class="fa-solid fa-check"></i>
                                </div>
                            <?php else: ?>
                                <div class="w-10 h-10 rounded-full bg-amber-50 text-amber-500 flex items-center justify-center text-sm">
                                    <i class="fa-solid fa-bell"></i>
                                </div>
                            <?php endif; ?>
                            <div>
                                <h4 class="font-bold text-sm text-gray-900"><?= htmlspecialchars($n['titre']) ?></h4>
                                <p class="text-xs text-gray-500"><?= htmlspecialchars($n['description']) ?></p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-6">
                            <span class="text-xs text-gray-400 font-medium"><?= tempsEcoule($n['created_at']) ?></span>
                            <button type="button" onclick="supprimerNotification(<?= (int)$n['id'] ?>)" class="text-gray-400 hover:text-red-600 transition cursor-pointer" title="Supprimer">
                                <i class="fa-regular fa-trash-can"></i>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div id="etat-vide" class="<?= count($notifications) > 0 ? 'hidden' : '' ?> bg-white p-10 rounded-2xl border border-dashed border-gray-300 text-center text-sm text-gray-400">
                    <i class="fa-regular fa-bell-slash text-2xl mb-2 block"></i>
                    Aucune notification pour le moment.
                </div>
            </div>

        </div>
    </main>

    <!-- MODALE D'AJOUT -->
    <div id="modal-container" class="fixed inset-0 z-50 flex items-center justify-center hidden">
        <div id="modal-backdrop" class="fixed inset-0 bg-black/50 opacity-0 transition-opacity duration-300" onclick="toggleModal(false)"></div>

        <div id="modal-box" class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 p-6 z-10 opacity-0 scale-95 transition-all duration-300 space-y-4 max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center border-b border-gray-100 pb-3">
                <h3 class="font-bold text-base text-gray-800">Ajouter une notification</h3>
                <button onclick="toggleModal(false)" class="text-gray-400 hover:text-gray-600 text-xl font-bold cursor-pointer">&times;</button>
            </div>

            <form action="notifications.php" method="POST" class="space-y-3" novalidate>
                <input type="hidden" name="action" value="ajouter_notification">

                <?php if ($erreurs['general'] !== ''): ?>
                    <div class="p-3 bg-red-50 border-l-4 border-red-500 text-red-700 text-xs rounded-lg">
                        <?= htmlspecialchars($erreurs['general']) ?>
                    </div>
                <?php endif; ?>
                
                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Titre de la notification</label>
                    <input type="text" name="titre" value="<?= htmlspecialchars($ancien['titre']) ?>" placeholder="ex: Rappel important"
                           class="w-full px-3 py-2 border rounded-lg text-sm focus:outline-none focus:border-red-600 <?= $erreurs['titre'] !== '' ? 'border-red-500' : 'border-gray-200' ?>">
                    <?php if ($erreurs['titre'] !== ''): ?>
                        <p class="mt-1 text-xs text-red-600"><i class="fa-solid fa-circle-exclamation mr-1"></i><?= htmlspecialchars($erreurs['titre']) ?></p>
                    <?php endif; ?>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Type</label>
                    <select name="type" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm bg-white focus:outline-none focus:border-red-600">
                        <option value="rappel" <?= $ancien['type'] === 'rappel' ? 'selected' : '' ?>>Rappel / Information (Jaune)</option>
                        <option value="validation" <?= $ancien['type'] === 'validation' ? 'selected' : '' ?>>Validation / Succès (Vert)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Description / Message</label>
                    <textarea name="description" rows="3" placeholder="Détails de la notification..."
                              class="w-full px-3 py-2 border rounded-lg text-sm focus:outline-none focus:border-red-600 <?= $erreurs['description'] !== '' ? 'border-red-500' : 'border-gray-200' ?>"><?= htmlspecialchars($ancien['description']) ?></textarea>
                    <?php if ($erreurs['description'] !== ''): ?>
                        <p class="mt-1 text-xs text-red-600"><i class="fa-solid fa-circle-exclamation mr-1"></i><?= htmlspecialchars($erreurs['description']) ?></p>
                    <?php endif; ?>
                </div>

                <!-- Boutons d'action occupant la largeur totale -->
                <div class="pt-4 flex items-center space-x-3 border-t border-gray-100">
                    <button type="button" onclick="toggleModal(false)" class="w-full px-4 py-2.5 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition cursor-pointer text-center">
                        Annuler
                    </button>
                    <button type="submit" class="w-full px-4 py-2.5 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition shadow-sm cursor-pointer text-center">
                        Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- TOAST (annuler la suppression) -->
    <div id="toast" class="fixed bottom-4 right-4 sm:bottom-6 sm:right-6 z-50 hidden items-center space-x-4 bg-gray-900 text-white text-sm px-5 py-3 rounded-xl shadow-2xl">
        <span id="toast-message"></span>
        <button id="toast-annuler" type="button" class="font-bold text-amber-400 hover:text-amber-300 underline cursor-pointer">Annuler</button>
    </div>

    <script>
        const URL_ACTIONS = 'action.php';

        // Rouvre automatiquement la modale si le formulaire contient des erreurs
        <?php if ($aDesErreurs): ?>
        document.addEventListener('DOMContentLoaded', () => toggleModal(true));
        <?php endif; ?>

        let derniereNotifSupprimee = null;
        let toastTimer = null;

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
                setTimeout(() => container.classList.add('hidden'), 300);
            }
        }

        async function appelerAction(payload) {
            const res = await fetch(URL_ACTIONS, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            return res.json();
        }

        function afficherToast(texte, avecAnnuler) {
            const toast = document.getElementById('toast');
            document.getElementById('toast-message').textContent = texte;
            document.getElementById('toast-annuler').classList.toggle('hidden', !avecAnnuler);
            toast.classList.remove('hidden');
            toast.classList.add('flex');
            clearTimeout(toastTimer);
            toastTimer = setTimeout(cacherToast, 8000);
        }

        function cacherToast() {
            const toast = document.getElementById('toast');
            toast.classList.add('hidden');
            toast.classList.remove('flex');
        }

        function majEtatVide() {
            const restantes = document.querySelectorAll('.notif-card').length;
            document.getElementById('etat-vide').classList.toggle('hidden', restantes > 0);
        }

        // SUPPRIMER
        async function supprimerNotification(id) {
            if (!confirm('Supprimer cette notification ?')) return;

            try {
                const rep = await appelerAction({ action: 'supprimer_notification', id: id });
                if (!rep.success) {
                    afficherToast(rep.message || 'Échec de la suppression.', false);
                    return;
                }

                derniereNotifSupprimee = rep.data; 

                const card = document.querySelector('.notif-card[data-id="' + id + '"]');
                if (card) {
                    card.classList.add('opacity-0', 'scale-95');
                    setTimeout(() => { card.remove(); majEtatVide(); }, 300);
                }
                afficherToast('Notification supprimée.', true);
            } catch (e) {
                afficherToast('Erreur réseau.', false);
            }
        }

        // RÉCUPÉRER (annuler la suppression)
        document.getElementById('toast-annuler').addEventListener('click', async () => {
            if (!derniereNotifSupprimee) return;

            try {
                const rep = await appelerAction({ action: 'restaurer_notification', data: derniereNotifSupprimee });
                if (rep.success) {
                    derniereNotifSupprimee = null;
                    location.reload(); 
                } else {
                    afficherToast(rep.message || 'Échec de la restauration.', false);
                }
            } catch (e) {
                afficherToast('Erreur réseau.', false);
            }
        });
    </script>
</body>
</html>