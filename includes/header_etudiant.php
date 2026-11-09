<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
       <header class="bg-white border-b border-gray-200 px-8 py-4 flex justify-between items-center shrink-0">
            <div>
                <h1 class="text-lg font-bold text-gray-900"><?= htmlspecialchars($titrePage) ?></h1>
                <p class="text-xs text-gray-400"><?= htmlspecialchars($descriptionPage) ?></p>
            </div>

            <div class="flex items-center space-x-6">
                <!-- Notifications avec Badge -->
                <a href="notifications.php" class="relative text-gray-500 hover:text-gray-700 transition">
                    <i class="fa-regular fa-bell text-lg"></i>
                    <span class="absolute -top-1 -right-1 bg-red-600 text-white text-[10px] font-bold w-4 h-4 rounded-full flex items-center justify-center">2</span>
                </a>

                <!-- Profil Étudiant (Bleu) -->
                <div class="flex items-center space-x-3 cursor-pointer">
                    <div class="w-9 h-9 rounded-full bg-blue-600 text-white font-bold flex items-center justify-center text-xs shadow-sm">
                        <?= isset($etudiant['prenom']) ? strtoupper(substr($etudiant['prenom'], 0, 1) . substr($etudiant['nom'], 0, 1)) : 'MD' ?>
                    </div>
                    <div class="flex flex-col text-left">
                        <span class="text-sm font-bold text-gray-900 leading-tight">
                            <?= htmlspecialchars(($etudiant['prenom'] ?? 'Mamadou')) ?>
                        </span>
                        <span class="text-[11px] text-gray-500 leading-tight">
                            <?= htmlspecialchars(($etudiant['nom'] ?? 'Diallo')) ?>
                        </span>
                    </div>
                    <i class="fa-solid fa-chevron-down text-xs text-red-600 ml-1"></i>
                </div>
            </div>
        </header>
</body>
</html>