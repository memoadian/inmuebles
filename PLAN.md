# Plan — Inmuebles (Laravel 12 + Docker)

Proyecto para registrar inmuebles tipo Inmuebles24: catálogo de propiedades con
fotos, usuarios con roles/permisos y panel de control.

**Ubicación:** `/home/memoadian/apps/php/inmuebles`

---

## Stack

| Capa | Elección | Por qué |
|---|---|---|
| Framework | Laravel 12, PHP 8.4 | Igual que `pos` |
| Contenedor | `php:8.4-fpm` + nginx + supervisor (imagen única) | Patrón de `pos/Dockerfile` |
| BD | MySQL 8 + phpMyAdmin | Patrón de `pos/docker-compose.yml` |
| Frontend | Blade + Tailwind 4 + Vite 7 | Sin starter kit, como `pos` |
| Auth | `LoginController` propio + registro | Control total del flujo |
| Permisos | `spatie/laravel-permission` ^6 | Ya lo usas en `pos` y `unoauno` |
| Fotos | **Cloudflare R2** vía `league/flysystem-aws-s3-v3` | S3-compatible, egress gratis |
| Imágenes | `intervention/image` | Thumbnails antes de subir a R2 |

---

## Estructura Docker

Copiada de `pos` con nombres y puertos ajustados para no chocar con los
contenedores que ya corren:

```
inmuebles/
├── Dockerfile                  # php:8.4-fpm + nginx + supervisor
├── docker-compose.yml          # inmuebles-app, mysql, phpmyadmin
├── .dockerignore
└── docker/
    ├── nginx/default.conf      # root /var/www/html/public, fastcgi 127.0.0.1:9000
    └── supervisord.conf        # php-fpm (prio 5) + nginx (prio 10)
```

Puertos (para convivir con `pos` en 80/8082 y `unoauno` en 3306/8081):

| Servicio | Host | Contenedor |
|---|---|---|
| app | `${APP_PORT:-8000}` | 80 |
| vite | `${VITE_PORT:-5175}` | 5175 |
| mysql | `${FORWARD_DB_PORT:-3308}` | 3306 |
| phpmyadmin | `8083` | 80 |

Extras respecto a `pos`:
- Extensión `intl` y `redis` en el Dockerfile (útiles para formato de precios y cache).
- `docker-entrypoint.sh` que corre `storage:link` y espera a MySQL antes de arrancar.

---

## Modelo de datos — Fase 1

```
users ──< properties >── property_types
              │
              ├──< property_images   (R2: disk, path, thumb_path, order, is_cover)
              ├──< property_features (m2m con features)
              └──> locations (state → city → neighborhood)
```

### Tablas

**`users`** — se extiende la de Laravel
- `phone`, `avatar_path`, `is_active`, `soft_deletes`

**`property_types`** — catálogo
- `name`, `slug`, `icon`, `is_active`
- Seed: Casa, Departamento, Terreno, Local comercial, Oficina, Bodega

**`properties`** — la entidad central
- `user_id` (dueño/agente), `property_type_id`
- `title`, `slug`, `description`
- `operation` enum: `sale` | `rent` | `both`
- `price`, `currency` (MXN/USD), `maintenance_fee`
- `bedrooms`, `bathrooms`, `half_bathrooms`, `parking_spaces`
- `land_area`, `built_area` (m²), `floors`, `age_years`
- `street`, `ext_number`, `int_number`, `postal_code`
- `state_id`, `city_id`, `neighborhood_id`
- `latitude`, `longitude`
- `status` enum: `draft` | `published` | `reserved` | `sold` | `rented` | `inactive`
- `published_at`, `views_count`, `is_featured`
- `soft_deletes`

**`property_images`**
- `property_id`, `disk` (`r2`), `path`, `thumb_path`
- `original_name`, `size`, `mime`, `width`, `height`
- `order`, `is_cover`

**`features`** + **`feature_property`** — amenidades
- Seed: Alberca, Jardín, Roof garden, Seguridad 24h, Elevador, Gimnasio,
  Amueblado, Acepta mascotas, Cisterna, Aire acondicionado

**`states` / `cities` / `neighborhoods`** — catálogo geográfico
- Seed inicial con los 32 estados de México; ciudades/colonias se cargan después

**Tablas de Spatie:** `roles`, `permissions`, `model_has_roles`,
`model_has_permissions`, `role_has_permissions` + columnas `group` y
`description` en permissions (como en `pos`).

---

## Roles y permisos

| Rol | Alcance |
|---|---|
| `Admin` | Todo, incluido gestión de usuarios/roles/permisos y catálogos |
| `Agent` | CRUD de **sus propias** propiedades e imágenes |
| `Client` | Solo lectura del catálogo público + favoritos (fase 2) |

Permisos agrupados (`group` de Spatie):

