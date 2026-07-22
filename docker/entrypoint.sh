#!/bin/sh
set -e

# Garantiza que los directorios escribibles existan con los permisos correctos
# antes de arrancar, incluso si se monta un volumen sobre var/ (que llegaria
# como root y dejaria a www-data sin poder escribir):
#   var/cache -> contenedor PHP-DI compilado + cache de Twig (APP_ENV=prod)
#   var/log   -> logs de Monolog (app.log)
mkdir -p var/cache var/log
chown -R www-data:www-data var

exec "$@"
