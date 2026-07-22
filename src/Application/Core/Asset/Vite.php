<?php

declare(strict_types=1);

namespace App\Application\Core\Asset;

use RuntimeException;

// Genera las etiquetas de los assets de Vite, resolviendo recursivamente los
// chunks compartidos (CSS y modulepreload) que importa cada entrada.
//
// - Desarrollo (existe public/hot): apunta al servidor de Vite con HMR.
// - Producción: lee el manifest y emite los archivos hasheados.
final class Vite
{
    private bool $clientRendered = false;

    public function __construct(
        private readonly string $hotFile,
        private readonly string $manifestPath,
        private readonly string $buildBase,
    ) {}

    public function tags(string ...$entries): string
    {
        $devServer = $this->devServerUrl();

        return $devServer !== null
            ? $this->devTags($devServer, $entries)
            : $this->productionTags($entries);
    }

    private function devServerUrl(): ?string
    {
        if (!is_file($this->hotFile)) {
            return null;
        }

        $url = trim((string) file_get_contents($this->hotFile));

        return $url !== '' ? rtrim($url, '/') : null;
    }

    /**
     * @param string[] $entries
     */
    private function devTags(string $devServer, array $entries): string
    {
        $tags = [];

        // El cliente de HMR se inyecta una sola vez por petición, aunque
        // se llame a vite() en varios bloques de la plantilla.
        if (!$this->clientRendered) {
            $tags[] = sprintf('<script type="module" src="%s/@vite/client"></script>', $devServer);
            $this->clientRendered = true;
        }

        foreach ($entries as $entry) {
            $tags[] = sprintf('<script type="module" src="%s/%s"></script>', $devServer, $entry);
        }

        return implode("\n", $tags);
    }

    /**
     * @param string[] $entries
     */
    private function productionTags(array $entries): string
    {
        $manifest = $this->manifest();

        $styles = [];
        $preloads = [];
        $scripts = [];

        foreach ($entries as $entry) {
            if (!isset($manifest[$entry]['file'])) {
                continue;
            }

            $seen = [];
            $css = [];
            $imports = [];
            $this->collect($entry, $manifest, $seen, $css, $imports);

            // Dedupe por nombre de archivo (clave del array).
            foreach ($css as $file) {
                $styles[$file] = sprintf('<link rel="stylesheet" href="%s%s">', $this->buildBase, $file);
            }

            foreach ($imports as $file) {
                $preloads[$file] = sprintf('<link rel="modulepreload" href="%s%s">', $this->buildBase, $file);
            }

            $scripts[] = sprintf(
                '<script type="module" src="%s%s"></script>',
                $this->buildBase,
                $manifest[$entry]['file'],
            );
        }

        // Estilos primero (evita FOUC), luego las precargas, y al final los scripts.
        return implode("\n", [...array_values($styles), ...array_values($preloads), ...$scripts]);
    }

    /**
     * Recorre una entrada y sus imports estáticos en profundidad, acumulando
     * el CSS y los archivos JS a precargar con modulepreload.
     *
     * @param array<string, array{file?: string, css?: list<string>, imports?: list<string>}> $manifest
     * @param array<string, true> $seen
     * @param list<string> $css
     * @param list<string> $imports
     */
    private function collect(string $key, array $manifest, array &$seen, array &$css, array &$imports): void
    {
        if (isset($seen[$key]) || !isset($manifest[$key])) {
            return;
        }

        $seen[$key] = true;
        $chunk = $manifest[$key];

        foreach ($chunk['imports'] ?? [] as $import) {
            if (isset($manifest[$import]['file'])) {
                $imports[] = $manifest[$import]['file'];
            }
            $this->collect($import, $manifest, $seen, $css, $imports);
        }

        foreach ($chunk['css'] ?? [] as $file) {
            $css[] = $file;
        }
    }

    /**
     * @return array<string, array{file?: string, css?: list<string>, imports?: list<string>}>
     */
    private function manifest(): array
    {
        if (!is_file($this->manifestPath)) {
            throw new RuntimeException('No se encontró el manifest de Vite. Ejecuta "npm run build".');
        }

        /** @var array<string, array{file?: string, css?: list<string>, imports?: list<string>}> $manifest */
        $manifest = json_decode(
            (string) file_get_contents($this->manifestPath),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        return $manifest;
    }
}
