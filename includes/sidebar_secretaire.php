<?php
$pageActuelle = $pageActuelle ?? '';
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
    #sidebar-secretaire {
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
    #sidebar-secretaire::-webkit-scrollbar { display: none; }
    #sidebar-wrap.sb-open #sidebar-secretaire {
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
        #sidebar-secretaire {
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

    /* ==========================================================
       TABLEAUX / CARTES
       - Téléphone et tablette (< 1024px) : seule la vue "Cartes" est affichée
       - Grands écrans (>= 1024px) : les deux vues restent disponibles
       ========================================================== */
    @media (max-width: 1023.98px) {
        #view-table { display: none !important; }
        #view-cards { display: block !important; }
    }
</style>

<!-- Menu latéral : réserve 5rem (icônes) et s'étire à 16rem en poussant le contenu -->
<div id="sidebar-wrap">
    <div id="sb-backdrop" aria-hidden="true"></div>
    <aside id="sidebar-secretaire" class="bg-white flex flex-col justify-between overflow-x-hidden overflow-y-auto font-sans border-r border-gray-100">
        <div class="flex-1">
            <!-- Logo / En-tête -->
            <div class="px-[22px] py-5 border-b border-gray-100 flex items-center space-x-3 whitespace-nowrap">
                <div class="bg-red-600 text-white p-2.5 rounded-xl shadow-xs flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-house text-base"></i>
                </div>
                <div class="sb-label">
                    <h1 class="font-bold text-base text-gray-900 leading-tight">ESP 221</h1>
                    <p class="text-xs text-gray-500 font-medium">Gestion scolaire</p>
                </div>
                <button type="button" data-sb-toggle aria-label="Fermer le menu" aria-expanded="false"
                        class="md:hidden ml-auto w-9 h-9 shrink-0 flex items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-700 transition">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <!-- Menu de navigation -->
            <div class="px-4 py-3 space-y-4">
                <!-- GESTION -->
                <div>
                    <p class="sb-label text-[10px] font-bold text-gray-400 px-3 mb-1.5 uppercase tracking-wider whitespace-nowrap">Gestion</p>
                    <div class="space-y-0.5">
                        <a href="dashboard.php" class="flex items-center space-x-3 px-3.5 py-2 rounded-lg transition whitespace-nowrap <?= ($pageActuelle == 'dashboard') ? 'bg-red-50 text-red-600 font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                            <i class="fa-solid fa-table-cells-large w-5 text-center text-sm shrink-0"></i>
                            <span class="sb-label text-sm">Tableau de bord</span>
                        </a>
                        <a href="filieres.php" class="flex items-center space-x-3 px-3.5 py-2 rounded-lg transition whitespace-nowrap <?= ($pageActuelle == 'filieres') ? 'bg-red-50 text-red-600 font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                            <i class="fa-solid fa-graduation-cap w-5 text-center text-sm shrink-0"></i>
                            <span class="sb-label text-sm">Filière</span>
                        </a>
                        <a href="niveaux.php" class="flex items-center space-x-3 px-3.5 py-2 rounded-lg transition whitespace-nowrap <?= ($pageActuelle == 'niveaux') ? 'bg-red-50 text-red-600 font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                            <i class="fa-solid fa-chart-line w-5 text-center text-sm shrink-0"></i>
                            <span class="sb-label text-sm">Niveaux</span>
                        </a>
                        <a href="classes.php" class="flex items-center space-x-3 px-3.5 py-2 rounded-lg transition whitespace-nowrap <?= ($pageActuelle == 'classes') ? 'bg-red-50 text-red-600 font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                            <i class="fa-solid fa-users w-5 text-center text-sm shrink-0"></i>
                            <span class="sb-label text-sm">Classes</span>
                        </a>
                        <a href="etudiants.php" class="flex items-center space-x-3 px-3.5 py-2 rounded-lg transition whitespace-nowrap <?= ($pageActuelle == 'etudiants') ? 'bg-red-50 text-red-600 font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                            <i class="fa-solid fa-user-graduate w-5 text-center text-sm shrink-0"></i>
                            <span class="sb-label text-sm">Étudiants</span>
                        </a>
                        <a href="notes.php" class="flex items-center space-x-3 px-3.5 py-2 rounded-lg transition whitespace-nowrap <?= ($pageActuelle == 'notes') ? 'bg-red-50 text-red-600 font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                            <i class="fa-solid fa-file-invoice w-5 text-center text-sm shrink-0"></i>
                            <span class="sb-label text-sm">Notes</span>
                        </a>
                    </div>
                </div>

                <!-- ORGANISATION -->
                <div>
                    <p class="sb-label text-[10px] font-bold text-gray-400 px-3 mb-1.5 uppercase tracking-wider whitespace-nowrap">Organisation</p>
                    <div class="space-y-0.5">
                        <a href="emploi_du_temps.php" class="flex items-center space-x-3 px-3.5 py-2 rounded-lg transition whitespace-nowrap <?= ($pageActuelle == 'emploi_du_temps') ? 'bg-red-50 text-red-600 font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                            <i class="fa-regular fa-calendar-days w-5 text-center text-sm shrink-0"></i>
                            <span class="sb-label text-sm">Emploi du Temps</span>
                        </a>
                        <a href="documents.php" class="flex items-center space-x-3 px-3.5 py-2 rounded-lg transition whitespace-nowrap <?= ($pageActuelle == 'documents') ? 'bg-red-50 text-red-600 font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                            <i class="fa-regular fa-file-lines w-5 text-center text-sm shrink-0"></i>
                            <span class="sb-label text-sm">Documents</span>
                        </a>
                    </div>
                </div>

                <!-- SYSTÈME -->
                <div>
                    <p class="sb-label text-[10px] font-bold text-gray-400 px-3 mb-1.5 uppercase tracking-wider whitespace-nowrap">Système</p>
                    <div class="space-y-0.5">
                        <a href="notifications.php" class="flex items-center space-x-3 px-3.5 py-2 rounded-lg transition whitespace-nowrap <?= ($pageActuelle == 'notifications') ? 'bg-red-50 text-red-600 font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                            <i class="fa-regular fa-bell w-5 text-center text-sm shrink-0"></i>
                            <span class="sb-label text-sm">Notifications</span>
                        </a>
                        <a href="parametres.php" class="flex items-center space-x-3 px-3.5 py-2 rounded-lg transition whitespace-nowrap <?= ($pageActuelle == 'parametres') ? 'bg-red-50 text-red-600 font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                            <i class="fa-solid fa-gear w-5 text-center text-sm shrink-0"></i>
                            <span class="sb-label text-sm">Paramètres</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bouton de déconnexion en bas (toujours visible) -->
        <div class="px-4 py-3 border-t border-gray-100 bg-white">
            <a href="../../login.php?logout=1" class="flex items-center space-x-3 px-3.5 py-2 rounded-lg text-red-600 hover:bg-red-50 font-medium text-sm transition whitespace-nowrap">
                <i class="fa-solid fa-arrow-right-from-bracket w-5 text-center text-sm shrink-0"></i>
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