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
    /* ==========================================================
       MENU LATÉRAL
       - Téléphone (< 768px)  : menu caché, il s'ouvre avec le bouton ☰ du header
       - Tablette / PC (>= 768px) : rail d'icônes (5rem) qui s'étire à 16rem
         au survol et POUSSE le contenu (header et page se rétrécissent)
       ========================================================== */
    #sidebar-wrap {
        position: relative;
        flex-shrink: 0;
        width: 0;
        height: 100vh;
        z-index: 40;
    }

    /* Téléphone : tiroir hors écran */
    #sidebar-etudiant {
        position: fixed;
        top: 0;
        bottom: 0;
        left: 0;
        width: 16rem;
        max-width: 85vw;
        transform: translateX(-100%);
        visibility: hidden;
        transition: transform .3s ease, box-shadow .3s ease, visibility 0s linear .3s;
        z-index: 60;
        scrollbar-width: none;
    }
    #sidebar-etudiant::-webkit-scrollbar { display: none; }
    #sidebar-wrap.sb-open #sidebar-etudiant {
        transform: translateX(0);
        visibility: visible;
        box-shadow: 8px 0 24px rgba(0, 0, 0, .2);
        transition-delay: 0s;
    }

    /* Fond sombre derrière le tiroir (téléphone) */
    #sb-backdrop {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, .5);
        opacity: 0;
        pointer-events: none;
        transition: opacity .3s ease;
        z-index: 55;
    }
    #sidebar-wrap.sb-open #sb-backdrop {
        opacity: 1;
        pointer-events: auto;
    }

    /* Tablette / PC : rail d'icônes qui pousse le contenu */
    @media (min-width: 768px) {
        #sidebar-wrap {
            width: 5rem;
            transition: width .3s ease;
        }
        #sidebar-etudiant {
            position: absolute;
            width: 100%;
            max-width: none;
            transform: none;
            visibility: visible;
            box-shadow: 1px 0 3px rgba(0, 0, 0, .06);
            z-index: auto;
        }
        #sb-backdrop { display: none; }

        /* Les textes sont masqués quand le menu est réduit */
        #sidebar-wrap .sb-label {
            opacity: 0;
            transition: opacity .15s ease;
        }

        /* Menu étiré : clavier (focus) ou tactile (classe sb-open) */
        #sidebar-wrap.sb-open,
        #sidebar-wrap:focus-within {
            width: 16rem;
        }
        #sidebar-wrap.sb-open .sb-label,
        #sidebar-wrap:focus-within .sb-label {
            opacity: 1;
            transition: opacity .25s ease .1s;
        }
    }

    /* Menu étiré : survol de la souris (appareils avec souris uniquement) */
    @media (min-width: 768px) and (hover: hover) {
        #sidebar-wrap:hover {
            width: 16rem;
        }
        #sidebar-wrap:hover .sb-label {
            opacity: 1;
            transition: opacity .25s ease .1s;
        }
    }

    /* Mobiles : la hauteur tient compte de la barre d'adresse du navigateur */
    @supports (height: 100dvh) {
        body.h-screen, main.h-screen, #sidebar-wrap { height: 100dvh; }
    }
</style>

<!-- Menu latéral : réserve 5rem (icônes) et s'étire à 16rem en poussant le contenu -->
<div id="sidebar-wrap">
    <div id="sb-backdrop" aria-hidden="true"></div>
    <aside id="sidebar-etudiant" class="bg-white flex flex-col justify-between overflow-x-hidden overflow-y-auto">
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
                <button type="button" data-sb-toggle aria-label="Fermer le menu" aria-expanded="false"
                        class="md:hidden ml-auto w-9 h-9 shrink-0 flex items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-700 transition">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
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
    // Menu latéral : ouverture / fermeture
    (function () {
        var wrap = document.getElementById('sidebar-wrap');
        if (!wrap) return;
        var large = window.matchMedia('(min-width: 768px)');
        var tactile = window.matchMedia('(hover: none)');

        function majAria(ouvert) {
            var boutons = document.querySelectorAll('[data-sb-toggle]');
            for (var i = 0; i < boutons.length; i++) {
                boutons[i].setAttribute('aria-expanded', ouvert ? 'true' : 'false');
            }
        }
        function ouvrir() { wrap.classList.add('sb-open'); majAria(true); }
        function fermer() { wrap.classList.remove('sb-open'); majAria(false); }

        document.addEventListener('click', function (e) {
            // Bouton ☰ (dans le header) ou ✕ (dans le menu) : ouvre / ferme
            if (e.target.closest('[data-sb-toggle]')) {
                if (wrap.classList.contains('sb-open')) { fermer(); } else { ouvrir(); }
                return;
            }
            // Tablette tactile (>= 768px sans survol) : 1er appui = ouvre, 2e appui sur un lien = navigue
            if (large.matches && tactile.matches && wrap.contains(e.target) && !wrap.classList.contains('sb-open')) {
                e.preventDefault();
                ouvrir();
                return;
            }
            // Clic sur le fond sombre ou en dehors du menu : on le referme
            if (!wrap.contains(e.target) || e.target.id === 'sb-backdrop') fermer();
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') fermer();
        });

        // Changement de taille d'écran (rotation...) : on repart d'un menu fermé
        if (large.addEventListener) large.addEventListener('change', fermer);
    })();
</script>