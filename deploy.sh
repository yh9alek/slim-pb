#!/usr/bin/env bash
#
# Despliegue construyendo en el propio servidor.
# Requiere PHP, Composer y Bun instalados en producción.
# Idempotente: se puede ejecutar tantas veces como haga falta.
#
set -euo pipefail

# Situarse en la raíz del proyecto (donde vive este script).
cd "$(dirname "$0")"

echo "==> 1/8  Actualizando código"
git pull --ff-only

echo "==> 2/8  Dependencias PHP (sin dev, autoloader optimizado)"
composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

echo "==> 3/8  Ejecutando migraciones de base de datos"
composer run migrate

echo "==> 4/8  Instalando dependencias de Node y compilando assets"
bun install --frozen-lockfile
bun run build

echo "==> 5/8  Eliminando el hot file de desarrollo (si quedó de una sesión dev)"
rm -f public/hot

echo "==> 6/8  Asegurando directorios de runtime"
mkdir -p var/cache var/log

echo "==> 7/8  Limpiando cachés (contenedor PHP-DI compilado + plantillas Twig)"
find var/cache -mindepth 1 ! -name '.gitignore' -exec rm -rf {} +

echo "==> 8/8  Permisos de escritura en var/"
chmod -R u+rwX var/cache var/log

# Si usas php-fpm, descomenta para que opcache tome el código nuevo:
# sudo systemctl reload php-fpm

echo "==> Despliegue completado."
