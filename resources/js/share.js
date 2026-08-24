// Compartir una propiedad: hoja nativa donde exista, enlaces directos donde no.
document.querySelectorAll('[data-share]').forEach((root) => {
    const { url, title } = root.dataset;
    const nativeBtn = root.querySelector('[data-share-native]');
    const copyBtn = root.querySelector('[data-share-copy]');
    const status = root.querySelector('[data-share-status]');

    const say = (text) => {
        if (!status) return;
        status.textContent = text;
        setTimeout(() => (status.textContent = ''), 2500);
    };

    // navigator.share sólo existe en contexto seguro (https o localhost).
    if (navigator.share && nativeBtn) {
        nativeBtn.hidden = false;
        nativeBtn.addEventListener('click', async () => {
            try {
                await navigator.share({ title, text: title, url });
            } catch (error) {
                // Cancelar la hoja de compartir lanza AbortError: no es un fallo.
                if (error.name !== 'AbortError') say('No se pudo compartir.');
            }
        });
    }

    copyBtn?.addEventListener('click', async () => {
        const icon = copyBtn.querySelector('[data-share-copy-icon]');

        try {
            await navigator.clipboard.writeText(url);
        } catch {
            // clipboard.writeText no existe sin https; el respaldo es seleccionar.
            const field = document.createElement('input');
            field.value = url;
            document.body.appendChild(field);
            field.select();
            document.execCommand('copy');
            field.remove();
        }

        icon?.classList.replace('bi-link-45deg', 'bi-check-lg');
        say('Enlace copiado.');
        setTimeout(() => icon?.classList.replace('bi-check-lg', 'bi-link-45deg'), 2500);
    });
});
