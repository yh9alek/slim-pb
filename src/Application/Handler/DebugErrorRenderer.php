<?php

declare(strict_types=1);

namespace App\Application\Handler;

use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Throwable;

// Página de depuración para errores 500 en desarrollo (estilo Laravel/Ignition):
// mensaje, petición, versiones, traza completa y el
// fragmento de código (resaltado) de cada frame. Es autocontenida para NO
// depender de Twig ni de Vite, que pueden ser justo lo que ha fallado.
final class DebugErrorRenderer
{
    /** @var array<string, array<int, string>> Caché de líneas resaltadas por archivo. */
    private array $highlighted = [];

    public function __construct(private readonly string $basePath)
    {
    }

    public function render(Throwable $e, Request $request): string
    {
        // Para fatales (envueltos por el ShutdownHandler) y excepciones
        // encadenadas, la causa raíz apunta al lugar REAL a corregir.
        $primary = $this->rootCause($e);
        $frames = $this->buildFrames($primary);

        $uri = $request->getUri();
        $port = $uri->getPort();
        $rawTarget = ($uri->getHost() ?: 'localhost') . ($port !== null ? ':' . $port : '') . $uri->getPath();

        $method = $this->e($request->getMethod());
        $fullClass = $this->e($primary::class);
        $shortClass = $this->e($this->shortClass($primary));
        $message = $this->e($primary->getMessage());
        $php = $this->e(PHP_VERSION);
        $slim = $this->e(defined(App::class . '::VERSION') ? App::VERSION : '4');
        $target = $this->e($rawTarget);

        [$framesList, $panels] = $this->buildHtml($frames);
        $markdown = $this->markdownJson($primary, $request, $frames, $rawTarget);

        return <<<HTML
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>500 · {$shortClass}</title>
            <link rel="preconnect" href="https://fonts.googleapis.com">
            <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
            <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
            <style>
                :root {
                    --bg: #ebebed; --card: #ffffff; --text: #18181b; --muted: #71717a;
                    --border: #e4e4e7; --hover: #f4f4f5; --accent: #e5484d;
                    --accent-bg: #fdecec; --line: #fdecec; --ln: #a1a1aa; --pill: #f4f4f5;
                    --scroll: #d4d4d8; --scroll-hover: #a1a1aa;
                    --kw: #7c3aed; --str: #16a34a; --com: #94a3b8; --var: #c2410c; --num: #b45309; --id: #2563eb;
                }
                html.dark {
                    --bg: #09090b; --card: #18181b; --text: #fafafa; --muted: #a1a1aa;
                    --border: #27272a; --hover: #27272a; --accent: #ff6369;
                    --accent-bg: #2a1517; --line: #311a1c; --ln: #52525b; --pill: #27272a;
                    --scroll: #3f3f46; --scroll-hover: #52525b;
                    --kw: #c4b5fd; --str: #86efac; --com: #64748b; --var: #fdba74; --num: #fbbf24; --id: #93c5fd;
                }
                * { box-sizing: border-box; }
                body {
                    margin: 0; background: var(--bg); color: var(--text);
                    font-family: 'Instrument Sans', system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
                    -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale;
                }
                .wrap { max-width: 1080px; margin: 0 auto; padding: 2rem 1.5rem 4rem; }
                .topbar { display: flex; align-items: center; justify-content: space-between; gap: 20px; margin-bottom: 1.5rem; }
                .brand { display: flex; align-items: center; gap: 0.85rem; }
                .brand__icon { width: 40px; height: 40px; border-radius: 10px; object-fit: contain; display: block; }
                .brand__title { font-size: 1.5rem; font-weight: 500; letter-spacing: -0.02em; }
                .actions { display: flex; align-items: center; gap: 0.75rem; }
                .btn {
                    font: inherit; font-size: 0.85rem; font-weight: 500; cursor: pointer;
                    padding: 0.5rem 0.9rem; border-radius: 999px;
                    border: 1px solid var(--border); background: var(--card); color: var(--text);
                }
                .btn:hover { background: var(--hover); }
                .icon-btn {
                    width: 34px; height: 34px; border-radius: 50%; display: grid; place-content: center;
                    border: 1px solid var(--border); background: var(--card); cursor: pointer; color: var(--muted); font-size: 1rem;
                }
                .icon {
                    position: relative;
                    top: 1px;
                }
                .card {
                    background: var(--card); border-radius: 8px; padding: 1.75rem;
                    box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 1px 2px rgba(0,0,0,0.06); margin-bottom: 1.25rem;
                }
                .head { display: flex; justify-content: space-between; gap: 1rem; align-items: flex-start; }
                .badge {
                    display: inline-block; background: var(--accent-bg); color: var(--accent);
                    font-size: 0.8rem; font-weight: 600; padding: 0.3rem 0.85rem; border-radius: 999px;
                }
                .exc-msg { font-size: 1.5rem; font-weight: 500; letter-spacing: -0.02em; margin: 1.1rem 0 0; line-height: 1.25; overflow-wrap: anywhere; word-break: break-word; }
                .exc-class { color: var(--muted); font-size: 0.8rem; margin-top: 0.5rem; font-family: 'JetBrains Mono', ui-monospace, monospace; }
                .req { text-align: right; white-space: nowrap; }
                .req__pill {
                    display: inline-block; background: var(--pill); border: 1px solid var(--border);
                    padding: 0.4rem 0.8rem; border-radius: 999px; font-size: 0.8rem;
                    font-family: 'JetBrains Mono', ui-monospace, monospace;
                }
                .req__ver { color: var(--muted); font-size: 0.8rem; margin-top: 0.6rem; }
                .trace { display: grid; grid-template-columns: 300px minmax(0, 1fr); gap: 1.25rem; margin-top: 1.25rem; }
                .frames { display: flex; flex-direction: column; gap: 0.15rem; min-width: 0; max-height: 418px; overflow-y: scroll; }
                .frame {
                    display: block; width: 100%; text-align: left; border: 0; background: transparent;
                    color: inherit; font: inherit; cursor: pointer; padding: 0.6rem 0.75rem;
                    border-left: 2px solid transparent; min-width: 0;
                }
                .frame:hover { background: var(--hover); }
                .frame.is-active { background: var(--hover); border-left-color: var(--accent); }
                .frame__file { display: block; font-size: 0.85rem; font-weight: 400; overflow-wrap: anywhere; word-break: break-word; }
                .frame__line { color: var(--accent); }
                .frame__fn {
                    display: block; color: var(--muted); font-size: 0.75rem; margin-top: 0.2rem;
                    font-family: 'JetBrains Mono', ui-monospace, monospace; overflow-wrap: anywhere; word-break: break-word;
                }
                .panels { min-width: 0; }
                .panel { display: none; min-width: 0; }
                .panel.is-active { display: block; }
                .panel__head { color: var(--muted); font-size: 0.8rem; margin-bottom: 0.75rem; font-family: 'JetBrains Mono', ui-monospace, monospace; overflow-wrap: anywhere; word-break: break-word; }
                .panel__line { color: var(--accent); }
                .code {
                    border: 1px solid var(--border); border-radius: 10px; overflow-x: auto; padding: 0.45rem 0;
                    font-family: 'JetBrains Mono', ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
                    font-size: 0.82rem; line-height: 1.65;
                }
                .line { display: flex; min-width: 100%; width: max-content; }
                .line--active { background: var(--line); }
                .ln {
                    position: sticky; left: 0; flex: none; width: 3.5ch; text-align: right; padding: 0 1rem;
                    color: var(--ln); user-select: none; background: var(--card);
                }
                .line--active .ln { background: var(--line); color: var(--accent); }
                .lc { white-space: pre; padding-right: 1.5rem; }
                /* Scrollbars finos y discretos (en lugar del grueso por defecto) */
                .frames, .code { scrollbar-width: thin; scrollbar-color: var(--scroll) transparent; }
                .frames::-webkit-scrollbar, .code::-webkit-scrollbar { width: 8px; height: 8px; }
                .frames::-webkit-scrollbar-track, .code::-webkit-scrollbar-track { background: transparent; }
                .frames::-webkit-scrollbar-thumb, .code::-webkit-scrollbar-thumb { background: var(--scroll); border-radius: 999px; border: 2px solid transparent; background-clip: padding-box; }
                .frames::-webkit-scrollbar-thumb:hover, .code::-webkit-scrollbar-thumb:hover { background: var(--scroll-hover); background-clip: padding-box; }
                .frames::-webkit-scrollbar-corner, .code::-webkit-scrollbar-corner { background: transparent; }
                .tok-keyword { color: var(--kw); }
                .tok-str { color: var(--str); }
                .tok-comment { color: var(--com); font-style: italic; }
                .tok-var { color: var(--var); }
                .tok-num { color: var(--num); }
                .tok-id { color: var(--id); }
                @media (max-width: 760px) {
                    .trace { grid-template-columns: 1fr; }
                    .req { display: none; }

                    .frames {
                        max-height: 200px;
                    }
                }
                @media (max-width: 587px) {
                    .topbar {
                        flex-direction: column;
                        align-items: start;
                    }
                }
            </style>
        </head>
        <body>
            <div class="wrap">
                <div class="topbar">
                    <div class="brand">
                        <img src="/favicon.png" alt="" class="brand__icon">
                        <div class="brand__title">Error Interno del Servidor</div>
                    </div>
                    <div class="actions">
                        <button type="button" class="btn" data-copy>Copy as Markdown</button>
                        <button type="button" class="icon-btn" data-theme-toggle title="Cambiar tema"><span class="icon">◐</span></button>
                    </div>
                </div>

                <div class="card">
                    <div class="head">
                        <div>
                            <span class="badge">Error</span>
                            <h1 class="exc-msg">{$message}</h1>
                            <div class="exc-class">{$fullClass}</div>
                        </div>
                        <div class="req">
                            <span class="req__pill">{$method} {$target}</span>
                            <div class="req__ver">PHP {$php} — Slim {$slim}</div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="trace">
                        <div class="frames">{$framesList}</div>
                        <div class="panels">{$panels}</div>
                    </div>
                </div>
            </div>

            <script>
                (function () {
                    var root = document.documentElement;
                    var toggle = document.querySelector('[data-theme-toggle]');
                    if (toggle) toggle.addEventListener('click', function () { root.classList.toggle('dark'); });

                    document.querySelectorAll('[data-frame]').forEach(function (btn) {
                        btn.addEventListener('click', function () {
                            var idx = btn.getAttribute('data-frame');
                            document.querySelectorAll('[data-frame]').forEach(function (b) { b.classList.remove('is-active'); });
                            btn.classList.add('is-active');
                            document.querySelectorAll('[data-panel]').forEach(function (p) {
                                p.classList.toggle('is-active', p.getAttribute('data-panel') === idx);
                            });
                        });
                    });

                    var md = {$markdown};
                    var cbtn = document.querySelector('[data-copy]');
                    if (cbtn) cbtn.addEventListener('click', function () {
                        navigator.clipboard.writeText(md).then(function () {
                            var prev = cbtn.textContent; cbtn.textContent = 'Copiado ✓';
                            setTimeout(function () { cbtn.textContent = prev; }, 1500);
                        });
                    });
                })();
            </script>
        </body>
        </html>
        HTML;
    }

