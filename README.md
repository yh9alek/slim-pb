<div align="center">
  <img src="https://raw.githubusercontent.com/yh9alek/miscellaneous/refs/heads/main/src/SlimVite/imgs/slim-vite.png">
</div>

# slim-pb

Scaffold para arrancar proyectos Full Stack: backend PHP con **Slim 4** +
frontend con **Vite 8**, Tailwind CSS y DaisyUI. Las vistas se renderizan en el
servidor (SSR) usando Twig y JavaScript para añadir interactividad encima.

Trae un módulo de ejemplo (`tasks`) con API JSON e interfaz web sobre el mismo
recurso. **El instalador pregunta si quieres conservarlo**; si lo descartas, el
proyecto queda limpio y listo para tu primer módulo.

---

## Stack

| Capa       | Herramientas                                                     |
| ---------- | ---------------------------------------------------------------- |
| Backend    | PHP 8.4+, Slim 4, PHP-DI, Twig, Monolog, Symfony Validator, PDO  |
| Frontend   | Vite 8, Tailwind CSS 4, DaisyUI 5, Axios, SweetAlert2            |
| Base datos | Phinx (migraciones y seeders), Faker                             |
| Calidad    | Pest 4, PHPStan (lvl 8), PHP-CS-Fixer (PER)                      |
| Despliegue | Docker (PHP-FPM + Nginx), Apache/Nginx tradicional, `deploy.sh`  |

---

## Crear un proyecto

