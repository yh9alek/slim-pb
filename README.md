# Flujo HTTP completo Slim 4

Esqueleto de un backend PHP por capas que demuestra el recorrido de una
petición: front controller → router → middleware → controlador → servicio →
repositorio → base de datos, y la respuesta de vuelta.

## Estructura y su lugar en el flujo

```
public/index.php ............. Front controller: bootstrap, contenedor, app
config/settings.php .......... Configuración (entorno, BD, logger)
config/dependencies.php ...... Definiciones del contenedor (PSR-11)
config/repositories.php ........ Definiciones de repositorios
config/middleware.php ........ Pipeline: JSON body parser, routing, errores
config/routes.php ............ Router: método + URI → acción

src/Domain/ .................. Núcleo sin framework
  Task/Task.php .............. Entidad inmutable
  Task/TaskRepository.php .... Contrato de persistencia (interfaz)
  Task/Exception/ ............ Excepciones de dominio

src/Application/ ............. Casos de uso y borde HTTP
  Action/ ................... Controladores (uno por endpoint)
  Service/TaskService.php ... Lógica de negocio
  DTO/CreateTaskInput.php ... Validación y transporte de la entrada
  Middleware/ ............... Middleware PSR-15 (parseo JSON)
  Handler/ .................. Traducción de excepciones a HTTP

src/Infrastructure/ ......... Detalles concretos
  Persistence/PdoTaskRepository.php ...... Implementación SQL
  Persistence/InMemoryTaskRepository.php . Implementación para tests
```

## Recorrido de una petición `POST /tasks`

1. El servidor web reescribe todo hacia `public/index.php` (front controller).
2. El front controller carga el `.env`, construye el contenedor y la app Slim.
3. El `JsonBodyParserMiddleware` decodifica el cuerpo JSON.
4. El router empareja `POST /tasks` con `CreateTaskAction`.
5. La acción arma un `CreateTaskInput` (que valida) y delega en `TaskService`.
6. El servicio crea la entidad `Task` y la pasa al `TaskRepository`.
7. `PdoTaskRepository` ejecuta el `INSERT` y devuelve la tarea con su `id`.
8. La acción serializa la respuesta a JSON (`201 Created`).
9. Si algo lanza una excepción, `HttpErrorHandler` la convierte en `404`,
   `422` o `500` de forma centralizada.

## Puesta en marcha

```bash
composer install
cp .env.example .env

php -S localhost:8080 -t public
```

## Probar

```bash
curl localhost:8080/tasks
curl -X POST localhost:8080/tasks \
  -H 'Content-Type: application/json' \
  -d '{"title":"Comprar pan"}'
curl localhost:8080/tasks/1
```

## Cambiar de persistencia

Para usar la implementación en memoria (por ejemplo en tests), basta una línea
en `config/dependencies.php`:

```php
TaskRepository::class => autowire(InMemoryTaskRepository::class)
