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

        <!-- Bloc Profil Secrétaire (ouvre le modal) -->
        <button type="button" onclick="openProfilModal()" aria-haspopup="dialog" title="Mon profil"
                class="flex items-center gap-3 cursor-pointer text-left">
            <div class="w-9 h-9 rounded-full bg-red-600 text-white font-bold flex items-center justify-center text-sm shadow-sm">
                S
            </div>
            <div class="hidden sm:flex flex-col text-left">
                <span class="text-sm font-bold text-gray-900 leading-tight">Secrétaire</span>
                <span class="text-[11px] text-gray-500 leading-tight">Secrétaire</span>
            </div>
            <i class="hidden sm:inline fa-solid fa-chevron-down text-xs text-red-600 ml-1"></i>
        </button>
    </div>
</header>

    <!-- MODAL PROFIL SECRÉTAIRE -->
    <div id="profilModal" class="fixed inset-0 z-[100] hidden" role="dialog" aria-modal="true" aria-labelledby="profilModalTitre">
        <div id="profilModalBackdrop" class="absolute inset-0 bg-black/40 opacity-0 transition-opacity duration-200" onclick="closeProfilModal()"></div>
        <div class="absolute inset-x-0 top-16 md:top-20 px-4 sm:px-6 md:px-8 flex justify-end pointer-events-none">
            <div id="profilModalCard" class="pointer-events-auto w-full max-w-sm bg-white rounded-3xl shadow-2xl overflow-hidden opacity-0 scale-95 -translate-y-2 transition-all duration-200 origin-top-right">
                <div class="bg-red-600 px-6 py-6 flex items-center gap-4">
                    <div class="w-14 h-14 shrink-0 rounded-2xl bg-white/20 border-2 border-white/40 text-white text-xl font-bold flex items-center justify-center">S</div>
                    <div class="min-w-0">
                        <p id="profilModalTitre" class="text-white font-bold text-base leading-tight">Secrétaire</p>
                        <p class="text-white/90 text-sm truncate">secretaire@plateforme.com</p>
                        <span class="mt-1.5 inline-flex items-center gap-1.5 bg-white/20 text-white text-xs font-semibold px-3 py-1 rounded-full">
                            <i class="fa-solid fa-user-lock text-[11px]"></i> Secrétaire
                        </span>
                    </div>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-2 gap-3">
                        <div class="bg-red-50 rounded-xl p-4 min-w-0">
                            <p class="text-xs uppercase text-slate-500">E-mail</p>
                            <p class="text-sm font-bold text-slate-900 break-all">secretaire@plateforme.com</p>
                        </div>
                        <div class="bg-red-50 rounded-xl p-4 min-w-0">
                            <p class="text-xs uppercase text-slate-500">Rôle</p>
                            <p class="text-sm font-bold text-slate-900">Secrétaire</p>
                        </div>
                        <div class="bg-red-50 rounded-xl p-4 min-w-0">
                            <p class="text-xs uppercase text-slate-500">Établissement</p>
                            <p class="text-sm font-bold text-slate-900">ESP 221</p>
                        </div>
                        <div class="bg-red-50 rounded-xl p-4 min-w-0">
                            <p class="text-xs uppercase text-slate-500">Accès</p>
                            <p class="text-sm font-bold text-slate-900">Complet</p>
                        </div>
                    </div>
                    <a href="../../logout.php" class="mt-5 flex items-center gap-3 pt-4 border-t border-gray-200 text-red-600 hover:text-red-700 transition">
                        <i class="fa-solid fa-arrow-right-from-bracket"></i>
                        <span class="text-base">Se déconnecter</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

<script>
// --- MODAL PROFIL ---
function openProfilModal() {
    const modal = document.getElementById('profilModal');
    const backdrop = document.getElementById('profilModalBackdrop');
    const card = document.getElementById('profilModalCard');
    modal.classList.remove('hidden');
    requestAnimationFrame(() => {
        backdrop.classList.remove('opacity-0');
        card.classList.remove('opacity-0', 'scale-95', '-translate-y-2');
    });
}

function closeProfilModal() {
    const modal = document.getElementById('profilModal');
    const backdrop = document.getElementById('profilModalBackdrop');
    const card = document.getElementById('profilModalCard');
    backdrop.classList.add('opacity-0');
    card.classList.add('opacity-0', 'scale-95', '-translate-y-2');
    setTimeout(() => modal.classList.add('hidden'), 200);
}

document.addEventListener('keydown', (e) => {
    const modal = document.getElementById('profilModal');
    if (e.key === 'Escape' && modal && !modal.classList.contains('hidden')) closeProfilModal();
});
</script>
