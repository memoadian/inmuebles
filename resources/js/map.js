// Mapas con Leaflet + OpenStreetMap: no requieren API key ni facturación.
// Este entry se carga sólo en las vistas que traen mapa (alta/edición de una
// propiedad y ficha pública), no en todo el sitio.
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import markerIcon from 'leaflet/dist/images/marker-icon.png';
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png';
import markerShadow from 'leaflet/dist/images/marker-shadow.png';

// Leaflet arma las rutas de sus iconos por su cuenta y con un bundler quedan rotas.
delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
    iconUrl: markerIcon,
    iconRetinaUrl: markerIcon2x,
    shadowUrl: markerShadow,
});

// Centro de respaldo cuando la propiedad todavía no tiene coordenadas.
const MEXICO_CENTER = [23.6345, -102.5528];
const TILES = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
const ATTRIBUTION = '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>';

const postJson = async (url, payload) => {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    const response = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrf,
        },
        body: JSON.stringify(payload),
    });

    const data = await response.json();

    if (!response.ok) throw new Error(data.message || 'No se pudo consultar la dirección.');

    return data;
};

const readCoords = (el) => {
    const lat = parseFloat(el.dataset.lat);
    const lng = parseFloat(el.dataset.lng);
    return Number.isFinite(lat) && Number.isFinite(lng) ? [lat, lng] : null;
};

/**
 * Botón de ampliar: en vez de la Fullscreen API (que Safari de iOS no soporta
 * para elementos) se usa una capa fija sobre la página, que funciona igual en
 * todos los navegadores y se cierra con Escape.
 *
 * Al cambiar de tamaño hay que avisarle a Leaflet con invalidateSize(), o el
 * mapa queda con tiles grises, y volver a centrar donde estaba el usuario.
 */
// Ampliado: capa oscura de fondo y el mapa enmarcado dentro, no a sangre.
const EXPANDED_WRAPPER = ['fixed', 'inset-0', 'z-[60]', 'bg-slate-900/70', 'backdrop-blur-sm', 'p-4', 'sm:p-8'];
const EXPANDED_MAP = ['h-full', 'shadow-2xl'];
// El botón se recorre para quedar dentro del marco y no sobre el fondo.
const EXPANDED_BUTTON = ['top-6', 'right-6', 'sm:top-10', 'sm:right-10'];

const enableExpand = (el, map) => {
    const wrapper = el.closest('[data-map-wrapper]');
    const button = wrapper?.querySelector('[data-map-expand]');

    if (!wrapper || !button) return;

    const icon = button.querySelector('[data-map-expand-icon]');
    const label = button.querySelector('[data-map-expand-label]');
    const heightClasses = (el.dataset.height ?? '').split(' ').filter(Boolean);
    let expanded = false;

    const baseButtonClasses = [...button.classList].filter((cls) => /^(top|right)-/.test(cls));

    const render = () => {
        wrapper.classList.toggle('relative', !expanded);
        EXPANDED_WRAPPER.forEach((cls) => wrapper.classList.toggle(cls, expanded));
        heightClasses.forEach((cls) => el.classList.toggle(cls, !expanded));
        EXPANDED_MAP.forEach((cls) => el.classList.toggle(cls, expanded));
        baseButtonClasses.forEach((cls) => button.classList.toggle(cls, !expanded));
        EXPANDED_BUTTON.forEach((cls) => button.classList.toggle(cls, expanded));
        // Sin esto la página de atrás sigue desplazándose bajo el mapa.
        document.body.classList.toggle('overflow-hidden', expanded);

        icon?.classList.toggle('bi-arrows-fullscreen', !expanded);
        icon?.classList.toggle('bi-x-lg', expanded);
        if (label) label.textContent = expanded ? 'Cerrar' : 'Ampliar';
        button.setAttribute(
            'aria-label',
            expanded ? 'Cerrar el mapa ampliado' : 'Ampliar el mapa a pantalla completa',
        );
    };

    const toggle = (next) => {
        const center = map.getCenter();
        expanded = next;
        render();

        // El navegador necesita un cuadro para aplicar el nuevo tamaño.
        requestAnimationFrame(() => {
            map.invalidateSize();
            map.setView(center, map.getZoom());
        });
    };

    button.addEventListener('click', () => toggle(!expanded));

    // Clic en el fondo oscuro (fuera del marco) también cierra.
    wrapper.addEventListener('click', (e) => {
        if (expanded && e.target === wrapper) toggle(false);
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && expanded) toggle(false);
    });
};

