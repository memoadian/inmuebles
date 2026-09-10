// Visor de galería de la ficha pública: al hacer clic en cualquier foto se abre
// una capa a pantalla completa con navegación por flechas, teclado y gestos.
//
// Mismo criterio que el "ampliar" del mapa (ver map.js): en vez de la
// Fullscreen API —que Safari de iOS no aplica a elementos— se usa una capa
// fija propia, que se comporta igual en todos los navegadores y cierra con
// Escape.

const root = document.querySelector('[data-gallery]');

if (root) {
    // Las URLs de todas las fotos, en orden, vienen serializadas desde Blade.
    let sources = [];
    try {
        sources = JSON.parse(root.dataset.images ?? '[]');
    } catch {
        sources = [];
    }

    if (sources.length) {
        const alt = root.dataset.alt ?? '';
        const single = sources.length === 1;
        let index = 0;

        const overlay = document.createElement('div');
        overlay.className = [
            'fixed', 'inset-0', 'z-[70]', 'hidden', 'flex-col', 'bg-stone-950/95',
            'backdrop-blur-sm', 'select-none',
        ].join(' ');
        overlay.setAttribute('role', 'dialog');
        overlay.setAttribute('aria-modal', 'true');
        overlay.setAttribute('aria-label', 'Galería de fotos');
        overlay.innerHTML = `
            <div class="flex items-center justify-between px-4 py-3 text-white/90">
                <span data-viewer-counter class="text-sm font-medium tabular-nums ${single ? 'invisible' : ''}"></span>
                <button type="button" data-viewer-close aria-label="Cerrar galería"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-full hover:bg-white/10">
                    <i class="bi bi-x-lg text-xl"></i>
                </button>
            </div>

            <div data-viewer-stage class="relative flex flex-1 items-center justify-center overflow-hidden px-4 pb-4">
                <button type="button" data-viewer-prev aria-label="Foto anterior"
                        class="absolute left-2 z-10 inline-flex h-11 w-11 items-center justify-center rounded-full
                               bg-white/10 text-white transition hover:bg-white/20 sm:left-4 ${single ? 'hidden' : ''}">
                    <i class="bi bi-chevron-left text-xl"></i>
                </button>

                <img data-viewer-image alt="${alt}"
                     class="max-h-full max-w-full object-contain shadow-2xl">

                <button type="button" data-viewer-next aria-label="Foto siguiente"
                        class="absolute right-2 z-10 inline-flex h-11 w-11 items-center justify-center rounded-full
                               bg-white/10 text-white transition hover:bg-white/20 sm:right-4 ${single ? 'hidden' : ''}">
                    <i class="bi bi-chevron-right text-xl"></i>
                </button>
            </div>

            <div data-viewer-strip
                 class="flex shrink-0 justify-start gap-1.5 overflow-x-auto px-4 pb-4 ${single ? 'hidden' : ''}"></div>
        `;
        document.body.appendChild(overlay);

        const image = overlay.querySelector('[data-viewer-image]');
        const counter = overlay.querySelector('[data-viewer-counter]');
        const strip = overlay.querySelector('[data-viewer-strip]');
        const stage = overlay.querySelector('[data-viewer-stage]');

        // Miniatura por foto en la tira inferior; la activa lleva un anillo.
        const thumbs = sources.map((src, i) => {
            const thumb = document.createElement('button');
            thumb.type = 'button';
            thumb.className = 'h-14 w-20 shrink-0 overflow-hidden rounded-md ring-2 ring-transparent transition';
            thumb.innerHTML = `<img src="${src}" alt="" class="h-full w-full object-cover">`;
            thumb.addEventListener('click', () => show(i));
            strip.appendChild(thumb);
            return thumb;
        });

        // Precarga de la foto contigua para que el siguiente salto sea inmediato.
        const preload = (i) => {
            if (i >= 0 && i < sources.length) new Image().src = sources[i];
        };

        function render() {
            image.src = sources[index];
            counter.textContent = `${index + 1} / ${sources.length}`;
            thumbs.forEach((thumb, i) => {
                thumb.classList.toggle('ring-white', i === index);
                thumb.classList.toggle('ring-transparent', i !== index);
            });
            thumbs[index]?.scrollIntoView({ inline: 'nearest', block: 'nearest' });
        }

        function show(i) {
            index = (i + sources.length) % sources.length;
            render();
            preload(index + 1);
            preload(index - 1);
        }

        const open = (i) => {
            show(i);
            overlay.classList.remove('hidden');
            overlay.classList.add('flex');
            // Sin esto la página de atrás sigue desplazándose bajo el visor.
            document.body.classList.add('overflow-hidden');
            overlay.querySelector('[data-viewer-close]').focus();
        };

        const close = () => {
            overlay.classList.add('hidden');
            overlay.classList.remove('flex');
            document.body.classList.remove('overflow-hidden');
        };

        const isOpen = () => !overlay.classList.contains('hidden');

        // Cada foto de la ficha abre el visor en su propia posición.
        root.querySelectorAll('[data-gallery-open]').forEach((trigger) => {
            trigger.addEventListener('click', () => open(Number(trigger.dataset.galleryOpen) || 0));
        });

        overlay.querySelector('[data-viewer-close]').addEventListener('click', close);
        overlay.querySelector('[data-viewer-prev]').addEventListener('click', () => show(index - 1));
        overlay.querySelector('[data-viewer-next]').addEventListener('click', () => show(index + 1));

        // Clic en el fondo (fuera de la imagen y los controles) también cierra.
        stage.addEventListener('click', (e) => {
            if (e.target === stage) close();
        });

        document.addEventListener('keydown', (e) => {
            if (!isOpen()) return;
            if (e.key === 'Escape') close();
            if (e.key === 'ArrowLeft') show(index - 1);
            if (e.key === 'ArrowRight') show(index + 1);
        });

        // Deslizar entre fotos en pantallas táctiles.
        let touchStartX = null;
        stage.addEventListener('touchstart', (e) => {
            touchStartX = e.changedTouches[0].clientX;
        }, { passive: true });
        stage.addEventListener('touchend', (e) => {
            if (touchStartX === null) return;
            const delta = e.changedTouches[0].clientX - touchStartX;
            if (Math.abs(delta) > 40) show(index + (delta < 0 ? 1 : -1));
            touchStartX = null;
        }, { passive: true });
    }
}
