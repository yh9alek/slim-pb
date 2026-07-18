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

| Capa       | Herramientas                                                        |
| ---------- | ------------------------------------------------------------------- |
| Backend    | PHP 8.4+, Slim 4, PHP-DI, Twig, Monolog, Symfony Validator, PDO     |
| Frontend   | Vite 8, Tailwind CSS 4, DaisyUI 5, Axios, SweetAlert2               |
| Calidad    | Pest, PHPStan (lvl 8), PHP-CS-Fixer (PER)                           |
| Despliegue | Docker (PHP-FPM + Nginx), `deploy.sh`                               |

---

## Puesta en marcha

Requisitos: PHP >= 8.4.1, Composer y [Bun](https://bun.sh).

```bash
composer install
bun install
cp .env.example .env      # y rellena DB_DSN, DB_USER, DB_PASS

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
```

---

## Rutas

**Web (HTML)**

| Método | Ruta                | Descripción            |
| ------ | ------------------- | ---------------------- |
| GET    | `/`                 | Página de bienvenida   |
| GET    | `/tasks`            | Lista de tareas        |
| GET    | `/tasks/create`     | Formulario de creación |
| GET    | `/tasks/{id}/edit`  | Formulario de edición  |
| GET    | `/health`           | Healthcheck (204)      |

**API (JSON)**

| Método | Ruta               | Descripción       |
| ------ | ------------------ | ----------------- |
| GET    | `/api/tasks`       | Listar            |
| GET    | `/api/tasks/{id}`  | Ver una           |
| POST   | `/api/tasks`       | Crear             |
| PUT    | `/api/tasks/{id}`  | Actualizar        |
| DELETE | `/api/tasks/{id}`  | Eliminar (204)    |

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

Los avisos al usuario están repartidos así: **modal** (SweetAlert2) para errores
400/500 y de red, disparado automáticamente desde `lib/http.js`; **toast** para
confirmaciones breves (`toast.success('Tarea eliminada')`).

---

## Comandos

```bash
composer run dev          # PHP + Vite a la vez (desarrollo)
composer run serve        # solo el servidor PHP
bun run build             # compila los assets a public/build

composer run cs:fix       # formateo de código
composer run phpstan      # análisis estático
composer run test         # Pest
composer run test:coverage
```

---

## Variables de entorno

| Variable                            | Para qué                                         |
| ---------------------------------   | -----------------------------------------        |
| `APP_ENV`                           | `dev` o `prod` (activa errores detallados)       |
| `APP_URL`                           | URL pública de la app                            |
| `DB_DSN`, `DB_USER`, `DB_PASS`      | Conexión PDO                                     |
| `THROTTLE_LIMIT`, `THROTTLE_WINDOW` | Peticiones por IP y ventana (por defecto 60/60s) |

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

O sobre un servidor con `deploy.sh`, que hace `git pull`, instala dependencias,
compila los assets y limpia la caché.
