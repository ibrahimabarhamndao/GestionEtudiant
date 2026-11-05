<?php
$pageActuelle = $pageActuelle ?? '';
?>
<div class="w-64 bg-white h-screen shadow-md flex flex-col justify-between overflow-y-auto font-sans border-r border-gray-100">
    <div class="flex-1">
        <!-- Logo / En-tête -->
        <div class="p-5 border-b border-gray-100 flex items-center space-x-3">
            <div class="bg-red-600 text-white p-2.5 rounded-xl shadow-xs flex items-center justify-center">
                <i class="fa-solid fa-house text-base"></i>
            </div>
            <div>
                <h1 class="font-bold text-base text-gray-900 leading-tight">ESP 221</h1>
                <p class="text-xs text-gray-500 font-medium">Gestion scolaire</p>
            </div>
        </div>

        <!-- Menu de navigation (espacements réduits) -->
        <div class="p-3 space-y-4">
            <!-- GESTION -->
            <div>
                <p class="text-[10px] font-bold text-gray-400 px-3 mb-1.5 uppercase tracking-wider">Gestion</p>
                <div class="space-y-0.5">
                    <a href="dashboard.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg transition <?= ($pageActuelle == 'dashboard') ? 'bg-red-50 text-red-600 font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                        <i class="fa-solid fa-table-cells-large w-5 text-center text-sm"></i>
                        <span class="text-sm">Tableau de bord</span>
                    </a>
                    <a href="filieres.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg transition <?= ($pageActuelle == 'filieres') ? 'bg-red-50 text-red-600 font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                        <i class="fa-solid fa-graduation-cap w-5 text-center text-sm"></i>
                        <span class="text-sm">Filière</span>
                    </a>
                    <a href="niveaux.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg transition <?= ($pageActuelle == 'niveaux') ? 'bg-red-50 text-red-600 font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                        <i class="fa-solid fa-chart-line w-5 text-center text-sm"></i>
                        <span class="text-sm">Niveaux</span>
                    </a>
                    <a href="classes.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg transition <?= ($pageActuelle == 'classes') ? 'bg-red-50 text-red-600 font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                        <i class="fa-solid fa-users w-5 text-center text-sm"></i>
                        <span class="text-sm">Classes</span>
                    </a>
                    <a href="etudiants.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg transition <?= ($pageActuelle == 'etudiants') ? 'bg-red-50 text-red-600 font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                        <i class="fa-solid fa-user-graduate w-5 text-center text-sm"></i>
                        <span class="text-sm">Étudiants</span>
                    </a>
                    <a href="notes.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg transition <?= ($pageActuelle == 'notes') ? 'bg-red-50 text-red-600 font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                        <i class="fa-solid fa-file-invoice w-5 text-center text-sm"></i>
                        <span class="text-sm">Notes</span>
                    </a>
                </div>
            </div>

            <!-- ORGANISATION -->
            <div>
                <p class="text-[10px] font-bold text-gray-400 px-3 mb-1.5 uppercase tracking-wider">Organisation</p>
                <div class="space-y-0.5">
                    <a href="emploi_du_temps.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg transition <?= ($pageActuelle == 'emploi_du_temps') ? 'bg-red-50 text-red-600 font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                        <i class="fa-regular fa-calendar-days w-5 text-center text-sm"></i>
                        <span class="text-sm">Emploi du Temps</span>
                    </a>
                    <a href="documents.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg transition <?= ($pageActuelle == 'documents') ? 'bg-red-50 text-red-600 font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                        <i class="fa-regular fa-file-lines w-5 text-center text-sm"></i>
                        <span class="text-sm">Documents</span>
                    </a>
                </div>
            </div>

            <!-- SYSTÈME -->
            <div>
                <p class="text-[10px] font-bold text-gray-400 px-3 mb-1.5 uppercase tracking-wider">Système</p>
                <div class="space-y-0.5">
                    <a href="notifications.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg transition <?= ($pageActuelle == 'notifications') ? 'bg-red-50 text-red-600 font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                        <i class="fa-regular fa-bell w-5 text-center text-sm"></i>
                        <span class="text-sm">Notifications</span>
                    </a>
                    <a href="parametres.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg transition <?= ($pageActuelle == 'parametres') ? 'bg-red-50 text-red-600 font-semibold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                        <i class="fa-solid fa-gear w-5 text-center text-sm"></i>
                        <span class="text-sm">Paramètres</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Bouton de déconnexion en bas (toujours visible) -->
    <div class="p-3 border-t border-gray-100 bg-white">
        <a href="../../login.php?logout=1" class="flex items-center space-x-3 px-3 py-2 rounded-lg text-red-600 hover:bg-red-50 font-medium text-sm transition">
            <i class="fa-solid fa-arrow-right-from-bracket w-5 text-center text-sm"></i>
            <span>Se déconnecter</span>
        </a>
    </div>
</div>