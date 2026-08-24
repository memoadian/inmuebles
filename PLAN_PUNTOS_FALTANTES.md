# Plan — Puntos faltantes (UBIQA)

Respuesta al PDF *"UBIQA PUNTOS FALTANTES"* (15 puntos del cliente + recomendaciones
de Gemini). Complementa [PLAN.md](PLAN.md), que describe la fase 1 ya construida.

**Base actual:** Laravel 12, Blade + Tailwind 4, MySQL, R2 para imágenes,
`spatie/laravel-permission`, extracción con IA (Groq) en el alta de propiedades.

> Nota: entre los puntos 6 y 7 el PDF trae una frase suelta que no corresponde al
> documento; se ignoró.

---

## 1. Resumen — qué ya existe y qué falta

| # | Punto del cliente | Estado hoy | Acción |
|---|---|---|---|
| 1 | Amenidades (16 nuevas) | `features` existe pero es una lista plana de 15 | Agrupar por categoría + seed nuevo |
| 2 | Recreación (8 nuevas) | — | Nueva categoría en `features` |
| 3 | Mapa para ubicar la propiedad | `latitude`/`longitude` como inputs numéricos a mano | Mapa Leaflet con marcador arrastrable + geocoding |
| 4 | Propiedad en exclusiva sí/no | — | Campo `is_exclusive` |
| 5 | Comisión de renta | — | Campo `rent_commission` (enum) |
| 6 | Comisión de venta 1–6 % | — | Campo `sale_commission_percent` |
| 7 | Condición de la propiedad | — | Campo `condition` (enum) |
| 8 | Niveles de la propiedad | `floors` ya existe | Sólo re-etiquetar y exponer |
| 9 | Piso en el que se encuentra | — | Campo `floor_number` |
| 10 | Orientación | — | Campo `orientation` (enum) |
| 11 | Interior / Exterior | — | Campo `position` (enum) |
| 12 | Años de la propiedad | `age_years` ya existe | Mostrarlo en la ficha pública |
| 13 | Fecha de creación | `created_at` ya existe | Mostrarlo en panel y ficha |
| 14 | Descargar propiedad en PDF | ✅ hecho | Ficha técnica con dompdf |
| 15 | Compartir la propiedad | ✅ hecho | Botones de compartir + Web Share API |

De los 15 puntos, **3 ya están en la base de datos** (8, 12, 13) y sólo hay que
mostrarlos; 8 son campos nuevos que caben en **una sola migración**; los 4
restantes (1–2, 3, 14, 15) son features con trabajo de front.

---

## 2. Campos nuevos — una migración

`database/migrations/xxxx_add_listing_details_to_properties_table.php`

```php
// Comercial (visible sólo para el equipo, nunca en el catálogo público)
$table->boolean('is_exclusive')->default(false)->after('is_featured');
$table->enum('rent_commission', ['half_month', 'one_month', 'two_three_months'])
      ->nullable()->after('is_exclusive');
$table->decimal('sale_commission_percent', 4, 2)->nullable()->after('rent_commission');

// Ficha técnica (público)
$table->enum('condition', ['nueva', 'excelente', 'buena', 'regular'])
      ->nullable()->after('age_years');
$table->unsignedSmallInteger('floor_number')->nullable()->after('floors'); // punto 9
$table->enum('orientation', ['norte','sur','oriente','poniente',
      'noreste','noroeste','sureste','suroeste'])->nullable()->after('condition');
$table->enum('position', ['interior', 'exterior'])->nullable()->after('orientation');

// Desglose de costos (recomendación C de Gemini)
$table->decimal('property_tax_estimate', 10, 2)->nullable()->after('maintenance_fee');
$table->decimal('services_estimate', 10, 2)->nullable()->after('property_tax_estimate');

$table->index('is_exclusive');
```

Tocar en el mismo paso:

- `app/Models/Property.php` → `$fillable` y `casts` (`is_exclusive` bool,
  decimales), más accesores de etiqueta: `condition_label`, `orientation_label`,
  `rent_commission_label`.
- `app/Http/Requests/PropertyRequest.php` → reglas
  (`sale_commission_percent` → `numeric|between:1,6`;
  `rent_commission` → `required_if:operation,rent,both` o `nullable` según se decida).
- `resources/views/properties/_form.blade.php` → nueva sección **"Datos comerciales"**
  (puntos 4, 5, 6) visible sólo con permiso, y los campos 7/9/10/11 dentro de
  "Características".
