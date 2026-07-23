<div align="center">
  <img src="https://raw.githubusercontent.com/yh9alek/miscellaneous/refs/heads/main/src/SlimVite/imgs/slim-vite.png">
</div>

# slim-pb

Backend PHP con **Slim 4** por capas + frontend con **Vite 8**, Tailwind CSS y
DaisyUI. Las vistas se renderizan en el servidor (Twig) y JavaScript solo
añade interactividad encima.

Incluye una API JSON y una interfaz web sobre el mismo recurso (`tasks`).

---

## Stack

| Capa       | Herramientas                                                          |
| ---------- | --------------------------------------------------------------------- |
| Backend    | PHP 8.4+, Slim 4, PHP-DI, Twig, Monolog, Symfony Validator, PDO       |
| Frontend   | Vite 8, Tailwind CSS 4, DaisyUI 5, Axios, SweetAlert2                 |
| Base datos | Phinx (migraciones y seeders), Faker                                  |
| Calidad    | Pest, PHPStan (lvl 8), PHP-CS-Fixer (PER)                             |
| Despliegue | Docker (PHP-FPM + Nginx), Apache/Nginx tradicional, `deploy.sh`       |

---

## Puesta en marcha

Requisitos: PHP >= 8.4.1, [Composer](https://getcomposer.org/) y [Bun](https://bun.sh).

```bash
composer install --no-scripts
bun install --ignore-scripts
cp .env.example .env      # indicar los valores de entorno a trabajar

composer run migrate      # crea las tablas de la BD
composer run seed         # opcional: datos de ejemplo

composer run dev
```

`composer run dev` levanta **los dos servidores a la vez** (PHP y Vite) con
salida etiquetada:

```
[vite]   VITE v8.1.5  ready in 353 ms
[vite]   →  Local:   http://localhost:5173/
[vite]   SLIM v4.15.2  plugin v1.0.0
[vite]   →  APP_URL: http://localhost:8000
[server]    INFO  Server running on [http://localhost:8000].
```

La app se abre en **http://localhost:8000**. El puerto 5173 es solo el servidor
de assets de Vite.

---

## Estructura

```
public/index.php ............ Front controller: bootstrap, contenedor, app
public/build/ ............... Assets compilados por Vite (generado)
public/hot .................. Marca el modo dev de Vite (generado, efímero)
public/.htaccess ............ Reescritura a index.php (Apache)

bootstrap/app.php ........... Construye la app: contenedor, config, rutas
server.php .................. Router del servidor embebido de PHP
serve.mjs ................... Arranca el servidor PHP con salida formateada

config/
  settings.php .............. Configuración leída del .env
  dependencies.php .......... Contenedor: logger, PDO, throttling
  repositories.php .......... Qué implementación de repositorio se usa
  validation.php ............ Symfony Validator + contrato propio
  views.php ................. Twig + extensión vite()
  middleware.php ............ Pipeline: JSON, routing, Twig, throttle, errores

routes/
  web.php ................... Rutas que devuelven HTML
  api.php ................... Rutas que devuelven JSON

database/
  migrations/ ............... Migraciones de Phinx
  seeds/ .................... Seeders (DatabaseSeeder, TaskSeeder)
phinx.php ................... Config de Phinx: deriva el adaptador del DB_DSN

src/Domain/ ................. Núcleo sin framework
  Task/Task.php ............. Entidad
  Task/TaskRepository.php ... Contrato de persistencia
  Shared/ ................... Excepciones (NotFound, Validation)

src/Application/ ............ Casos de uso y borde HTTP
  Http/Controller/ .......... Controladores (API y web)
  Service/TaskService.php ... Lógica de negocio
  DTO/TaskInput.php ......... Entrada + reglas de validación (#[Assert\*])
  Core/Validation/ .......... Validator (interfaz) + adaptador de Symfony
  Core/Handler/ ............. Excepción → respuesta HTTP; página de debug
  Core/Middleware/ .......... JSON body, throttling, sondas de DevTools
  Core/Throttle/ ............ Límite de peticiones por IP
  Core/Asset/ ............... Lee el manifest de Vite para las plantillas

src/Infrastructure/
  Persistence/PdoTaskRepository.php ...... Implementación SQL
  Persistence/InMemoryTaskRepository.php . Implementación para tests

templates/
  views/ .................... Plantillas Twig (SSR)
  css/app.css ............... Tailwind + DaisyUI
  js/ ....................... Frontend por módulos (ver abajo)

tests/
  Unit/ ..................... Lógica de negocio aislada
  Feature/ .................. Pila HTTP completa + reglas de arquitectura
```

---

## Rutas

**Web (HTML)**

| Método | Ruta            | Descripción            |
| ------ | --------------- | ---------------------- |
| GET    | `/`             | Página de bienvenida   |
| GET    | `/tasks`        | Lista de tareas        |
| GET    | `/tasks/create` | Formulario de creación |
| GET    | `/health`       | Healthcheck (204)      |

**API (JSON)**

| Método | Ruta               | Descripción       |
| ------ | ------------------ | ----------------- |
| GET    | `/api/tasks`       | Listar            |
| GET    | `/api/tasks/{id}`  | Ver una           |
| POST   | `/api/tasks`       | Crear             |
| PUT    | `/api/tasks/{id}`  | Actualizar        |
| DELETE | `/api/tasks/{id}`  | Eliminar (204)    |

Las rutas de la API con `{id}` solo aceptan valores numéricos (`[0-9]+`).

Los errores se devuelven siempre con la misma forma:

```json
{
  "error": {
    "status": 422,
    "message": "Los datos enviados no son válidos.",
    "errors": { "title": ["El título es obligatorio."] }
  }
}
```

---

## Cómo funciona una petición

1. El servidor manda todo a `public/index.php` (front controller).
2. Se carga el `.env`, se construye el contenedor y la app Slim.
3. Pasa por el middleware: parseo de JSON, routing, Twig y throttling.
4. El router elige el controlador (`routes/web.php` o `routes/api.php`).
5. El controlador arma un `TaskInput`, lo valida y delega en `TaskService`.
6. El servicio usa `TaskRepository`; `PdoTaskRepository` ejecuta el SQL.
7. La respuesta sale como HTML (Twig) o JSON según la ruta.

Si algo lanza una excepción, `HttpErrorHandler` la traduce a HTTP de forma
centralizada (404, 422, 429, 500…) y responde en HTML o JSON según el `Accept`.
En desarrollo, los errores 500 muestran una página de debug con el código
fuente y la traza.

---

## Base de datos

Las migraciones y los seeders se gestionan con **Phinx**. `phinx.php` reutiliza
el mismo `DB_DSN` de la app y deriva el adaptador (`mysql`, `pgsql` o `sqlite`)
a partir de él, así que no hay que configurar la conexión dos veces.

```bash
composer run migrate            # aplica las migraciones pendientes
composer run migrate:rollback   # revierte la última
composer run migrate:status     # estado de cada migración
composer run migrate:create     # crea una migración nueva

composer run seed               # ejecuta los seeders
composer run seed:create        # crea un seeder nuevo
```

La migración inicial crea la tabla `tasks` (`id`, `title`, `completed`, además
de `created_at` y `updated_at`). `TaskSeeder` la puebla con datos de ejemplo
generados con Faker.

---

## Frontend

Un módulo por recurso, con la misma estructura siempre:

```
templates/js/
  app.js ......................... Entrada común (carga el CSS)
  lib/http.js .................... Axios + interceptores (avisa de 400/500)
  lib/toast.js ................... Toasts (esquina inferior derecha)
  tasks/
    api/tasks.api.js ............. Llamadas a /api/tasks
    store/store.js ............... Estado del módulo
    use-cases/ ................... Acciones (toggle, delete, render…)
    presentation/ ................ Entradas de Vite, una por vista
```

**Convención:** cada archivo en `<módulo>/presentation/*.js` es una entrada de
Vite y se detecta sola (no hay que tocar `vite.config.js`). Para usarla en su
vista:

```twig
{% block head %}
    {{ vite('templates/js/tasks/presentation/tasks-page.js') }}
{% endblock %}
```

En desarrollo, Vite escribe `public/hot` con la URL de su servidor; la extensión
`vite()` de Twig lo detecta para servir los assets desde el dev server, y cae al
manifest de `public/build` cuando ese archivo no existe (producción).

Los avisos al usuario están repartidos así: **modal** (SweetAlert2) para errores
400/500 y de red, disparado automáticamente desde `lib/http.js`; **toast** para
confirmaciones breves (`toast.success('Tarea eliminada')`).

---

## Comandos

```bash
composer run dev          # PHP + Vite a la vez (desarrollo)
composer run serve        # solo el servidor PHP
bun run build             # compila los assets a public/build

composer run migrate      # migraciones (ver sección Base de datos)
composer run seed         # seeders

composer run cs:fix       # formateo de código
composer run phpstan      # análisis estático (nivel 8)
composer run test         # Pest
composer run test:coverage
```

---

## Tests

Pest, con tres frentes:

- **`tests/Unit`** — lógica de negocio aislada (`TaskServiceTest`).
- **`tests/Feature/TaskApiTest`** — ejercita la pila HTTP completa (router,
  middleware, validación, manejador de errores) contra
  `InMemoryTaskRepository`, sin tocar la base de datos.
- **`tests/Feature/ArchTest`** — reglas de arquitectura: el dominio no puede
  depender de Slim, PDO, `App\Infrastructure` ni `App\Application`, y no deben
  quedar funciones de depuración (`dd`, `dump`, `var_dump`, `ray`) en el código.

Los helpers de `tests/Pest.php` (`testApp()`, `apiRequest()`, `jsonBody()`)
construyen la app de pruebas y desactivan el throttling durante los tests.

---

## Variables de entorno

| Variable                            | Para qué                                         |
| ----------------------------------- | ------------------------------------------------ |
| `APP_NAME`                          | Nombre de la app                                 |
| `APP_ENV`                           | `dev` o `prod` (activa errores detallados)       |
| `APP_URL`                           | URL pública de la app                            |
| `VITE_APP_URL`                      | URL que consume Vite (por defecto, `${APP_URL}`) |
| `DB_DSN`, `DB_USER`, `DB_PASS`      | Conexión PDO (y también la de Phinx)             |
| `THROTTLE_LIMIT`, `THROTTLE_WINDOW` | Peticiones por IP y ventana (por defecto 60/60s) |

`THROTTLE_LIMIT` y `THROTTLE_WINDOW` son opcionales: si no se definen, se usan
los valores por defecto de `config/settings.php`.

---

## Cambiar de persistencia

Basta una línea en `config/repositories.php` para usar la implementación en
memoria (por ejemplo, en tests):

```php
TaskRepository::class => autowire(InMemoryTaskRepository::class)
```

---

## Despliegue

Con Docker (PHP-FPM + Nginx vía supervisord):

```bash
docker build -t slim-pb .
docker run -p 8080:80 --env-file .env slim-pb
```

Sobre un servidor tradicional, `deploy.sh` es idempotente y hace: `git pull`,
instala dependencias PHP sin dev, ejecuta las migraciones, compila los assets,
borra `public/hot`, limpia las cachés de PHP-DI y Twig, y ajusta permisos en
`var/`.

Para Apache hay un `apache-vhost.conf.example`. En cualquier servidor, el
`DocumentRoot` debe apuntar a `public/`, nunca a la raíz del proyecto, para que
`vendor/`, `config/` y `.env` queden fuera del alcance público.
