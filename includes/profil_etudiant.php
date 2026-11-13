<!-- Profil Étudiant (ouvre le modal) -->
<button type="button" onclick="openProfilModal()" aria-haspopup="dialog" title="Mon profil"
        class="flex items-center space-x-3 cursor-pointer text-left">
    <div class="w-9 h-9 rounded-full bg-blue-600 text-white font-bold flex items-center justify-center text-xs shadow-sm">
        <?= isset($etudiant['prenom']) ? strtoupper(substr($etudiant['prenom'], 0, 1) . substr($etudiant['nom'], 0, 1)) : 'MD' ?>
    </div>
    <div class="hidden sm:flex flex-col text-left">
        <span class="text-sm font-bold text-gray-900 leading-tight">
            <?= htmlspecialchars(($etudiant['prenom'] ?? 'Mamadou')) ?>
        </span>
        <span class="text-[11px] text-gray-500 leading-tight">
            <?= htmlspecialchars(($etudiant['nom'] ?? 'Diallo')) ?>
        </span>
    </div>
    <i class="hidden sm:inline fa-solid fa-chevron-down text-xs text-red-600 ml-1"></i>
</button>

<!-- MODAL PROFIL ÉTUDIANT -->
<div id="profilModal" class="fixed inset-0 z-[100] hidden" role="dialog" aria-modal="true" aria-labelledby="profilModalTitre">
    <div id="profilModalBackdrop" class="absolute inset-0 bg-black/40 opacity-0 transition-opacity duration-200" onclick="closeProfilModal()"></div>
    <div class="absolute inset-x-0 top-16 md:top-20 px-4 sm:px-6 md:px-8 flex justify-end pointer-events-none">
        <div id="profilModalCard" class="pointer-events-auto w-full max-w-sm bg-white rounded-3xl shadow-2xl overflow-hidden opacity-0 scale-95 -translate-y-2 transition-all duration-200 origin-top-right">
            <div class="bg-red-600 px-6 py-6 flex items-center gap-4">
                <div class="w-14 h-14 shrink-0 rounded-2xl bg-white/20 border-2 border-white/40 text-white text-xl font-bold flex items-center justify-center">E</div>
                <div class="min-w-0">
                    <p id="profilModalTitre" class="text-white font-bold text-base leading-tight">Etudiant</p>
                    <p class="text-white/90 text-sm truncate">etudiant@plateforme.com</p>
                    <span class="mt-1.5 inline-flex items-center gap-1.5 bg-white/20 text-white text-xs font-semibold px-3 py-1 rounded-full">
                        <i class="fa-solid fa-user-lock text-[11px]"></i> Etudiant
                    </span>
                </div>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-2 gap-3">
                    <div class="bg-red-50 rounded-xl p-4 min-w-0">
                        <p class="text-xs uppercase text-slate-500">E-mail</p>
                        <p class="text-sm font-bold text-slate-900 break-all">etudiant@plateforme.com</p>
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
                        <p class="text-sm font-bold text-slate-900">Limité</p>
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-3 mt-3">
                    <div class="bg-red-50 rounded-xl p-4 text-center">
                        <p class="text-xs uppercase text-slate-500">Filière</p>
                        <p class="text-sm font-bold text-red-600">INFO</p>
                    </div>
                    <div class="bg-red-50 rounded-xl p-4 text-center">
                        <p class="text-xs uppercase text-slate-500">Niveau</p>
                        <p class="text-sm font-bold text-red-600">INFO-L1</p>
                    </div>
                    <div class="bg-red-50 rounded-xl p-4 text-center">
                        <p class="text-xs uppercase text-slate-500">Classe</p>
                        <p class="text-sm font-bold text-red-600">INFO-L1-A</p>
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