    /**
     * @return list<array{relative: string, line: int, function: string, vendor: bool, snippet: list<array{num: int, html: string, active: bool}>}>
     */
    private function buildFrames(Throwable $e): array
    {
        $trace = $e->getTrace();

        $frames = [$this->frame($e->getFile(), $e->getLine(), $this->fnLabel($trace[0] ?? null))];

        foreach ($trace as $t) {
            if (!isset($t['file'])) {
                continue;
            }
            $frames[] = $this->frame($t['file'], (int) ($t['line'] ?? 0), $this->fnLabel($t));
        }

        return $frames;
    }

    /**
     * @return array{relative: string, line: int, function: string, vendor: bool, snippet: list<array{num: int, html: string, active: bool}>}
     */
    private function frame(string $file, int $line, string $function): array
    {
        $normalized = str_replace('\\', '/', $file);

        return [
            'relative' => $this->relative($file),
            'line' => $line,
            'function' => $function,
            'vendor' => str_contains($normalized, '/vendor/'),
            'snippet' => $this->snippet($file, $line),
        ];
    }

    /**
     * @param array{class?: string, type?: string, function?: string}|null $trace
     */
    private function fnLabel(?array $trace): string
    {
        if ($trace === null) {
            return '{main}';
        }

        $class = $trace['class'] ?? '';

        // Las clases anónimas traen toda la ruta y un hash; lo recortamos.
        if (($pos = strpos($class, '@anonymous')) !== false) {
            $class = substr($class, 0, $pos) . '@anonymous';
        }

        $fn = $class . ($trace['type'] ?? '') . ($trace['function'] ?? '');

        return $fn !== '' ? $fn . '()' : '{closure}';
    }

