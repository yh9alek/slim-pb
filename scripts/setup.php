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
const SLIM_COLOR = "\x1b[38;2;92;206;167m";
const GRAY = "\033[90m";
const GREEN = "\033[32m";
const YELLOW = "\033[33m";

$auto = in_array('--auto', $argv ?? [], true);
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

    if (function_exists('posix_isatty')) {
        return @posix_isatty(STDIN);
    }

    // Windows sin ext/posix: si STDIN no es un carácter (consola) sino un
    // pipe o está cerrado, no podemos preguntar.
    $stat = @fstat(STDIN);

    if ($stat === false) {
        return false;
    }

    // S_IFCHR = 0020000. Una consola real es un dispositivo de caracteres.
    return ($stat['mode'] & 0170000) === 0020000;
}

/** Pregunta sí/no. Enter acepta el valor por defecto. */
function confirm(string $question, bool $default = true): bool
{
    $hint = $default ? 'S/n' : 's/N';

    while (true) {
        echo BOLD . $question . RESET . GRAY . " [{$hint}]" . RESET . ': ';

        $line = fgets(STDIN);

        if ($line === false) {
            return $default;
        }

        $answer = strtolower(trim($line));

        if ($answer === '') {
            return $default;
        }

        if (in_array($answer, ['s', 'si', 'sí', 'y', 'yes'], true)) {
            return true;
        }

        if (in_array($answer, ['n', 'no'], true)) {
            return false;
        }

        echo YELLOW . '  ! Responde s o n.' . RESET . PHP_EOL;
    }
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

/** Borra un archivo o un directorio completo. */
function removePath(string $path): void
{
    if (is_file($path) || is_link($path)) {
        @unlink($path);

        return;
    }

    if (!is_dir($path)) {
        return;
    }

    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );

    foreach ($items as $item) {
        $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
    }

    @rmdir($path);
}

/** Elimina el enlace a /tasks de la portada. */
function removeDemoLink(string $root): void
{
    $path = $root . '/templates/views/pages/home/home.twig';

    if (!is_file($path)) {
        return;
    }

    $html = (string) file_get_contents($path);

    // El botón "Ver tareas" completo, con su SVG.
    $pattern = '#\s*<a href="/tasks".*?</a>#s';
    $clean = preg_replace($pattern, '', $html, 1);

    if ($clean !== null && $clean !== $html) {
        file_put_contents($path, $clean);
    }
}

