<!-- CONTAINER DES TOASTS NOTIFICATIONS -->
<div id="toastContainer" class="fixed bottom-6 right-6 z-50 flex flex-col space-y-3"></div>

<script>
// --- FONCTION TOAST FIDÈLE AU FIGMA ---
function showToast(message, type = 'success') {
    const container = document.getElementById('toastContainer');
    const isSuccess = type === 'success';
    
    // Couleurs selon le design Figma : vert émeraude pour succès, rouge pour suppression/erreur
    const bgColor = isSuccess ? 'bg-[#00B67A]' : 'bg-[#DC2626]'; 
    
    const toast = document.createElement('div');
    toast.className = `flex items-center justify-between space-x-4 ${bgColor} text-white px-6 py-4 rounded-2xl shadow-xl transform transition-all duration-300 translate-y-5 opacity-0 min-w-[320px]`;
    
    // Icône de gauche (coche verte ou poubelle/croix pour le rouge)
    const iconSvg = isSuccess 
        ? `<svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path></svg>`
        : `<svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>`;

    toast.innerHTML = `
        <div class="flex items-center space-x-3">
            ${iconSvg}
            <span class="text-sm font-semibold tracking-wide">${message}</span>
        </div>
        <button onclick="this.parentElement.remove()" class="text-white/80 hover:text-white transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
    `;

    container.appendChild(toast);

    // Animation d'apparition fluide
    setTimeout(() => {
        toast.classList.remove('translate-y-5', 'opacity-0');
    }, 50);

    // Disparition automatique après 4 secondes
    setTimeout(() => {
        toast.classList.add('opacity-0', 'translate-y-5');
        setTimeout(() => toast.remove(), 300);
    }, 4000);
}
</script>