    /**
     * @return list<array{num: int, html: string, active: bool}>
     */
    private function snippet(string $file, int $line, int $pad = 8): array
    {
        if ($line < 1 || !is_file($file) || !is_readable($file)) {
            return [];
        }

        $lines = $this->highlightedLines($file);
        if ($lines === []) {
            return [];
        }

        $total = count($lines);
        $start = max(1, $line - $pad);
        $end = min($total, $line + $pad);

        $rows = [];
        for ($i = $start; $i <= $end; ++$i) {
            $rows[] = ['num' => $i, 'html' => $lines[$i] ?? '', 'active' => $i === $line];
        }

        return $rows;
    }

    /**
     * Devuelve el archivo como mapa "línea => HTML resaltado". Tokeniza el PHP
     * para colorear; otros archivos se muestran en texto plano escapado.
     *
     * @return array<int, string>
     */
    private function highlightedLines(string $file): array
    {
        if (isset($this->highlighted[$file])) {
            return $this->highlighted[$file];
        }

        $source = @file_get_contents($file);
        if ($source === false) {
            return $this->highlighted[$file] = [];
        }

        $lines = str_ends_with(strtolower($file), '.php')
            ? $this->tokenizeToLines($source)
            : $this->plainLines($source);

        return $this->highlighted[$file] = $lines;
    }

