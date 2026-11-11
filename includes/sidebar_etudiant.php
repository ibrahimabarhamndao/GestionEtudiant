<?php
// Menu latéral de l'espace étudiant (à inclure dans les 5 pages de views/user/)
// La page doit définir $pageActuelle : dashboard, notes, emploi_du_temps, documents ou notifications
$pageActuelle = $pageActuelle ?? '';

$menu = [
    'MON ESPACE' => [
        ['dashboard',       'dashboard.php',       'fa-solid fa-border-all',     'Tableau de bord'],
        ['notes',           'notes.php',           'fa-regular fa-square-check', 'Mes notes'],
    ],
    'RESSOURCES' => [
        ['emploi_du_temps', 'emploi_du_temps.php', 'fa-regular fa-calendar',     'Emploi du Temps'],
        ['documents',       'documents.php',       'fa-regular fa-file-lines',   'Documents'],
        ['notifications',   'notifications.php',   'fa-regular fa-bell',         'Notifications'],
    ],
];
?>
<style>
    /* Menu latéral : réduit (icônes seules) par défaut, il s'étire au survol */
    #sidebar-etudiant {
        width: 5rem;
        transition: width .3s ease, box-shadow .3s ease;
        scrollbar-width: none;
    }
    #sidebar-etudiant::-webkit-scrollbar { display: none; }

    /* Les textes sont masqués quand le menu est réduit */
    #sidebar-etudiant .sb-label {
        opacity: 0;
        transition: opacity .15s ease;
    }

    /* Menu étiré : clavier (focus), tactile (classe sb-open) */
    #sidebar-etudiant:focus-within,
    #sidebar-etudiant.sb-open {
        width: 16rem;
        box-shadow: 8px 0 24px rgba(0, 0, 0, .12);
    }
    #sidebar-etudiant:focus-within .sb-label,
    #sidebar-etudiant.sb-open .sb-label {
        opacity: 1;
        transition: opacity .25s ease .1s;
    }

    /* Menu étiré : survol de la souris (appareils avec souris uniquement) */
    @media (hover: hover) {
        #sidebar-etudiant:hover {
            width: 16rem;
            box-shadow: 8px 0 24px rgba(0, 0, 0, .12);
        }
        #sidebar-etudiant:hover .sb-label {
            opacity: 1;
            transition: opacity .25s ease .1s;
        }
    }
</style>

<!-- Réserve 5rem dans la mise en page : le menu s'étire PAR-DESSUS le contenu sans le pousser -->
<div class="relative w-20 shrink-0 h-screen z-50">
    <aside id="sidebar-etudiant" class="absolute inset-y-0 left-0 bg-white border-r border-gray-200 flex flex-col justify-between overflow-x-hidden overflow-y-auto">
        <div>
            <!-- Logo -->
            <div class="px-5 py-6 flex items-center space-x-3 border-b border-gray-100 whitespace-nowrap">
                <div class="w-10 h-10 shrink-0 rounded-2xl bg-red-600 flex items-center justify-center text-white font-bold shadow-md">
                    <i class="fa-solid fa-house text-lg"></i>
                </div>
                <div class="sb-label">
                    <h2 class="font-extrabold text-base text-gray-900 leading-tight">ESP 221</h2>
                    <p class="text-xs text-gray-400">Gestion Scolaire</p>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="px-4 py-4 space-y-6">
                <?php foreach ($menu as $titre => $liens): ?>
                    <div>
                        <span class="sb-label block text-[10px] font-bold text-gray-400 uppercase tracking-wider px-3 whitespace-nowrap"><?= htmlspecialchars($titre) ?></span>
                        <div class="mt-2 space-y-1">
                            <?php foreach ($liens as [$cle, $url, $icone, $libelle]): ?>
                                <a href="<?= $url ?>" class="flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-sm transition whitespace-nowrap <?= ($pageActuelle === $cle) ? 'font-semibold bg-red-50 text-red-600' : 'font-medium text-gray-500 hover:bg-gray-50 hover:text-gray-900' ?>">
                                    <i class="<?= $icone ?> text-base w-5 text-center shrink-0"></i>
                                    <span class="sb-label"><?= htmlspecialchars($libelle) ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </nav>
        </div>

        <!-- Déconnexion -->
        <div class="px-4 py-4 border-t border-gray-100">
            <a href="../../logout.php" class="flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-sm font-medium text-red-600 hover:bg-red-50 transition whitespace-nowrap">
                <i class="fa-solid fa-arrow-right-from-bracket w-5 text-center shrink-0"></i>
                <span class="sb-label">Se déconnecter</span>
            </a>
        </div>
    </aside>
</div>

<script>
    // Appareils tactiles (pas de survol) : 1er appui = ouvre le menu, 2e appui sur un lien = navigue
    (function () {
        var sb = document.getElementById('sidebar-etudiant');
        if (!sb) return;
        var tactile = window.matchMedia('(hover: none)');
        sb.addEventListener('click', function (e) {
            if (tactile.matches && !sb.classList.contains('sb-open')) {
                e.preventDefault();
                sb.classList.add('sb-open');
            }
        });
        document.addEventListener('click', function (e) {
            if (!sb.contains(e.target)) sb.classList.remove('sb-open');
        });
    })();
</script>