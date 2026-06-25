#!/usr/bin/env bash
#
# Despliegue construyendo en el propio servidor.
# Requiere PHP, Composer y Node instalados en producción.
# Idempotente: se puede ejecutar tantas veces como haga falta.
#
set -euo pipefail

# Situarse en la raíz del proyecto (donde vive este script).
cd "$(dirname "$0")"

echo "==> 1/7  Actualizando código"
git pull --ff-only

echo "==> 2/7  Dependencias PHP (sin dev, autoloader optimizado)"
composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

echo "==> 3/7  Instalando dependencias de Node y compilando assets"
npm ci
npm run build

echo "==> 4/7  Eliminando el hot file de desarrollo (si quedó de una sesión dev)"
rm -f public/hot

echo "==> 5/7  Asegurando directorios de runtime"
mkdir -p var/cache var/log

echo "==> 6/7  Limpiando cachés (contenedor PHP-DI compilado + plantillas Twig)"
find var/cache -mindepth 1 ! -name '.gitignore' -exec rm -rf {} +

echo "==> 7/7  Permisos de escritura en var/"
chmod -R u+rwX var/cache var/log

# Si usas php-fpm, descomenta para que opcache tome el código nuevo:
# sudo systemctl reload php-fpm

echo "==> Despliegue completado."