    /**
     * @return array<int, string>
     */
    private function tokenizeToLines(string $source): array
    {
        $tokens = @token_get_all($source);

        $lines = [];
        $lineNo = 1;
        $current = '';

        foreach ($tokens as $token) {
            if (is_array($token)) {
                $class = $this->tokenClass($token[0], $token[1]);
                $text = $token[1];
            } else {
                $class = null;
                $text = $token;
            }

            $segments = explode("\n", $text);
            $last = count($segments) - 1;

            foreach ($segments as $idx => $segment) {
                if ($segment !== '') {
                    $escaped = $this->e($segment);
                    $current .= $class !== null
                        ? '<span class="' . $class . '">' . $escaped . '</span>'
                        : $escaped;
                }
                if ($idx < $last) {
                    $lines[$lineNo] = $current;
                    $current = '';
                    ++$lineNo;
                }
            }
        }

        $lines[$lineNo] = $current;

        return $lines;
    }

    /**
     * @return array<int, string>
     */
    private function plainLines(string $source): array
    {
        $lines = [];
        foreach (explode("\n", str_replace("\r\n", "\n", $source)) as $i => $text) {
            $lines[$i + 1] = $this->e($text);
        }

        return $lines;
    }

    private function tokenClass(int $id, string $text): ?string
    {
        /** @var array<int, true>|null $keywords */
        static $keywords = null;
        if ($keywords === null) {
            $keywords = array_flip([
                T_FUNCTION, T_FN, T_RETURN, T_YIELD, T_NEW, T_CLONE, T_CLASS, T_INTERFACE,
                T_TRAIT, T_ENUM, T_EXTENDS, T_IMPLEMENTS, T_ABSTRACT, T_FINAL, T_PUBLIC,
                T_PROTECTED, T_PRIVATE, T_STATIC, T_READONLY, T_VAR, T_CONST, T_GLOBAL,
                T_USE, T_NAMESPACE, T_AS, T_INSTANCEOF, T_INSTEADOF, T_IF, T_ELSE, T_ELSEIF,
                T_ENDIF, T_FOR, T_ENDFOR, T_FOREACH, T_ENDFOREACH, T_WHILE, T_ENDWHILE,
                T_DO, T_SWITCH, T_ENDSWITCH, T_CASE, T_DEFAULT, T_MATCH, T_BREAK, T_CONTINUE,
                T_GOTO, T_THROW, T_TRY, T_CATCH, T_FINALLY, T_ECHO, T_PRINT, T_DECLARE,
                T_ENDDECLARE, T_REQUIRE, T_REQUIRE_ONCE, T_INCLUDE, T_INCLUDE_ONCE,
                T_LOGICAL_AND, T_LOGICAL_OR, T_LOGICAL_XOR, T_EXIT, T_UNSET, T_ISSET,
                T_EMPTY, T_LIST, T_ARRAY, T_CALLABLE,
            ]);
        }

        return match (true) {
            $id === T_OPEN_TAG, $id === T_OPEN_TAG_WITH_ECHO, $id === T_CLOSE_TAG => 'tok-keyword',
            $id === T_COMMENT, $id === T_DOC_COMMENT => 'tok-comment',
            $id === T_VARIABLE => 'tok-var',
            $id === T_LNUMBER, $id === T_DNUMBER => 'tok-num',
            $id === T_CONSTANT_ENCAPSED_STRING, $id === T_ENCAPSED_AND_WHITESPACE,
            $id === T_STRING_VARNAME, $id === T_NUM_STRING => 'tok-str',
            isset($keywords[$id]) => 'tok-keyword',
            $id === T_STRING => in_array(strtolower($text), ['true', 'false', 'null'], true) ? 'tok-keyword' : 'tok-id',
            default => null,
        };
    }