- `resources/views/public/show.blade.php` → tabla de ficha técnica con condición,
  niveles, piso, orientación, interior/exterior, antigüedad y fecha de publicación.
- `app/Services/PropertyAiExtractionService.php` → agregar los campos nuevos al
  esquema que se le pide al modelo, para que la IA también los llene desde un texto.

**Decisiones a confirmar con el cliente**

1. Comisiones (5 y 6): asumimos que son **información interna del asesor** y no se
   publican. Se muestran sólo a quien tenga un permiso nuevo `properties.view-commission`.
2. Orientación (10): el PDF dice "oriente poniente este oeste", pero oriente = este y
   poniente = oeste. Se propone el set completo de 8 puntos cardinales.
3. Puntos 8 y 9 se confunden fácil: `floors` = "niveles que tiene la propiedad",
   `floor_number` = "piso en el que se ubica" (aplica a depa/oficina, no a casa).

---

## 3. Amenidades y Recreación (puntos 1 y 2)

Hoy `features` es un catálogo plano. Se le agrega categoría y orden:

```php
// add_group_to_features_table
$table->string('group', 40)->default('amenidad')->after('name'); // amenidad|recreacion|caracteristica
$table->unsignedSmallInteger('order')->default(0)->after('icon');
```

`FeatureSeeder` crece con las listas del PDF:

- **amenidad**: estacionamiento para visitas, facilidad para estacionarse,
  estacionamiento techado, jardín, terraza, muelle de carga, parrilla, patio,
  acceso para adultos mayores*, cocina equipada, vestidor
  (+ las 15 actuales: alberca, roof garden, seguridad 24h, elevador, gimnasio,
  amueblado, acepta mascotas, cisterna, aire acondicionado, calentador solar,
  cocina integral, cuarto de servicio, vigilancia en caseta…).
- **recreacion**: área de juegos infantiles, cancha de pádel, cancha de tenis,
  cine, fogatero, jacuzzi, ludoteca, salón de usos múltiples.
- **caracteristica**: dos plantas, una sola planta, estudio, fraccionamiento
  privado, penthouse.

\* El PDF dice "acceso para adultos"; se interpreta como **acceso para adultos
mayores / accesibilidad**. Confirmar.

> "Dos plantas / una sola planta" se puede derivar de `floors`, pero se dejan como
> etiquetas porque así las busca la gente y así las tienen las plataformas rivales.
> El formulario puede pre-marcarlas según `floors` para no capturar dos veces.

Cambios de vista: el bloque "Amenidades" del formulario y la ficha pública se
renderizan agrupados (`$features->groupBy('group')`), y el CRUD de features
(`features/_form.blade.php`, `index`) gana el selector de categoría.

Filtro público por amenidades: `?features[]=id` con `whereHas` — un panel
colapsable en `public/index.blade.php`.

---

## 4. Mapa de ubicación (punto 3)

Lo más útil de toda la lista: hoy el asesor teclea latitud y longitud a mano.

**Recomendación: Leaflet + OpenStreetMap** (sin API key, sin tarjeta) para el
selector del panel y para el mapa de la ficha pública. Google Maps queda para
después, cuando haga falta el cálculo de tiempos de traslado (sección 7C), que sí
exige facturación.

- Panel (`properties/_form.blade.php`): mapa con marcador arrastrable; al mover el
  pin se llenan `latitude`/`longitude` (que pasan a `hidden`). Botón
  **"Buscar por dirección"** que geocodifica calle + número + CP + estado con
  Nominatim, y el camino inverso: mover el pin sugiere el CP/colonia.
- Público (`public/show.blade.php`): mapa estático centrado en el pin. Para no
  exponer el domicilio exacto se dibuja un **círculo de ~300 m** en vez del marcador
  cuando la propiedad está publicada (práctica estándar del sector).
- `public/index.blade.php`: fase posterior, vista de resultados en mapa.

Costo: 0. Nominatim pide un `User-Agent` propio y máximo 1 req/s → llamarlo desde
el backend con caché por dirección, no desde el navegador del asesor.

---

## 5. Ficha en PDF (punto 14)

- Paquete: `barryvdh/laravel-dompdf`.
- Ruta pública: `GET /propiedades/{property:slug}/pdf` → `PublicPropertyController@pdf`.
- Vista dedicada `resources/views/public/pdf.blade.php`: portada con la foto de
  portada, precio, ficha técnica completa, hasta 6 fotos, mapa estático, y datos
  del asesor (nombre, teléfono, correo) — sin comisiones ni datos internos.