/** Sustituye el contenido de un archivo solo si ya existe. */
function replaceFile(string $path, string $contents): void
{
    if (is_file($path)) {
        file_put_contents($path, $contents);
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

/**
* Elimina el CRUD de ejemplo (tasks) y deja las rutas y el contenedor
* en un estado limpio y arrancable.
*/
function removeDemoModule(string $root): void
{
    $paths = [
        // Backend
        'src/Domain/Task',
        'src/Application/DTO/TaskInput.php',
        'src/Application/Service/TaskService.php',
        'src/Application/Http/Controller/TaskController.php',
        'src/Application/Http/Controller/TaskWebController.php',
        'src/Infrastructure/Persistence/PdoTaskRepository.php',
        'src/Infrastructure/Persistence/InMemoryTaskRepository.php',

        // Base de datos
        'database/migrations/20260718120000_create_tasks_table.php',
        'database/seeds/TaskSeeder.php',

        // Frontend
        'templates/js/tasks',
        'templates/views/pages/tasks',
        'templates/views/components/task-form.twig',

        // Tests
        'tests/Unit/TaskServiceTest.php',
        'tests/Feature/TaskApiTest.php',
    ];

    foreach ($paths as $path) {
        removePath($root . '/' . $path);
    }

    // routes/web.php sin las rutas de tasks.
    replaceFile($root . '/routes/web.php', <<<'PHP'
        <?php

        declare(strict_types=1);

        use App\Application\Http\Controller\WebController;
        use Psr\Http\Message\RequestInterface as Request;
        use Psr\Http\Message\ResponseInterface as Response;
        use Slim\App;

        # Rutas que devuelven HTML (SSR con Twig), separadas de la API JSON.

        return function (App $app): void {

            $app->get('/', [WebController::class, 'home']);

            # HEALTHCHECK
            $app->get('/health', fn(Request $request, Response $response): Response => $response->withStatus(204));
        };

        PHP);

    // routes/api.php vacío pero listo para usar.
    replaceFile($root . '/routes/api.php', <<<'PHP'
        <?php

        declare(strict_types=1);

        use Slim\App;

        # Rutas de API (JSON)

        return function (App $app): void {
            //
        };

        PHP);

    // El contenedor pierde el binding del repositorio de ejemplo.
    replaceFile($root . '/config/repositories.php', <<<'PHP'
        <?php

        declare(strict_types=1);

        # Vinculación de interfaces de repositorio con sus implementaciones.

        return [
            //
        ];

        PHP);

    // DatabaseSeeder ya no depende de TaskSeeder.
    replaceFile($root . '/database/seeds/DatabaseSeeder.php', <<<'PHP'
        <?php

        declare(strict_types=1);

        use Phinx\Seed\AbstractSeed;

        class DatabaseSeeder extends AbstractSeed
        {
            public function getDependencies(): array
            {
                return [];
            }

            public function run(): void {}
        }

        PHP);

    removeDemoLink($root);
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

if ($auto || !isInteractive()) {
    if ($envCreated) {
        echo GREEN . '  ✓' . RESET . ' .env creado a partir de .env.example' . PHP_EOL;
    }

    echo PHP_EOL;
    echo BOLD . '  Ejecuta ' . SLIM_COLOR . 'php scripts/setup.php' . RESET . BOLD . ' para configurar el proyecto.' . RESET . PHP_EOL;
    echo PHP_EOL;

    exit(0);
}

/** @var array<string, mixed> $composer */
try {
    /** @var array<string, mixed> $composer */
    $composer = json_decode(
        (string) file_get_contents($composerFile),
        true,
        512,
        JSON_THROW_ON_ERROR,
    );
} catch (JsonException $e) {
    fwrite(STDERR, 'composer.json no es JSON válido: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

// Nombre por defecto: el vendor original + la carpeta donde se instaló.
$currentName = is_string($composer['name'] ?? null) ? $composer['name'] : 'vendor/app';
$vendor = explode('/', $currentName)[0];
$folder = strtolower(basename($root));
$folder = preg_replace('/[^a-z0-9._-]+/', '-', $folder) ?? '';
$folder = trim($folder, '._-');

if ($folder === '') {
    $folder = 'app';
}

$suggested = "{$vendor}/{$folder}";

echo PHP_EOL;
echo SLIM_COLOR . BOLD . '  Slim + Vite scaffold' . RESET . GRAY . ' — configuración inicial' . RESET . PHP_EOL;
echo GRAY . '  Pulsa Enter para aceptar el valor entre corchetes.' . RESET . PHP_EOL . PHP_EOL;

$currentDescription = is_string($composer['description'] ?? null)
    ? $composer['description']
    : 'Proyecto PHP con Slim 4';

$name = ask('  Nombre del paquete', $suggested, validatePackageName(...));
$description = ask('  Descripción', $currentDescription);

$keepDemo = confirm('  ¿Conservar el módulo de ejemplo (tasks)?', false);

$composer['name'] = $name;
$composer['description'] = $description;

// Metadatos propios del scaffold: no deben heredarse al proyecto nuevo.
unset($composer['keywords'], $composer['homepage'], $composer['authors']);

// Esta personalización es de un solo uso: quitamos el hook y el script.
unset($composer['scripts']['post-create-project-cmd']);

if (isset($composer['scripts']) && $composer['scripts'] === []) {
    unset($composer['scripts']);
}

try {
    $json = json_encode(
        $composer,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
    );
} catch (JsonException $e) {
    fwrite(STDERR, 'No se pudo serializar composer.json: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

// El borrado se difiere: unlink() sobre el script en ejecución no es fiable
// en Windows. La bandera evita limpiar si la escritura falla, para poder
// reintentar a mano.
$cleanup = true;

register_shutdown_function(static function () use (&$cleanup): void {
    if (!$cleanup) {
        return;
    }

    @unlink(__FILE__);

    if (is_dir(__DIR__) && (scandir(__DIR__) ?: []) === ['.', '..']) {
        @rmdir(__DIR__);
    }
});

if (file_put_contents($composerFile, $json . PHP_EOL) === false) {
    $cleanup = false;
    fwrite(STDERR, 'No se pudo escribir composer.json.' . PHP_EOL);
    exit(1);
}

if (!$keepDemo) {
    removeDemoModule($root);
}

echo PHP_EOL;
echo GREEN . '  ✓' . RESET . " composer.json actualizado ({$name})" . PHP_EOL;

if ($envCreated) {
    echo GREEN . '  ✓' . RESET . ' .env creado a partir de .env.example' . PHP_EOL;
}

if (!$keepDemo) {
    echo GREEN . '  ✓' . RESET . ' Módulo de ejemplo (tasks) eliminado' . PHP_EOL;
}

echo PHP_EOL;
echo BOLD . '  Siguientes pasos:' . RESET . PHP_EOL;
echo GRAY . '    bun install --ignore-scripts' . RESET . PHP_EOL;
echo GRAY . '    # configurar las variables en .env' . RESET . PHP_EOL;
echo GRAY . '    composer run migrate' . RESET . PHP_EOL;
echo GRAY . '    composer run seed' . RESET . PHP_EOL;
echo GRAY . '    composer run dev' . RESET . PHP_EOL;
echo PHP_EOL;
