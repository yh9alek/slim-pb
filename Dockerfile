# syntax=docker/dockerfile:1

# =============================================================================
# Base PHP — capa compartida por las etapas de dependencias y de runtime.
# Centralizar aqui la version de PHP y las extensiones evita duplicarlas y
# garantiza que Composer resuelva las mismas platform-reqs que tendra produccion.
# =============================================================================
FROM php:8.3-fpm-alpine AS base

# install-php-extensions gestiona extensiones (y sus libs de sistema) de forma
# limpia, tanto en Alpine como en Debian.
COPY --from=mlocati/php-extension-installer:latest \
     /usr/bin/install-php-extensions /usr/local/bin/

# mbstring es requerido
RUN install-php-extensions \
        pdo_mysql \
        mysqli \
        mbstring \
        bcmath \
        gd \
        intl \
        zip \
        exif \
        pcntl \
        opcache \
        redis

# Composer (ultima version) disponible tambien en runtime.
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www/html


# =============================================================================
# Assets — compila el frontend con Bun (vite.config.js emite a public/build
# con manifest). Se cachea bun install copiando primero package.json + lockfile.
# =============================================================================
FROM oven/bun:latest AS assets

WORKDIR /app

COPY package.json bun.lock ./
RUN bun install --frozen-lockfile

COPY . .
RUN bun run build


# =============================================================================
# Vendor — instala dependencias PHP de produccion.
# =============================================================================
FROM base AS vendor

COPY composer.json composer.lock ./
RUN composer install \
        --no-dev \
        --no-scripts \
        --no-autoloader \
        --prefer-dist \
        --no-interaction \
        --no-progress

COPY . .
RUN composer dump-autoload --no-dev --optimize --classmap-authoritative


# =============================================================================
# Runtime — imagen final: PHP-FPM + NGINX bajo Supervisor en un unico contenedor.
# =============================================================================
FROM base AS app

RUN apk add --no-cache nginx supervisor \
    && mkdir -p /run/nginx

# Configuracion (ver carpeta docker/).
COPY docker/nginx.conf        /etc/nginx/nginx.conf
COPY docker/php-fpm.conf      /usr/local/etc/php-fpm.d/zz-app.conf
COPY docker/php.ini           /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/supervisord.conf  /etc/supervisord.conf
COPY docker/entrypoint.sh     /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

# Codigo de la app + artefactos construidos en las etapas previas.
COPY --chown=www-data:www-data . .
COPY --from=vendor --chown=www-data:www-data /var/www/html/vendor ./vendor
COPY --from=assets --chown=www-data:www-data /app/public/build ./public/build

# Directorios escribibles necesarios en produccion (APP_ENV=prod):
#   var/cache -> contenedor PHP-DI compilado + cache de plantillas Twig
#   var/log   -> logs de Monolog (app.log)
RUN mkdir -p var/cache var/log \
    && chown -R www-data:www-data var \
    && chmod -R 775 var

# Dokploy enruta a este puerto: configurar "Container Port = 80" en el panel.
EXPOSE 80

# Chequeo de salud. Apunta a /health.
HEALTHCHECK --interval=30s --timeout=5s --start-period=20s --retries=3 \
    CMD wget -qO- http://127.0.0.1/health >/dev/null 2>&1 || exit 1

ENTRYPOINT ["entrypoint"]
CMD ["supervisord", "-c", "/etc/supervisord.conf"]