- Las imágenes vienen de R2 por URL pública; hay que habilitar
  `isRemoteEnabled` en la config de dompdf y cachear las descargas para que la
  generación no tarde.
- Botón "Descargar ficha PDF" en `public/show.blade.php` y en el panel.

---

## 6. Compartir (punto 15)

Las meta OG ya existen en `layouts/public.blade.php`; falta la parte visible:

- Componente `x-share-buttons` con WhatsApp, Facebook, X, correo y **copiar enlace**.
- `navigator.share()` en móvil cuando esté disponible, con los botones como respaldo.
- Complemento natural: **botón de contacto por WhatsApp** con mensaje prellenado
  (`https://wa.me/{{ $property->user->phone }}?text=…`) tanto en la ficha como en
  cada tarjeta del catálogo — es la recomendación 2 del MVP de Gemini y hoy sólo
  hay `tel:` y `mailto:`.
- Un `?ref=share` en el enlace permite medir qué tanto sirve.

---

## 7. Recomendaciones de Gemini — lectura crítica

### A. Filtro de prospectos con OTP (anti-spam)

El diagnóstico es correcto: el spam de leads es la queja número uno contra
Inmuebles24. Pero **el OTP por WhatsApp/SMS cuesta dinero por mensaje** (Twilio o
WhatsApp Cloud API) y agrega fricción justo en el momento en que el interesado
quiere escribir. Propuesta por etapas:

1. **Ahora:** tabla `leads` (`property_id`, `user_id` del asesor, nombre, teléfono,
   correo, mensaje, `budget_type`, `move_in_window`, `ip`, `status`) con formulario
   de contacto en la ficha, honeypot + `throttle`, y las **dos preguntas
   obligatorias del PDF**: ¿pago de contado o crédito preautorizado? y ¿en cuánto
   tiempo planeas mudarte? Notificación por correo al asesor + bandeja de leads
   en el panel. Esto ya entrega el "lead calificado" sin costo por mensaje.
2. **Después:** OTP por WhatsApp Cloud API sólo si el volumen de basura lo
   justifica. Se mide con los datos de la etapa 1.

### B. Cero anuncios fantasma

De acuerdo, y es barato de construir:

- Columna `last_confirmed_at` en `properties` (se pone al publicar y al editar).
- Comando `properties:request-confirmation` en `routes/console.php` (diario):
  a los 25 días manda recordatorio, a los 30 sin respuesta pasa la propiedad a
  `inactive` y avisa al asesor.
- Confirmación con **enlace firmado** (`URL::signedRoute`) de un clic — sirve igual
  por correo hoy y por WhatsApp mañana.
- Badges: **"Disponible hoy"** si `last_confirmed_at` < 7 días, **"Verificada"**
  si tiene ≥ 5 fotos, ubicación en mapa y ficha completa. Se calculan en el modelo,
  no se guardan.
- El punto 13 del cliente (fecha de creación) encaja aquí: mostrar
  "Publicado el X · Actualizado hace Y" da la señal de frescura que le falta a la
  competencia.

### C. Búsqueda por estilo de vida y desglose de costos

- **Desglose de costos:** ya contemplado en la migración de la sección 2
  (`property_tax_estimate`, `services_estimate` + el `maintenance_fee` existente).
  Se muestra como "costo mensual estimado" sumado. Barato y diferencia de verdad.
- **Tiempos de traslado:** Distance Matrix de Google se cobra por elemento y una
  búsqueda de catálogo dispara cientos de elementos por consulta — es un agujero de
  costos si se conecta directo al buscador. Alternativa para empezar:
  **búsqueda por radio** con fórmula de Haversine sobre `latitude`/`longitude`
  ("a menos de 5 km de [punto]"), que es SQL puro y cuesta 0. El tiempo real de
  traslado se calcula sólo **en la ficha de una propiedad concreta**, con caché por
  par origen–destino. Así se ofrece la función sin la factura.

### D. MVP

- **Velocidad < 2 s en móvil:** el catálogo ya trae `with()` en las consultas.
  Falta servir WebP con `srcset`, `loading="lazy"` en las tarjetas y un índice para
  el filtro por amenidades.