- **properties**: `properties.view`, `.create`, `.edit`, `.delete`, `.publish`, `.edit-any`
- **images**: `images.upload`, `images.delete`, `images.reorder`
- **catalogs**: `catalogs.manage` (tipos, features, ubicaciones)
- **users**: `users.view`, `.create`, `.edit`, `.delete`
- **roles**: `roles.view`, `.create`, `.edit`, `.delete`
- **permissions**: `permissions.view`, `.create`, `.edit`, `.delete`

`PropertyPolicy` resuelve el "solo las mías": un Agent pasa si
`property.user_id === user.id`; un Admin pasa siempre por `properties.edit-any`.

---

## Almacenamiento en Cloudflare R2

`config/filesystems.php`, disco `r2`:

```php
'r2' => [
    'driver' => 's3',
    'key' => env('R2_ACCESS_KEY_ID'),
    'secret' => env('R2_SECRET_ACCESS_KEY'),
    'region' => 'auto',
    'bucket' => env('R2_BUCKET'),
    'endpoint' => env('R2_ENDPOINT'),      // https://<account>.r2.cloudflarestorage.com
    'url' => env('R2_PUBLIC_URL'),          // dominio público/CDN del bucket
    'use_path_style_endpoint' => true,
    'visibility' => 'public',
    'throw' => true,
],
```

`.env` nuevo:
```
FILESYSTEM_DISK=r2
R2_ACCESS_KEY_ID=
R2_SECRET_ACCESS_KEY=
R2_BUCKET=inmuebles
R2_ENDPOINT=
R2_PUBLIC_URL=
```

**Flujo de subida** (`PropertyImageService`):
1. Validar: `image`, máx 8 MB, `jpg|jpeg|png|webp`.
2. Intervention Image → redimensionar a máx 1920px lado largo, convertir a WebP q80.
3. Generar thumb 400×300 (cover).
4. Subir ambos a `properties/{property_id}/{uuid}.webp` y `.../{uuid}_thumb.webp`.
5. Guardar fila en `property_images`; la primera imagen se marca `is_cover`.
6. Al borrar la propiedad (force delete) se borran los objetos de R2 vía Observer.

**Para desarrollo sin credenciales de R2:** `FILESYSTEM_DISK=public` funciona
igual porque el `disk` se guarda por imagen; la URL se resuelve con
`Storage::disk($image->disk)->url($image->path)`.

---

## Rutas — Fase 1

```php
// Invitados
GET  /                      → landing con propiedades publicadas
GET  /login                 → formulario
POST /dologin
GET  /register              → alta de usuario (rol Client por defecto)
POST /register
GET  /propiedades           → catálogo público con filtros
GET  /propiedades/{slug}    → detalle público

// Autenticado
POST /logout
GET  /dashboard             → panel con métricas según rol

// Panel (auth + permisos)
Route::resource('properties', PropertyController::class);
POST   /properties/{property}/images          → subir (múltiple)
DELETE /properties/{property}/images/{image}  → borrar
POST   /properties/{property}/images/reorder  → reordenar / fijar portada
POST   /properties/{property}/publish         → publicar/despublicar

// Admin
Route::resource('users',  UserController::class);
Route::resource('roles',  RoleController::class);
Route::resource('permissions', PermissionController::class)->except('show');
Route::resource('property-types', PropertyTypeController::class)->except('show');
Route::resource('features', FeatureController::class)->except('show');
```

---

## Vistas

```
resources/views/
├── layouts/app.blade.php + sidebar.blade.php + partials/
├── components/  (alerts, confirm-modal, image-uploader, property-card)
├── auth/        login, register
├── dashboard.blade.php
├── public/      index, show          ← catálogo sin login
├── properties/  index, create, edit, show
├── property-types/, features/
├── users/, roles/, permissions/
```

---

## Orden de ejecución

| # | Paso | Entregable |
|---|---|---|
| 1 | Scaffold Laravel 12 + Docker | `docker compose up` sirve la app en :8000 |
| 2 | Instalar Spatie, flysystem-s3, Intervention; configurar disco `r2` | `php artisan tinker` sube un archivo a R2 |
| 3 | Migraciones + modelos + factories + seeders de catálogos | `migrate:fresh --seed` limpio |
| 4 | Auth: login, registro, logout, layout con sidebar | Entrar y ver el dashboard |
| 5 | Roles/permisos + `PropertyPolicy` + seeder de roles | Admin ve todo, Agent solo lo suyo |
| 6 | CRUD de propiedades (sin fotos) | Crear/editar/listar una casa |
| 7 | Subida de fotos a R2 + galería + portada + reorden | Casa con 5 fotos servidas desde R2 |
| 8 | Panel: gestión de usuarios, roles, permisos y catálogos | CRUD completo para Admin |
| 9 | Catálogo público con filtros (tipo, operación, precio, ubicación) | Vista sin login navegable |

**Fase 1 = pasos 1 a 8.** El paso 9 cierra el MVP.

---

## Fuera de alcance (fase 2+)

- Favoritos y búsquedas guardadas
- Mensajería / solicitudes de contacto entre cliente y agente
- Mapa interactivo (Leaflet + geocoding)
- Planes de publicación y pagos
- Notificaciones por correo
- API pública / app móvil
