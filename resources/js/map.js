// Mapas con Mapbox GL JS. Este entry se carga sólo en las vistas que traen
// mapa (alta/edición de una propiedad, ficha pública y catálogo).
//
// Ojo al orden de las coordenadas: Mapbox trabaja en [lng, lat], al revés de
// como se leen y de como se guardan en la base.
import mapboxgl from 'mapbox-gl';
import 'mapbox-gl/dist/mapbox-gl.css';

const meta = (name) => document.querySelector(`meta[name="${name}"]`)?.content?.trim() ?? '';

const TOKEN = meta('mapbox-token');
const STYLE = meta('mapbox-style') || 'mapbox://styles/mapbox/light-v11';
const MEXICO_CENTER = [-102.5528, 23.6345];

mapboxgl.accessToken = TOKEN;

/**
 * Sin token no hay mapa: se avisa dentro del contenedor en vez de dejar un
 * hueco gris o reventar en consola.
 */
const missingToken = (el) => {
    if (TOKEN) return false;

    el.innerHTML = `<div style="height:100%;display:flex;align-items:center;justify-content:center;
        padding:24px;text-align:center;font:400 13px/1.5 system-ui,sans-serif;color:#78716c;">
        El mapa no está configurado todavía: falta MAPBOX_TOKEN en el archivo .env.
    </div>`;
    return true;
};

const readCoords = (el) => {
    const lat = parseFloat(el.dataset.lat);
    const lng = parseFloat(el.dataset.lng);
    return Number.isFinite(lat) && Number.isFinite(lng) ? [lng, lat] : null;
};

const baseMap = (el, { center, zoom, interactive = true }) => {
    const map = new mapboxgl.Map({
        container: el,
        style: STYLE,
        center,
        zoom,
        interactive,
        cooperativeGestures: false,
        attributionControl: true,
    });

    if (interactive) {
        map.addControl(new mapboxgl.NavigationControl({ showCompass: false }), 'bottom-right');
    }

    return map;
};

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

/**
 * Botón de ampliar: en vez de la Fullscreen API (que Safari de iOS no soporta
 * para elementos) se usa una capa fija sobre la página, que funciona igual en
 * todos los navegadores y se cierra con Escape.
 */
const EXPANDED_WRAPPER = ['fixed', 'inset-0', 'z-[60]', 'bg-slate-900/70', 'backdrop-blur-sm', 'p-4', 'sm:p-8'];
const EXPANDED_MAP = ['h-full', 'shadow-2xl'];
const EXPANDED_BUTTON = ['top-6', 'right-6', 'sm:top-10', 'sm:right-10'];

