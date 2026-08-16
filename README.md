# Inmuebles

Sistema **open source** para publicar y vender/rentar inmuebles: catálogo público
con buscador y filtros, fichas de propiedad con galería de fotos, y un panel de
control con roles y permisos para que dueños y agentes administren sus propias
publicaciones.

Pensado como una alternativa autohospedada y personalizable a portales tipo
Inmuebles24/Vivanuncios para quien quiere controlar sus propios datos e
infraestructura.

## Funcionalidades

- **Catálogo público** con búsqueda por texto, tipo de inmueble, operación
  (venta/renta), estado, rango de precio y número de recámaras.
- **Ficha de propiedad** con galería de imágenes, amenidades, ubicación y datos
  de contacto del dueño/agente.
- **Panel de control** para crear, editar y publicar propiedades, con subida y
  reordenamiento de fotos (portada incluida).
- **Roles y permisos** (`spatie/laravel-permission`): `Admin` gestiona todo,
  `Agent` administra únicamente sus propias propiedades, `Client` navega el
  catálogo.
- **Catálogos administrables**: tipos de inmueble y amenidades.
- **Almacenamiento de imágenes** en disco local para desarrollo o Cloudflare R2
  (S3-compatible) en producción, con redimensionado/optimización vía
  `intervention/image`.

## Stack

| Capa | Tecnología |
|---|---|
| Backend | Laravel 12, PHP 8.4 |
| Frontend | Blade + Tailwind CSS 4 + Vite |
| Base de datos | MySQL 8 |
| Permisos | spatie/laravel-permission |
| Imágenes | intervention/image + Cloudflare R2 (flysystem-aws-s3-v3) |
| Contenedores | Docker (php-fpm + nginx + supervisor) vía docker-compose |

## Requisitos

- Docker y Docker Compose (recomendado), **o**
- PHP 8.4, Composer, Node 20+ y MySQL 8 para correrlo sin contenedores.

## Puesta en marcha con Docker

```bash
cp .env.example .env

docker compose up -d --build

docker compose exec inmuebles-app php artisan key:generate
docker compose exec inmuebles-app php artisan migrate --seed

docker compose exec inmuebles-app npm install
docker compose exec inmuebles-app npm run build
```

Servicios expuestos por defecto:

| Servicio | URL |
|---|---|
| Aplicación | http://localhost:8000 |
| Vite (dev) | http://localhost:5175 |
| phpMyAdmin | http://localhost:8083 |

### Usuarios de prueba (seeder)

| Rol | Email | Contraseña |
|---|---|---|
| Admin | `admin@inmuebles.test` | `password` |
| Agente | `agente@inmuebles.test` | `password` |

## Puesta en marcha sin Docker

```bash
composer install
cp .env.example .env
php artisan key:generate

# Configura DB_* en .env apuntando a tu MySQL local
php artisan migrate --seed

npm install
npm run build   # o `npm run dev` en desarrollo

php artisan serve
```

## Almacenamiento de imágenes

Por defecto `FILESYSTEM_DISK=public`, útil para desarrollo sin credenciales.
Para producción, configura el disco `r2` (Cloudflare R2) con las variables
`R2_ACCESS_KEY_ID`, `R2_SECRET_ACCESS_KEY`, `R2_BUCKET`, `R2_ENDPOINT` y
`R2_PUBLIC_URL` en `.env` — ver `config/filesystems.php`.

## Roadmap

- Favoritos y búsquedas guardadas
- Mensajería entre cliente y agente
- Mapa interactivo de propiedades
- Planes de publicación y pagos
- API pública

## Contribuir

Este proyecto es open source y las contribuciones son bienvenidas: abre un
issue para reportar bugs o proponer funcionalidades, o envía un pull request.

## Licencia

Licenciado bajo [MIT](LICENSE).
