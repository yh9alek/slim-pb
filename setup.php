<?php

declare(strict_types=1);

/**
 * Personaliza el scaffold tras `composer create-project`.
 *
 * Pregunta nombre y descripción, los escribe en composer.json y crea el .env
 * si no existe. Pensado para ejecutarse una sola vez: al terminar se borra a
 * sí mismo (y su entrada en composer.json) para no dejar rastro en el proyecto
 * nuevo.
 *
 * En entornos no interactivos (CI, --no-interaction) no pregunta nada y se
 * limita a crear el .env, dejando el composer.json intacto.
 */

const RESET = "\033[0m";
const BOLD = "\033[1m";
const BLUE = "\033[38;2;78;105;251m";
const GRAY = "\033[90m";
const GREEN = "\033[32m";
const YELLOW = "\033[33m";

$root = dirname(__DIR__);

/**
 * ¿Podemos preguntar? Requiere STDIN conectado a un TTY y que Composer no
 * venga con --no-interaction.
 */
function isInteractive(): bool
{
    if (getenv('COMPOSER_NO_INTERACTION') === '1') {
        return false;
    }

    if (!defined('STDIN')) {
        return false;
    }

    // posix_isatty es la comprobación fiable; si la extensión no está,
    // asumimos que sí es interactivo (peor caso: una pregunta de más).
    if (function_exists('posix_isatty')) {
        return @posix_isatty(STDIN);
    }

    return true;
}

/**
 * Lee una línea con valor por defecto y validación opcional.
 *
 * @param callable(string): ?string $validator Devuelve el mensaje de error, o null si es válido.
 */
function ask(string $question, string $default = '', ?callable $validator = null): string
{
    while (true) {
        $suffix = $default !== '' ? GRAY . " [{$default}]" . RESET : '';
        echo BOLD . $question . RESET . $suffix . ': ';

        $line = fgets(STDIN);

        // Ctrl+D / EOF: nos quedamos con el valor por defecto.
        $answer = $line === false ? $default : trim($line);

        if ($answer === '') {
            $answer = $default;
        }

        if ($validator !== null && ($error = $validator($answer)) !== null) {
            echo YELLOW . "  ! {$error}" . RESET . PHP_EOL;
            continue;
        }

        return $answer;
    }
}

/** Composer exige vendor/paquete en minúsculas. */
function validatePackageName(string $name): ?string
{
    if (!preg_match('#^[a-z0-9]([_.-]?[a-z0-9]+)*/[a-z0-9](([_.]|-{1,2})?[a-z0-9]+)*$#', $name)) {
        return 'Formato inválido. Usa vendor/nombre en minúsculas (ej. yh9alek/erp).';
    }

    return null;
}

// ---------------------------------------------------------------------------

$composerFile = $root . '/composer.json';

if (!is_file($composerFile)) {
    fwrite(STDERR, 'No se encontró composer.json; se omite la personalización.' . PHP_EOL);
    exit(0);
}

// El .env se crea siempre, interactivo o no.
$envCreated = false;
if (!is_file($root . '/.env') && is_file($root . '/.env.example')) {
    copy($root . '/.env.example', $root . '/.env');
    $envCreated = true;
}

if (!isInteractive()) {
    if ($envCreated) {
        echo 'Creado .env a partir de .env.example.' . PHP_EOL;
    }
    exit(0);
}

/** @var array<string, mixed> $composer */
$composer = json_decode((string) file_get_contents($composerFile), true, 512, JSON_THROW_ON_ERROR);

// Nombre por defecto: el vendor original + la carpeta donde se instaló.
$currentName = is_string($composer['name'] ?? null) ? $composer['name'] : 'vendor/app';
$vendor = explode('/', $currentName)[0];
$folder = strtolower(basename($root));
$folder = preg_replace('/[^a-z0-9._-]+/', '-', $folder) ?? 'app';
$suggested = "{$vendor}/{$folder}";

echo PHP_EOL;
echo BLUE . BOLD . '  Slim scaffold' . RESET . GRAY . ' — configuración inicial' . RESET . PHP_EOL;
echo GRAY . '  Pulsa Enter para aceptar el valor entre corchetes.' . RESET . PHP_EOL . PHP_EOL;

$name = ask('  Nombre del paquete', $suggested, validatePackageName(...));
$description = ask('  Descripción', 'Proyecto PHP con Slim 4');

$composer['name'] = $name;
$composer['description'] = $description;

// Esta personalización es de un solo uso: quitamos el hook y el script.
unset($composer['scripts']['post-create-project-cmd']);

if (isset($composer['scripts']) && $composer['scripts'] === []) {
    unset($composer['scripts']);
}

$json = json_encode(
    $composer,
    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
);

file_put_contents($composerFile, $json . PHP_EOL);

echo PHP_EOL;
echo GREEN . '  ✓' . RESET . " composer.json actualizado ({$name})" . PHP_EOL;

if ($envCreated) {
    echo GREEN . '  ✓' . RESET . ' .env creado a partir de .env.example' . PHP_EOL;
}

echo PHP_EOL;
echo BOLD . '  Siguientes pasos:' . RESET . PHP_EOL;
echo GRAY . '    bun install' . RESET . PHP_EOL;
echo GRAY . '    # rellena DB_DSN, DB_USER y DB_PASS en .env' . RESET . PHP_EOL;
echo GRAY . '    composer run migrate' . RESET . PHP_EOL;
echo GRAY . '    composer run dev' . RESET . PHP_EOL;
echo PHP_EOL;

// Se borra a sí mismo. Si scripts/ queda vacío, también.
@unlink(__FILE__);

$scriptsDir = __DIR__;
if (is_dir($scriptsDir) && (scandir($scriptsDir) ?: []) === ['.', '..']) {
    @rmdir($scriptsDir);
}
