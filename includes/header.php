<header class="bg-white border-b border-gray-200 px-8 py-4 flex justify-between items-center shadow-2xs">
    <!-- Titre et sous-titre dynamiques -->
    <div>
        <h1 class="text-lg font-bold text-gray-900"><?= htmlspecialchars($titrePage ?? 'Tableau de bord') ?></h1>
        <p class="text-xs text-gray-500"><?= htmlspecialchars($descriptionPage ?? 'Gestion de l\'application') ?></p>
    </div>

    <!-- Profil et Notifications à droite -->
    <div class="flex items-center space-x-6">
        <!-- Icône de Notification avec badge -->
        <a href="notifications.php" class="relative text-gray-500 hover:text-gray-700 transition" title="Notifications">
            <i class="fa-regular fa-bell text-lg"></i>
            <span class="absolute -top-1 -right-1 bg-red-600 text-white text-[10px] font-bold w-4 h-4 rounded-full flex items-center justify-center">2</span>
        </a>

        <!-- Bloc Profil Secrétaire -->
        <div class="flex items-center space-x-3 cursor-pointer">
            <div class="w-9 h-9 rounded-full bg-red-600 text-white font-bold flex items-center justify-center text-sm shadow-sm">
                S
            </div>
            <div class="flex flex-col text-left">
                <span class="text-sm font-bold text-gray-900 leading-tight">Secrétaire</span>
                <span class="text-[11px] text-gray-500 leading-tight">Secrétaire</span>
            </div>
            <i class="fa-solid fa-chevron-down text-xs text-red-600 ml-1"></i>
        </div>
    </div>
</header>