/**
 * Buscador de direcciones dentro del mapa.
 *
 * Busca al enviar (Enter o botón), no mientras se teclea: el autocompletado
 * dispararía una petición por pulsación y Nominatim permite ~1 por segundo
 * para toda la aplicación. Las consultas van a nuestro endpoint, que es el
 * que tiene el User-Agent, la caché y el limitador.
 */
const addSearchControl = (map, { url, onPick }) => {
    const Control = L.Control.extend({
        options: { position: 'topleft' },

        onAdd() {
            const container = L.DomUtil.create('div', 'leaflet-bar');
            container.style.background = 'transparent';
            container.style.border = 'none';
            container.innerHTML = `
                <form class="flex items-stretch overflow-hidden rounded-lg border border-slate-300 bg-white shadow-sm">
                    <input type="search" placeholder="Buscar dirección o lugar…"
                           aria-label="Buscar una dirección en el mapa"
                           class="w-56 px-2.5 py-1.5 text-xs text-slate-700 outline-none sm:w-72">
                    <button type="submit" aria-label="Buscar"
                            class="border-l border-slate-200 px-2.5 text-slate-600 hover:bg-slate-50 disabled:opacity-50">
                        <i class="bi bi-search text-xs"></i>
                    </button>
                </form>
                <ul class="mt-1 hidden max-h-56 w-56 overflow-y-auto rounded-lg border border-slate-200
                           bg-white text-xs shadow-lg sm:w-72"></ul>
            `;

            // Sin esto, escribir o hacer scroll dentro del control mueve el mapa.
            L.DomEvent.disableClickPropagation(container);
            L.DomEvent.disableScrollPropagation(container);

            const form = container.querySelector('form');
            const input = container.querySelector('input');
            const button = container.querySelector('button');
            const list = container.querySelector('ul');

            const showMessage = (text) => {
                list.innerHTML = `<li class="px-2.5 py-2 text-slate-500">${text}</li>`;
                list.classList.remove('hidden');
            };

            const showResults = (results) => {
                list.innerHTML = '';

                results.forEach((result) => {
                    const item = L.DomUtil.create('li', '', list);
                    const option = L.DomUtil.create('button', '', item);
                    option.type = 'button';
                    option.className =
                        'block w-full px-2.5 py-2 text-left text-slate-700 hover:bg-slate-50 focus:bg-slate-50 focus:outline-none';
                    option.textContent = result.display_name;
                    option.addEventListener('click', () => {
                        onPick(result);
                        list.classList.add('hidden');
                    });
                });

                list.classList.remove('hidden');
            };

            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                const address = input.value.trim();

                if (address.length < 5) {
                    showMessage('Escribe al menos 5 caracteres.');
                    return;
                }

                button.disabled = true;
                showMessage('Buscando…');

                try {
                    const data = await postJson(url, { address, limit: 5 });
                    showResults(data.results ?? []);
                } catch (error) {
                    showMessage(error.message);
                } finally {
                    button.disabled = false;
                }
            });

            input.addEventListener('search', () => {
                if (!input.value) list.classList.add('hidden');
            });

            return container;
        },
    });

    map.addControl(new Control());
};

const baseMap = (el, { center, zoom, interactive = true }) => {
    const map = L.map(el, {
        center,
        zoom,
        scrollWheelZoom: interactive,
        dragging: interactive,
        zoomControl: interactive,
    });
    L.tileLayer(TILES, { attribution: ATTRIBUTION, maxZoom: 19 }).addTo(map);
    return map;
};

// ---------------------------------------------------------------------------
// Selector de ubicación del formulario de propiedades
// ---------------------------------------------------------------------------
const pickerEl = document.getElementById('locationMap');