- **Alta en 3 pasos:** el formulario actual es una sola pantalla larga. La
  extracción con IA que ya existe cubre buena parte del trabajo; convertirlo en
  asistente de 3 pasos (1. pega el texto / datos básicos → 2. fotos → 3. ubicación
  y publicar) es reordenar la vista, no reescribir el backend.

---

## 8. Orden de ejecución

| Fase | Contenido | Puntos del PDF | Esfuerzo |
|---|---|---|---|
| ~~**A**~~ ✅ | Migración de campos nuevos + modelo + request + formulario + ficha pública | 4–13 | hecho |
| ~~**B**~~ ✅ | Categorías de amenidades + seeder + agrupación en vistas + filtro público | 1, 2 | hecho |
| ~~**C**~~ ✅ | Mapa Leaflet: selector en el panel, geocoding, mapa en la ficha | 3 | hecho |
| ~~**D**~~ ✅ | Compartir la propiedad | 15 | hecho |
| ~~**E**~~ ✅ | Ficha en PDF | 14 | hecho |
| **F** | Leads con preguntas de intención + bandeja + aviso al asesor | Gemini A.1 | 2 días |
| **G** | Re-confirmación automática + badges de confianza | Gemini B | 1–2 días |
| **H** | Desglose de costos + búsqueda por radio | Gemini C | 1–2 días |
| **I** | Alta en 3 pasos + optimización móvil | Gemini D | 2 días |
| **J** | OTP por WhatsApp, tiempos de traslado con Google | Gemini A.2, C | evaluar |

**Fases A, B y C entregadas** (24 de agosto de 2026). Lo que quedó en el código:

- `2026_08_24_090001_add_listing_details_to_properties_table` y
  `2026_08_24_090002_add_group_to_features_table`.
- `Property::CONDITIONS`, `ORIENTATIONS`, `POSITIONS`, `RENT_COMMISSIONS` y el
  accesor `estimated_monthly_cost`; `Feature::GROUPS` con el scope `ordered()`.
- Permiso `properties.view-commission`: sin él, `PropertyRequest` descarta las
  comisiones antes de validar, así que no se pueden mandar ni a mano.
- `GeocodingService` (Nominatim con caché de 30 días y User-Agent propio),
  `GeocodingController` y las rutas `geocoding.search` / `geocoding.reverse`
  bajo el limitador `geocoding` (30/min compartidos).
- `resources/js/map.js`: entry aparte de Vite con Leaflet, cargado sólo en el
  alta/edición y en la ficha pública. En el panel el pin es arrastrable y sugiere
  el CP; en la ficha pública se dibuja un círculo de 300 m en vez del domicilio.
- La extracción con IA ya llena también condición, orientación, interior/exterior,
  piso, predial y servicios.
- Pruebas nuevas en `tests/Feature/PropertyFlowTest.php` (campos nuevos, permiso
  de comisiones, filtro por amenidad, ficha pública, geocodificación con
  `Http::fake`).

**Fases D y E entregadas** (24 de agosto de 2026); con esto **los 15 puntos del
documento del cliente quedan cerrados**. Lo que se agregó:

- `PropertyPdfService` + `resources/views/public/pdf.blade.php` y la ruta pública
  `public.properties.pdf` (`/propiedades/{slug}/ficha.pdf`), limitada por IP.
  Las fotos se incrustan como data URI convertidas a JPEG: dompdf no dibuja WebP,
  que es el formato en que se guardan. Con `isFontSubsettingEnabled` la ficha pasó
  de 859 KB a 22 KB.
- Componente `x-share-buttons` + `resources/js/share.js`: hoja nativa de compartir
  donde el navegador la soporta, más WhatsApp, Facebook, X, correo y copiar enlace
  con respaldo para contextos sin `navigator.clipboard`.
- Botón de ficha PDF también en el panel, para propiedades ya publicadas.
- `phpunit.xml` fija `FILESYSTEM_DISK=public`: sin eso las pruebas de imágenes
  heredaban el `r2` del `.env` del desarrollador y fallaban.

**Siguiente arranque sugerido:** F + G (leads con preguntas de intención y
re-confirmación automática), que son las que atacan las debilidades de la
competencia.

Las fases F y G son las que de verdad atacan las debilidades de Inmuebles24 y
MercadoLibre; conviene meterlas antes de empujar la plataforma con asesores nuevos.

La fase J es la única con costo recurrente (mensajería y API de Google): se decide
con números en la mano después de F y G.
