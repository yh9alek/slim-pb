# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Naturaleza del repo

`yh9alek/slim-pb` es un **scaffold Packagist** (`composer create-project yh9alek/slim-pb`) para un backend Slim 4 + frontend Vite/Tailwind/DaisyUI con SSR vía Twig. Trátalo como una **plantilla en evolución**: mantén los cambios genéricos y reutilizables, pensando en quien lo use como punto de partida vía `create-project`, no solo en el estado actual de este working tree.

`scripts/setup.php` es el instalador post-`create-project` (borra el módulo demo `tasks` si se pide y luego se autoelimina). Es efímero por diseño — si ya no existe en el árbol, es esperado.

## Requisitos

- PHP >= 8.4.1
- Frontend con **Bun** (no npm/yarn): `bun install --ignore-scripts`, `bun run dev:all`
- Migraciones/seeders con **Phinx** (no Doctrine Migrations)

## Comandos

- `composer test` — ejecuta la suite con **Pest** (no PHPUnit directo, aunque existe `phpunit.xml.dist` como base interna de Pest)
- `composer test:coverage` — Pest con cobertura
- `composer phpstan` — análisis estático, nivel 8, `--memory-limit=2G`
- `composer cs:fix` — aplica formateo con PHP-CS-Fixer (reglas `@PER`)
- `composer migrate` / `migrate:rollback` / `migrate:status` / `migrate:create` — Phinx
- `composer seed:create` / `seed` — seeders Phinx
- `composer dev` — levanta PHP + Vite juntos (`bun run dev:all`)
- No hay CI configurado (`.github/workflows` ausente) — tests/lint/stan solo corren localmente.

## Arquitectura

Capas estrictas bajo `src/` (namespace `App\`), reforzadas por `tests/Feature/ArchTest.php`:

- `Domain/` — entidades y contratos puros, **sin** dependencias de framework, Slim, PDO ni de `Application`/`Infrastructure`.
- `Application/{Core,DTO,Http,Service}/` — casos de uso, controladores HTTP, DTOs, settings, validación, middleware.
- `Infrastructure/Persistence/` — implementaciones concretas (PDO e InMemory) de los repositorios del dominio.

`ArchTest.php` también prohíbe funciones de debug (`dd`, `dump`, `var_dump`, `ray`) en el código de producción.

## Estilo de código

- `declare(strict_types=1);` en todo archivo PHP, con línea en blanco tras `<?php`.
- Clases `final`, `readonly` en entidades/propiedades inmutables, constructor property promotion.
- Comillas simples por defecto para strings; dobles solo con interpolación.
- 4 espacios de indentación.
- Reglas `@PER` de PHP-CS-Fixer (PSR-12 extendido) — no ejecutar formateo manual divergente, usar `composer cs:fix`.
- PHPDoc con tipos genéricos de array cuando aplique (`@return array{id: int|null, ...}`).

## Notas

- `.env` real está en `.gitignore`; nunca cites ni commits su contenido. `.env.example` documenta las claves esperadas.
- Ignora `vendor/` y `node_modules/` en cualquier búsqueda de código — son muy grandes e irrelevantes.