if (pickerEl) {
    const latInput = document.getElementById('latitude');
    const lngInput = document.getElementById('longitude');
    const postalInput = document.getElementById('postal_code');
    const geocodeBtn = document.getElementById('geocodeBtn');
    const status = document.getElementById('geocodeStatus');
    const saved = readCoords(pickerEl);

    const map = baseMap(pickerEl, {
        center: saved ?? MEXICO_CENTER,
        zoom: saved ? 16 : 5,
    });

    const marker = L.marker(saved ?? MEXICO_CENTER, { draggable: true, opacity: saved ? 1 : 0.45 }).addTo(map);

    enableExpand(pickerEl, map);

    const setCoords = ([lat, lng]) => {
        marker.setLatLng([lat, lng]).setOpacity(1);
        if (latInput) latInput.value = lat.toFixed(7);
        if (lngInput) lngInput.value = lng.toFixed(7);
    };

    const setStatus = (text) => {
        if (status) status.textContent = text;
    };

    addSearchControl(map, {
        url: geocodeBtn?.dataset.url,
        onPick: (result) => {
            setCoords([result.latitude, result.longitude]);
            map.setView([result.latitude, result.longitude], 17);
            if (postalInput && !postalInput.value.trim() && result.postal_code) {
                postalInput.value = result.postal_code;
            }
            setStatus('Ubicación tomada del buscador. Ajusta el pin si hace falta.');
        },
    });

    // Al soltar el pin se sugiere el código postal, pero sólo si está vacío:
    // lo que el asesor escribió a mano nunca se pisa.
    const suggestPostalCode = async ([lat, lng]) => {
        if (!postalInput || postalInput.value.trim()) return;

        try {
            const data = await postJson(geocodeBtn?.dataset.reverseUrl, { latitude: lat, longitude: lng });
            if (data.postal_code) {
                postalInput.value = data.postal_code;
                setStatus(`Código postal sugerido: ${data.postal_code}.`);
            }
        } catch {
            // Sugerir el CP es un extra; si falla, el pin ya quedó bien puesto.
        }
    };

    const moveTo = (coords, { fly = true } = {}) => {
        setCoords(coords);
        if (fly) map.setView(coords, Math.max(map.getZoom(), 16));
        suggestPostalCode(coords);
    };

    marker.on('dragend', () => {
        const { lat, lng } = marker.getLatLng();
        moveTo([lat, lng], { fly: false });
    });

    map.on('click', (e) => moveTo([e.latlng.lat, e.latlng.lng], { fly: false }));

    // Escribir latitud/longitud a mano sigue funcionando: el pin las sigue.
    [latInput, lngInput].forEach((input) => {
        input?.addEventListener('change', () => {
            const lat = parseFloat(latInput.value);
            const lng = parseFloat(lngInput.value);
            if (Number.isFinite(lat) && Number.isFinite(lng)) {
                marker.setLatLng([lat, lng]).setOpacity(1);
                map.setView([lat, lng], Math.max(map.getZoom(), 16));
            }
        });
    });

    geocodeBtn?.addEventListener('click', async () => {
        const stateSelect = document.getElementById('state_id');
        const address = [
            document.getElementById('street')?.value,
            document.getElementById('ext_number')?.value,
            postalInput?.value,
            stateSelect?.value ? stateSelect.options[stateSelect.selectedIndex]?.textContent?.trim() : null,
            'México',
        ]
            .map((part) => part?.toString().trim())
            .filter(Boolean)
            .join(', ');

        if (address.replace(/[, ]/g, '').length < 8) {
            setStatus('Escribe al menos calle y estado para buscar.');
            return;
        }

        geocodeBtn.disabled = true;
        setStatus('Buscando dirección…');

        try {
            const { results } = await postJson(geocodeBtn.dataset.url, { address });
            const [best] = results;
            setCoords([best.latitude, best.longitude]);
            map.setView([best.latitude, best.longitude], 17);
            setStatus(`Encontrado: ${best.display_name}. Ajusta el pin si hace falta.`);
        } catch (error) {
            setStatus(error.message);
        } finally {
            geocodeBtn.disabled = false;
        }
    });
}

// ---------------------------------------------------------------------------
// Mapa de la ficha pública: zona aproximada, no el domicilio exacto
// ---------------------------------------------------------------------------
const publicEl = document.getElementById('propertyMap');

if (publicEl) {
    const coords = readCoords(publicEl);

    if (coords) {
        const map = baseMap(publicEl, { center: coords, zoom: 15 });

        L.circle(coords, {
            radius: 300,
            color: '#275342',
            weight: 2,
            fillColor: '#3f7d63',
            fillOpacity: 0.18,
        }).addTo(map);

        enableExpand(publicEl, map);
    }
}