Requisitos: PHP >= 8.4.1, [Composer](https://getcomposer.org/) y [Bun](https://bun.sh).

```bash
composer create-project yh9alek/slim-pb mi-proyecto
cd mi-proyecto

php scripts/setup.php     # nombre, descripción e indicar si se conserva el módulo de ejemplo

bun install --ignore-scripts

# rellena variables de entorno en .env

composer run migrate      # crea las tablas de la BD
composer run seed         # opcional: datos de ejemplo

composer run dev
```

`create-project` copia `.env.example` a `.env` automáticamente. El paso
`php scripts/setup.php` es interactivo y hace tres cosas:

1. Escribe el nombre y la descripción del paquete en `composer.json`.
2. Pregunta si deseas conservar el módulo de ejemplo (`tasks`). Si respondes que no,
   elimina sus archivos y deja rutas, contenedor, seeders y portada limpios.
3. Se borra a sí mismo, junto con la carpeta `scripts/` y el hook de Composer.

Hay que ejecutarlo a mano porque Composer no conecta la consola a los scripts
que lanza, y sin ella no puede preguntar nada.

`composer run dev` levanta **los dos servidores a la vez** (PHP y Vite) con
salida etiquetada al estilo de frameworks como Laravel:

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

Las rutas marcadas con **(demo)** desaparecen si descartas el módulo de ejemplo.

```
public/index.php ............ Front controller: bootstrap, contenedor, app
public/build/ ............... Assets compilados por Vite (generado)
public/hot .................. Marca el modo dev de Vite (generado, efímero)
public/.htaccess ............ Reescritura a index.php (Apache)

bootstrap/app.php ........... Construye la app: contenedor, config, rutas
server.php .................. Router del servidor embebido de PHP
serve.mjs ................... Arranca el servidor PHP con salida formateada
scripts/setup.php ........... Instalador interactivo (se autoelimina)

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
  seeds/DatabaseSeeder.php .. Orquesta los seeders
  seeds/TaskSeeder.php ...... (demo) Datos de ejemplo con Faker
phinx.php ................... Config de Phinx: deriva el adaptador del DB_DSN

src/Domain/ ................. Núcleo sin framework
  Shared/ ................... Excepciones (NotFound, Validation)
  Task/Task.php ............. (demo) Entidad
  Task/TaskRepository.php ... (demo) Contrato de persistencia

src/Application/ ............ Casos de uso y borde HTTP
  Http/Api.php .............. Acción base: serialización JSON
  Http/Controller/WebController.php ....... Portada
  Http/Controller/TaskController.php ...... (demo) API
  Http/Controller/TaskWebController.php ... (demo) Web
  Service/TaskService.php ... (demo) Lógica de negocio
  DTO/TaskInput.php ......... (demo) Entrada + reglas (#[Assert\*])
  Core/Settings/ ............ Acceso tipado a la configuración
  Core/Validation/ .......... Validator (interfaz) + adaptador de Symfony
  Core/Handler/ ............. Excepción → respuesta HTTP; página de debug
  Core/Middleware/ .......... JSON body, throttling, sondas de DevTools
  Core/Throttle/ ............ Límite de peticiones por IP (store en fichero)
  Core/Asset/ ............... Lee el manifest de Vite para las plantillas

src/Infrastructure/
  Persistence/PdoTaskRepository.php ...... (demo) Implementación SQL
  Persistence/InMemoryTaskRepository.php . (demo) Implementación para tests

templates/
  views/layouts/ ............ Layout base
  views/errors/ ............. Plantilla de error
  views/pages/home/ ......... Portada
  views/pages/tasks/ ........ (demo) Lista y formulario
  views/components/ ......... (demo) task-form.twig
  css/app.css ............... Tailwind + DaisyUI
  js/ ....................... Frontend por módulos (ver abajo)

tests/
  Pest.php .................. Helpers: testApp(), apiRequest(), jsonBody()
  TestCase.php .............. TestCase base
  Unit/TaskServiceTest.php .. (demo) Lógica de negocio aislada
  Feature/TaskApiTest.php ... (demo) Pila HTTP completa
  Feature/ArchTest.php ...... Reglas de arquitectura
```

---

## Rutas

**Web (HTML)**

| Método | Ruta            | Descripción                   |
| ------ | --------------- | ----------------------------- |
| GET    | `/`             | Página de bienvenida          |
| GET    | `/health`       | Healthcheck (204)             |
| GET    | `/tasks`        | (demo) Lista de tareas        |
| GET    | `/tasks/create` | (demo) Formulario de creación |

**API (JSON)** — solo con el módulo de ejemplo

| Método | Ruta              | Descripción    |
| ------ | ----------------- | -------------- |
| GET    | `/api/tasks`      | Listar         |
| GET    | `/api/tasks/{id}` | Ver una        |
| POST   | `/api/tasks`      | Crear          |
| PUT    | `/api/tasks/{id}` | Actualizar     |
| DELETE | `/api/tasks/{id}` | Eliminar (204) |

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
5. El controlador arma un DTO de entrada, lo valida y delega en el servicio.
6. El servicio usa el contrato del repositorio; la implementación PDO ejecuta
   el SQL.
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

Con el módulo de ejemplo, la migración inicial crea la tabla `tasks` (`id`,
`title`, `completed`, `created_at`, `updated_at`) y `TaskSeeder` la puebla con
Faker. Si lo descartaste, no hay migraciones ni seeders que ejecutar hasta que
crees los tuyos: `composer run migrate` y `composer run seed` no harán nada.

---

## Frontend

Un módulo por recurso, con la misma estructura siempre.
Esta misma está inspirada en el curso [JavaScript Moderno: Guía para dominar el lenguaje](https://www.udemy.com/course/javascript-fernando-herrera/?utm_campaign=Search_DSA_Alpha_Prof_la.ES_cc.MX_Subs&utm_source=google&utm_medium=paid-search&portfolio=Mexico&utm_audience=mx&utm_tactic=nb&utm_term=_._ag_185300353676_._ad_773433175635_._kw_&utm_content=g&funnel=&test=&gad_source=1&gad_campaignid=23001129117&gbraid=0AAAAADROdO0WxZ45r1UOQVoVkV2xeopal&gclid=CjwKCAjwmozTBhAeEiwAkEGZzmGct-O4o9oL25MjG2nOCYNo4izPFvnnfqidiaP0GYwi0VQp3Pa2JhoC7LgQAvD_BwE&couponCode=PMNVD2525)

```
templates/js/
  app.js ......................... Entrada común (carga el CSS)
  lib/http.js .................... Axios + interceptores (avisa de 400/500)
  lib/toast.js ................... Toasts (esquina inferior derecha)
  tasks/                           (demo) Módulo de referencia
    api/tasks.api.js ............. Llamadas a /api/tasks
    store/store.js ............... Estado del módulo
    use-cases/ ................... Acciones (toggle, delete, render…)
    presentation/ ................ Entradas de Vite, una por vista
```

**Convención:** cada archivo en `<módulo>/presentation/*.js` es una entrada de
Vite y se detecta sola (no hay que tocar `vite.config.js`). Para usarla en la
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

- **`tests/Unit`** — lógica de negocio aislada (`TaskServiceTest`, demo).
- **`tests/Feature/TaskApiTest`** (demo) — ejercita la pila HTTP completa
  (router, middleware, validación, manejador de errores) contra
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

Basta una línea en `config/repositories.php` para cambiar qué implementación
resuelve el contenedor:

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

---

## Mantener el scaffold

Los cambios en este repositorio no llegan a `create-project` hasta que se
publica una versión nueva:

```bash
git add -A
git commit -m "Mejoras en el scaffold"
git push
git tag v1.1.0
git push origin v1.1.0
```

Packagist recoge el tag en segundos vía webhook. Para probar la rama sin
taggear:

```bash
composer create-project yh9alek/slim-pb prueba dev-main
```