const enableExpand = (el, map) => {
    const wrapper = el.closest('[data-map-wrapper]');
    const button = wrapper?.querySelector('[data-map-expand]');

    if (!wrapper || !button) return;

    const icon = button.querySelector('[data-map-expand-icon]');
    const label = button.querySelector('[data-map-expand-label]');
    const heightClasses = (el.dataset.height ?? '').split(' ').filter(Boolean);
    const baseButtonClasses = [...button.classList].filter((cls) => /^(top|right)-/.test(cls));
    let expanded = false;

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
        expanded = next;
        render();
        // Mapbox mide su lienzo al crearse: hay que avisarle del nuevo tamaño.
        requestAnimationFrame(() => map.resize());
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
 * Buscador de direcciones dentro del mapa, como control propio de Mapbox.
 *
 * Busca al enviar (Enter o botón), no mientras se teclea: el autocompletado
 * dispararía una petición por pulsación. Las consultas van a nuestro endpoint,
 * que es el que tiene el User-Agent, la caché y el limitador.
 */
class AddressSearchControl {
    constructor({ url, onPick }) {
        this.url = url;
        this.onPick = onPick;
    }

    onAdd() {
        const container = document.createElement('div');
        container.className = 'mapboxgl-ctrl mapboxgl-ctrl-group';
        container.style.background = 'transparent';
        container.style.boxShadow = 'none';
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
        ['mousedown', 'dblclick', 'wheel', 'touchstart'].forEach((evt) => {
            container.addEventListener(evt, (e) => e.stopPropagation());
        });

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
                const item = document.createElement('li');
                const option = document.createElement('button');
                option.type = 'button';
                option.className =
                    'block w-full px-2.5 py-2 text-left text-slate-700 hover:bg-slate-50 focus:bg-slate-50 focus:outline-none';
                option.textContent = result.display_name;
                option.addEventListener('click', () => {
                    this.onPick(result);
                    list.classList.add('hidden');
                });
                item.appendChild(option);
                list.appendChild(item);
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
                const data = await postJson(this.url, { address, limit: 5 });
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

        this.container = container;
        return container;
    }

    onRemove() {
        this.container?.remove();
    }
}

// ---------------------------------------------------------------------------
// Selector de ubicación del formulario de propiedades
// ---------------------------------------------------------------------------
const pickerEl = document.getElementById('locationMap');

if (pickerEl && !missingToken(pickerEl)) {
    const latInput = document.getElementById('latitude');
    const lngInput = document.getElementById('longitude');
    const postalInput = document.getElementById('postal_code');
    const geocodeBtn = document.getElementById('geocodeBtn');
    const status = document.getElementById('geocodeStatus');
    const saved = readCoords(pickerEl);

    const map = baseMap(pickerEl, {
        center: saved ?? MEXICO_CENTER,
        zoom: saved ? 16 : 4,
    });

    const marker = new mapboxgl.Marker({ draggable: true, color: '#1b3ac4' })
        .setLngLat(saved ?? MEXICO_CENTER)
        .addTo(map);

    if (!saved) marker.getElement().style.opacity = '0.45';

    const setCoords = ([lng, lat]) => {
        marker.setLngLat([lng, lat]);
        marker.getElement().style.opacity = '1';
        if (latInput) latInput.value = lat.toFixed(7);
        if (lngInput) lngInput.value = lng.toFixed(7);
    };

    const setStatus = (text) => {
        if (status) status.textContent = text;
    };

    // Al soltar el pin se sugiere el código postal, pero sólo si está vacío:
    // lo que el asesor escribió a mano nunca se pisa.
    const suggestPostalCode = async ([lng, lat]) => {
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
        if (fly) map.easeTo({ center: coords, zoom: Math.max(map.getZoom(), 16) });
        suggestPostalCode(coords);
    };

    marker.on('dragend', () => {
        const { lng, lat } = marker.getLngLat();
        moveTo([lng, lat], { fly: false });
    });

    map.on('click', (e) => moveTo([e.lngLat.lng, e.lngLat.lat], { fly: false }));

    // Escribir latitud/longitud a mano sigue funcionando: el pin las sigue.
    [latInput, lngInput].forEach((input) => {
        input?.addEventListener('change', () => {
            const lat = parseFloat(latInput.value);
            const lng = parseFloat(lngInput.value);
            if (Number.isFinite(lat) && Number.isFinite(lng)) {
                marker.setLngLat([lng, lat]);
                marker.getElement().style.opacity = '1';
                map.easeTo({ center: [lng, lat], zoom: Math.max(map.getZoom(), 16) });
            }
        });
    });

    enableExpand(pickerEl, map);

    map.addControl(
        new AddressSearchControl({
            url: geocodeBtn?.dataset.url,
            onPick: (result) => {
                setCoords([result.longitude, result.latitude]);
                map.easeTo({ center: [result.longitude, result.latitude], zoom: 17 });
                if (postalInput && !postalInput.value.trim() && result.postal_code) {
                    postalInput.value = result.postal_code;
                }
                setStatus('Ubicación tomada del buscador. Ajusta el pin si hace falta.');
            },
        }),
        'top-left',
    );

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
            setCoords([best.longitude, best.latitude]);
            map.easeTo({ center: [best.longitude, best.latitude], zoom: 17 });
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

if (publicEl && !missingToken(publicEl)) {
    const coords = readCoords(publicEl);

    if (coords) {
        const map = baseMap(publicEl, { center: coords, zoom: 14.5 });

        // Mapbox no trae círculos en metros, así que se dibuja el polígono.
        const circle = ([lng, lat], meters, steps = 72) => {
            const latRadius = meters / 110574;
            const lngRadius = meters / (111320 * Math.cos((lat * Math.PI) / 180));
            const ring = Array.from({ length: steps + 1 }, (_, i) => {
                const angle = (i / steps) * 2 * Math.PI;
                return [lng + lngRadius * Math.cos(angle), lat + latRadius * Math.sin(angle)];
            });

            return { type: 'Feature', geometry: { type: 'Polygon', coordinates: [ring] } };
        };

        map.on('load', () => {
            map.addSource('zona', { type: 'geojson', data: circle(coords, 300) });
            map.addLayer({
                id: 'zona-fill',
                type: 'fill',
                source: 'zona',
                paint: { 'fill-color': '#2c5cf6', 'fill-opacity': 0.18 },
            });
            map.addLayer({
                id: 'zona-line',
                type: 'line',
                source: 'zona',
                paint: { 'line-color': '#1b3ac4', 'line-width': 2 },
            });
        });

        enableExpand(publicEl, map);
    }
}

// ---------------------------------------------------------------------------
// Mapa del catálogo: pines con precio sincronizados con la lista de resultados
// ---------------------------------------------------------------------------
const catalogEl = document.getElementById('catalogMap');

if (catalogEl && !missingToken(catalogEl)) {
    const raw = document.querySelector('[data-catalog-points]')?.textContent;
    let points = [];

    try {
        points = JSON.parse(raw ?? '[]');
    } catch {
        points = [];
    }

    const map = baseMap(catalogEl, { center: MEXICO_CENTER, zoom: 4 });
    const pins = new Map();

    const paint = (el, active) => {
        el.style.background = active ? '#1b3ac4' : '#ffffff';
        el.style.color = active ? '#ffffff' : '#1c1917';
        el.style.zIndex = active ? '10' : '1';
    };

    const row = (id) => document.querySelector(`[data-property="${id}"]`);

    const highlight = (id, on) => {
        const el = pins.get(id);
        if (el) paint(el, on);
        row(id)?.setAttribute('data-active', on ? 'true' : 'false');
    };

    points.forEach((point) => {
        const el = document.createElement('button');
        el.type = 'button';
        el.textContent = point.price;
        el.style.cssText = `white-space:nowrap;border:0;border-radius:999px;padding:5px 11px;cursor:pointer;
            font:600 12px/1 'Inter',system-ui,sans-serif;box-shadow:0 3px 8px rgba(28,25,23,.25);`;
        paint(el, false);

        const popup = new mapboxgl.Popup({ offset: 14, closeButton: false }).setHTML(
            `<a href="${point.url}" style="display:block;width:200px;text-decoration:none;color:inherit;">
                ${point.image ? `<img src="${point.image}" alt="" style="width:100%;height:96px;object-fit:cover;border-radius:6px;">` : ''}
                <span style="display:block;padding:8px 2px 2px;font:700 15px/1.2 'Inter',system-ui,sans-serif;">${point.price}</span>
                <span style="display:block;padding:0 2px 4px;font:400 12px/1.4 'Inter',system-ui,sans-serif;color:#57534e;">${point.meta}</span>
             </a>`,
        );

        new mapboxgl.Marker({ element: el }).setLngLat([point.lng, point.lat]).setPopup(popup).addTo(map);

        el.addEventListener('mouseenter', () => highlight(point.id, true));
        el.addEventListener('mouseleave', () => highlight(point.id, false));

        pins.set(point.id, el);
    });

    if (points.length) {
        const bounds = points.reduce(
            (acc, p) => acc.extend([p.lng, p.lat]),
            new mapboxgl.LngLatBounds([points[0].lng, points[0].lat], [points[0].lng, points[0].lat]),
        );
        map.fitBounds(bounds, { padding: 70, maxZoom: 15, duration: 0 });
    }

    // Pasar el cursor por una fila resalta su pin, y al revés.
    document.querySelectorAll('[data-property]').forEach((el) => {
        const id = Number(el.dataset.property);
        el.addEventListener('mouseenter', () => highlight(id, true));
        el.addEventListener('mouseleave', () => highlight(id, false));
    });

    // "Buscar en esta zona": el recuadro visible se manda como filtro.
    document.querySelector('[data-map-search]')?.addEventListener('click', () => {
        const form = document.querySelector('[data-catalog-form]');
        const input = form?.querySelector('[data-bounds-input]');
        if (!form || !input) return;

        const b = map.getBounds();
        input.value = [b.getSouth(), b.getWest(), b.getNorth(), b.getEast()]
            .map((n) => n.toFixed(6))
            .join(',');
        form.submit();
    });
}

// La barra de filtros cambia de alto según cuánto se envuelva, así que el mapa
// pegajoso toma su offset de la medida real en vez de un número fijo.
const catalogBar = document.querySelector('[data-catalog-form]');

if (catalogBar) {
    const syncBarHeight = () =>
        document.documentElement.style.setProperty('--catalog-bar', `${catalogBar.offsetHeight}px`);

    syncBarHeight();
    new ResizeObserver(syncBarHeight).observe(catalogBar);
}

// Selects que filtran al cambiar: menos clics que "elegir y luego Buscar".
document.querySelectorAll('[data-autosubmit]').forEach((select) => {
    select.addEventListener('change', () => select.closest('form')?.submit());
});
