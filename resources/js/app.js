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

// Autocompletar el formulario de propiedades a partir de texto libre (Groq + Llama 3.1 8B).
const aiExtractBtn = document.getElementById('aiExtractBtn');
const aiExtractText = document.getElementById('aiExtractText');
const aiExtractStatus = document.getElementById('aiExtractStatus');

if (aiExtractBtn && aiExtractText) {
    const normalize = (value) => value.trim().toLowerCase();

    const setSelectByText = (select, text) => {
        if (!select || !text) return;
        const target = normalize(text);
        const option = Array.from(select.options).find((o) => normalize(o.textContent) === target);
        if (option) select.value = option.value;
    };

    const setSelectByValue = (select, value) => {
        if (!select || !value) return;
        const exists = Array.from(select.options).some((o) => o.value === value);
        if (exists) select.value = value;
    };

    const setField = (id, value) => {
        if (value === undefined || value === null || value === '') return;
        const el = document.getElementById(id);
        if (el) el.value = value;
    };

    const applyExtraction = (data) => {
        setField('title', data.title);
        setField('description', data.description);
        setField('price', data.price);
        setField('maintenance_fee', data.maintenance_fee);
        setField('bedrooms', data.bedrooms);
        setField('bathrooms', data.bathrooms);
        setField('half_bathrooms', data.half_bathrooms);
        setField('parking_spaces', data.parking_spaces);
        setField('land_area', data.land_area);
        setField('built_area', data.built_area);
        setField('floors', data.floors);
        setField('age_years', data.age_years);
        setField('street', data.street);
        setField('ext_number', data.ext_number);
        setField('int_number', data.int_number);
        setField('postal_code', data.postal_code);

        setSelectByValue(document.getElementById('operation'), data.operation);
        setSelectByValue(document.getElementById('currency'), data.currency);
        setSelectByText(document.getElementById('property_type_id'), data.property_type);
        setSelectByText(document.getElementById('state_id'), data.state);

        if (Array.isArray(data.features) && data.features.length) {
            const wanted = data.features.map(normalize);
            document.querySelectorAll('input[name="features[]"]').forEach((checkbox) => {
                const label = checkbox.closest('label')?.querySelector('span');
                if (label && wanted.includes(normalize(label.textContent))) {
                    checkbox.checked = true;
                }
            });
        }
    };

    aiExtractBtn.addEventListener('click', async () => {
        const text = aiExtractText.value.trim();
        if (!text) {
            aiExtractStatus.textContent = 'Pega primero una descripción.';
            return;
        }

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        aiExtractBtn.disabled = true;
        aiExtractStatus.textContent = 'Analizando con IA…';

        try {
            const response = await fetch(aiExtractBtn.dataset.url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken ?? '',
                },
                body: JSON.stringify({ text }),
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'No se pudo autocompletar.');
            }

            applyExtraction(data);
            aiExtractStatus.textContent = 'Listo. Revisa los campos antes de guardar.';
        } catch (error) {
            aiExtractStatus.textContent = error.message || 'Error al conectar con la IA.';
        } finally {
            aiExtractBtn.disabled = false;
        }
    });
}

// Sugerir títulos llamativos a partir de los campos YA llenados del formulario
// (no del texto libre de "Autocompletar con IA" — son dos features separadas).
const aiSuggestTitlesBtn = document.getElementById('aiSuggestTitlesBtn');
const aiSuggestTitlesStatus = document.getElementById('aiSuggestTitlesStatus');
const aiSuggestTitlesList = document.getElementById('aiSuggestTitlesList');

if (aiSuggestTitlesBtn) {
    const fieldValue = (id) => document.getElementById(id)?.value?.trim() || null;

    const selectedText = (id) => {
        const select = document.getElementById(id);
        if (!select || !select.value) return null;
        return select.options[select.selectedIndex]?.textContent?.trim() || null;
    };

    const selectedFeatureNames = () => {
        return Array.from(document.querySelectorAll('input[name="features[]"]:checked'))
            .map((checkbox) => checkbox.closest('label')?.querySelector('span')?.textContent?.trim())
            .filter(Boolean);
    };

    const buildPayload = () => ({
        property_type: selectedText('property_type_id'),
        operation: fieldValue('operation'),
        price: fieldValue('price'),
        currency: fieldValue('currency'),
        state: selectedText('state_id'),
        bedrooms: fieldValue('bedrooms'),
        bathrooms: fieldValue('bathrooms'),
        built_area: fieldValue('built_area'),
        land_area: fieldValue('land_area'),
        features: selectedFeatureNames(),
    });

    const renderTitles = (titles) => {
        aiSuggestTitlesList.innerHTML = '';
        titles.forEach((title) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'text-left rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700 hover:border-slate-400 hover:bg-white';
            btn.textContent = title;
            btn.addEventListener('click', () => {
                document.getElementById('title').value = title;
                aiSuggestTitlesList.innerHTML = '';
                aiSuggestTitlesStatus.textContent = '';
            });
            aiSuggestTitlesList.appendChild(btn);
        });
    };

    aiSuggestTitlesBtn.addEventListener('click', async () => {
        const payload = buildPayload();

        if (!payload.property_type || !payload.operation) {
            aiSuggestTitlesStatus.textContent = 'Llena al menos el tipo de inmueble y la operación primero.';
            return;
        }

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        aiSuggestTitlesBtn.disabled = true;
        aiSuggestTitlesList.innerHTML = '';
        aiSuggestTitlesStatus.textContent = 'Generando sugerencias…';

        try {
            const response = await fetch(aiSuggestTitlesBtn.dataset.url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken ?? '',
                },
                body: JSON.stringify(payload),
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'No se pudieron generar títulos.');
            }

            renderTitles(data.titles ?? []);
            aiSuggestTitlesStatus.textContent = 'Elige un título o sigue editando el tuyo.';
        } catch (error) {
            aiSuggestTitlesStatus.textContent = error.message || 'Error al conectar con la IA.';
        } finally {
            aiSuggestTitlesBtn.disabled = false;
        }
    });
}
