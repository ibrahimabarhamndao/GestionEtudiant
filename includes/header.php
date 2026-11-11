<header class="bg-white border-b border-gray-200 px-4 sm:px-6 md:px-8 py-3 md:py-4 flex justify-between items-center gap-3 shrink-0 shadow-2xs">
    <!-- Bouton menu (téléphone) + titre et sous-titre dynamiques -->
    <div class="flex items-center gap-3 min-w-0">
        <button type="button" data-sb-toggle aria-label="Ouvrir le menu" aria-expanded="false"
                class="md:hidden shrink-0 w-10 h-10 flex items-center justify-center rounded-lg text-gray-600 hover:bg-gray-100 transition">
            <i class="fa-solid fa-bars text-lg"></i>
        </button>
        <div class="min-w-0">
            <h1 class="text-base md:text-lg font-bold text-gray-900 truncate"><?= htmlspecialchars($titrePage ?? 'Tableau de bord') ?></h1>
            <p class="text-xs text-gray-500 truncate"><?= htmlspecialchars($descriptionPage ?? 'Gestion de l\'application') ?></p>
        </div>
    </div>

    <!-- Profil et Notifications à droite -->
    <div class="flex items-center gap-4 md:gap-6 shrink-0">
        <!-- Icône de Notification avec badge -->
        <a href="notifications.php" class="relative text-gray-500 hover:text-gray-700 transition" title="Notifications">
            <i class="fa-regular fa-bell text-lg"></i>
            <span class="absolute -top-1 -right-1 bg-red-600 text-white text-[10px] font-bold w-4 h-4 rounded-full flex items-center justify-center">2</span>
        </a>

        <!-- Bloc Profil Secrétaire -->
        <div class="flex items-center gap-3 cursor-pointer">
            <div class="w-9 h-9 rounded-full bg-red-600 text-white font-bold flex items-center justify-center text-sm shadow-sm">
                S
            </div>
            <div class="hidden sm:flex flex-col text-left">
                <span class="text-sm font-bold text-gray-900 leading-tight">Secrétaire</span>
                <span class="text-[11px] text-gray-500 leading-tight">Secrétaire</span>
            </div>
            <i class="hidden sm:inline fa-solid fa-chevron-down text-xs text-red-600 ml-1"></i>
        </div>
    </div>
</header>