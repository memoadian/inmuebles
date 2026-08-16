// Sidebar responsivo: en móvil se muestra como panel flotante sobre un overlay.
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('overlay');
const menuBtn = document.getElementById('menuBtn');

function toggleSidebar(show) {
    if (!sidebar || !overlay) return;
    sidebar.classList.toggle('-translate-x-full', !show);
    overlay.classList.toggle('hidden', !show);
}

menuBtn?.addEventListener('click', () => {
    toggleSidebar(sidebar.classList.contains('-translate-x-full'));
});

overlay?.addEventListener('click', () => toggleSidebar(false));

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') toggleSidebar(false);
});
