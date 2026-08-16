FROM php:8.4-fpm

# Argumentos
ARG WWWGROUP=1000
ARG WWWUSER=1000

WORKDIR /var/www/html

# Instalar dependencias del sistema
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libwebp-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libicu-dev \
    zip \
    unzip \
    nginx \
    supervisor

# Limpiar cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Instalar extensiones de PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip intl

# Obtener Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Crear usuario del sistema
RUN groupadd --force -g $WWWGROUP sail && \
    useradd -ms /bin/bash --no-user-group -g $WWWGROUP -u $WWWUSER sail

# php-fpm corre como 'sail' (uid del host) para que pueda escribir en el
# bind mount de storage/ y bootstrap/cache sin recurrir a chmod 777
RUN sed -i "s/^user = www-data/user = sail/; s/^group = www-data/group = sail/" \
    /usr/local/etc/php-fpm.d/www.conf

# Configurar Nginx
COPY docker/nginx/default.conf /etc/nginx/sites-available/default

# Configurar Supervisor
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Cambiar permisos
RUN chown -R sail:sail /var/www/html

# Exponer puerto 80
EXPOSE 80

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
