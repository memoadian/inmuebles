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

// Zona de arrastrar y soltar para subir fotos de propiedades.
const imageDropzone = document.getElementById('imageDropzone');
const imageInput = document.getElementById('imageInput');
const imageFileList = document.getElementById('imageFileList');

if (imageDropzone && imageInput) {
    const renderSelectedFiles = (files) => {
        if (!imageFileList) return;
        imageFileList.textContent = files.length
            ? `${files.length} archivo(s) seleccionados: ${Array.from(files).map((f) => f.name).join(', ')}`
            : '';
    };

    // Evita que el navegador abra el archivo si el usuario falla la zona.
    ['dragover', 'drop'].forEach((evt) => {
        window.addEventListener(evt, (e) => e.preventDefault());
    });

    ['dragenter', 'dragover'].forEach((evt) => {
        imageDropzone.addEventListener(evt, (e) => {
            e.preventDefault();
            imageDropzone.classList.add('border-slate-900', 'bg-slate-100');
        });
    });

    ['dragleave', 'dragend', 'drop'].forEach((evt) => {
        imageDropzone.addEventListener(evt, (e) => {
            e.preventDefault();
            imageDropzone.classList.remove('border-slate-900', 'bg-slate-100');
        });
    });

    imageDropzone.addEventListener('drop', (e) => {
        const dt = e.dataTransfer;
        if (!dt) return;

        // En algunos navegadores/SO (p. ej. Firefox en Linux con Wayland) el
        // drag-and-drop nativo deja dataTransfer.files vacío aunque sí venga
        // el archivo en dataTransfer.items; por eso probamos items primero.
        const collected = [];

        if (dt.items && dt.items.length) {
            for (const item of dt.items) {
                if (item.kind === 'file') {
                    const file = item.getAsFile();
                    if (file) collected.push(file);
                }
            }
        }

        if (!collected.length && dt.files?.length) {
            collected.push(...dt.files);
        }

        if (!collected.length) {
            console.warn('Drag-and-drop: no se recibió ningún archivo utilizable desde el navegador.');
            return;
        }

        // Reasignar un FileList directamente a otro <input> no es confiable
        // en todos los navegadores; reconstruirlo con DataTransfer sí.
        const dataTransfer = new DataTransfer();
        collected.forEach((file) => dataTransfer.items.add(file));
        imageInput.files = dataTransfer.files;

        renderSelectedFiles(imageInput.files);
    });

    imageInput.addEventListener('change', () => renderSelectedFiles(imageInput.files));
}
