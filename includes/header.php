<?php
$titrePage = $titrePage ?? 'Administration';
$descriptionPage = $descriptionPage ?? 'Gestion Scolaire';
?>
<header class="bg-white border-b border-gray-100 px-8 py-4 flex justify-between items-center">
    <div>
        <h2 class="text-xl font-bold text-gray-800"><?= htmlspecialchars($titrePage) ?></h2>
        <p class="text-xs text-gray-500"><?= htmlspecialchars($descriptionPage) ?></p>
    </div>
    <div class="flex items-center space-x-4">
        <!-- Notifications -->
        <div class="relative">
            <span class="absolute -top-1 -right-1 bg-red-600 text-white text-[10px] w-4 h-4 rounded-full flex items-center justify-center font-bold">2</span>
            <button class="p-2 text-gray-400 hover:text-gray-600 bg-gray-50 rounded-full">🔔</button>
        </div>
        <!-- Profil Secrétaire -->
        <div class="flex items-center space-x-2 border-l pl-4 border-gray-200">
            <div class="w-8 h-8 bg-red-600 text-white rounded-full flex items-center justify-center font-bold text-sm">S</div>
            <div>
                <p class="text-xs font-bold text-gray-800">Secrétaire</p>
                <p class="text-[10px] text-gray-500">Secrétaire</p>
            </div>
        </div>
    </div>
</header>