    /**
     * @param list<array{relative: string, line: int, function: string, vendor: bool, snippet: list<array{num: int, html: string, active: bool}>}> $frames
     * @return array{0: string, 1: string}
     */
    private function buildHtml(array $frames): array
    {
        $framesList = '';
        $panels = '';

        // Por defecto seleccionamos el primer frame de TU código (no vendor):
        // normalmente es ahí donde está la corrección.
        $activeIndex = 0;
        foreach ($frames as $i => $f) {
            if (!$f['vendor']) {
                $activeIndex = $i;
                break;
            }
        }

        foreach ($frames as $i => $f) {
            $active = $i === $activeIndex;
            $frameClass = 'frame' . ($f['vendor'] ? ' frame--vendor' : '') . ($active ? ' is-active' : '');

            $framesList .= sprintf(
                '<button type="button" class="%s" data-frame="%d">'
                . '<span class="frame__file">%s<span class="frame__line">:%d</span></span>'
                . '<span class="frame__fn">%s</span></button>',
                $frameClass,
                $i,
                $this->e($f['relative']),
                $f['line'],
                $this->e($f['function']),
            );

            $code = '';
            foreach ($f['snippet'] as $row) {
                $code .= sprintf(
                    '<div class="line%s"><span class="ln">%d</span><span class="lc">%s</span></div>',
                    $row['active'] ? ' line--active' : '',
                    $row['num'],
                    $row['html'] === '' ? ' ' : $row['html'],
                );
            }

            if ($code === '') {
                $code = '<div class="line"><span class="ln"></span><span class="lc">// código no disponible</span></div>';
            }

            $panels .= sprintf(
                '<div class="panel%s" data-panel="%d"><div class="panel__head">%s<span class="panel__line"> :%d</span></div><div class="code">%s</div></div>',
                $active ? ' is-active' : '',
                $i,
                $this->e($f['relative']),
                $f['line'],
                $code,
            );
        }

        return [$framesList, $panels];
    }

    /**
     * @param list<array{relative: string, line: int, function: string, vendor: bool, snippet: list<array{num: int, html: string, active: bool}>}> $frames
     */
    private function markdownJson(Throwable $e, Request $request, array $frames, string $target): string
    {
        $md = sprintf(
            "# %s\n\n%s\n\n`%s %s` — PHP %s, Slim %s\n\n## Stack trace\n",
            $e::class,
            $e->getMessage(),
            $request->getMethod(),
            $target,
            PHP_VERSION,
            defined(App::class . '::VERSION') ? App::VERSION : '4',
        );

        foreach ($frames as $f) {
            $md .= sprintf("- %s:%d — %s\n", $f['relative'], $f['line'], $f['function']);
        }

        return json_encode($md, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    private function relative(string $file): string
    {
        $base = str_replace('\\', '/', $this->basePath) . '/';
        $normalized = str_replace('\\', '/', $file);

        return str_starts_with($normalized, $base) ? substr($normalized, strlen($base)) : $normalized;
    }

    private function rootCause(Throwable $e): Throwable
    {
        while (($previous = $e->getPrevious()) !== null) {
            $e = $previous;
        }

        return $e;
    }

    private function shortClass(Throwable $e): string
    {
        $parts = explode('\\', $e::class);

        return end($parts);
    }

    private function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
