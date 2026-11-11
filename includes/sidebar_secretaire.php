<?php
$pageActuelle = $pageActuelle ?? '';
?>
<style>
    /* Menu latéral : réduit (icônes seules) par défaut, il s'étire au survol */
    #sidebar-secretaire {
        width: 5rem;
        transition: width .3s ease, box-shadow .3s ease;
        scrollbar-width: none;
    }
    #sidebar-secretaire::-webkit-scrollbar { display: none; }

    /* Les textes sont masqués quand le menu est réduit */
    #sidebar-secretaire .sb-label {
        opacity: 0;
        transition: opacity .15s ease;
    }

    /* Menu étiré : clavier (focus), tactile (classe sb-open) */
    #sidebar-secretaire:focus-within,
    #sidebar-secretaire.sb-open {
        width: 16rem;
        box-shadow: 8px 0 24px rgba(0, 0, 0, .12);
    }
    #sidebar-secretaire:focus-within .sb-label,
    #sidebar-secretaire.sb-open .sb-label {
        opacity: 1;
        transition: opacity .25s ease .1s;
    }

    /* Menu étiré : survol de la souris (appareils avec souris uniquement) */
    @media (hover: hover) {
        #sidebar-secretaire:hover {
            width: 16rem;
            box-shadow: 8px 0 24px rgba(0, 0, 0, .12);
        }
        #sidebar-secretaire:hover .sb-label {
            opacity: 1;
            transition: opacity .25s ease .1s;
        }
    }
</style>

<!-- Réserve 5rem dans la mise en page : le menu s'étire PAR-DESSUS le contenu sans le pousser -->
<div class="relative w-20 shrink-0 h-screen z-50">
    <aside id="sidebar-secretaire" class="absolute inset-y-0 left-0 bg-white shadow-md flex flex-col justify-between overflow-x-hidden overflow-y-auto font-sans border-r border-gray-100">
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
    // Appareils tactiles (pas de survol) : 1er appui = ouvre le menu, 2e appui sur un lien = navigue
    (function () {
        var sb = document.getElementById('sidebar-secretaire');
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