# Documentation

## Setup

Create `api/index.php`:

```php
<?php declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Contributte\Vercel\Application;
use Contributte\Vercel\Middlewares;
use Contributte\Vercel\Request;

$app = new Application();
$app->use(Middlewares::cors());

$app->getRouter()->get('/api/hello', fn(Request $r) => ['message' => 'Hello']);

$app->run();
```

Create `vercel.json`:

```json
{
  "functions": {
    "api/*.php": {
      "runtime": "vercel-php@0.7.2"
    }
  },
  "rewrites": [
    { "source": "/api/(.*)", "destination": "/api/index.php" }
  ]
}
```

## Routing

```php
$router = $app->getRouter();

$router->get('/api/users', fn(Request $r) => ['users' => []]);
$router->post('/api/users', fn(Request $r) => ['created' => true]);
$router->put('/api/users/<id>', fn(Request $r) => ['updated' => true]);
$router->delete('/api/users/<id>', fn(Request $r) => ['deleted' => true]);
$router->any('/api/any', fn(Request $r) => ['method' => $r->getMethod()]);

// Parameters
$router->get('/api/users/<id>', fn(Request $r) => ['id' => $r->getParam('id')]);
$router->get('/api/users/<id \\d+>', fn(Request $r) => ['id' => (int) $r->getParam('id')]);
$router->get('/api/page[/<page=1>]', fn(Request $r) => ['page' => $r->getParam('page')]);
```

## Request

```php
$request->getMethod();           // GET, POST, ...
$request->getPath();             // /api/users
$request->getParam('id');        // Route parameter
$request->getQueryParam('page'); // Query string
$request->getBodyParam('name');  // JSON/form body
$request->getHeader('Authorization');
```

## Response

```php
use Contributte\Vercel\Response;

Response::json(['data' => 'value']);
Response::json(['created' => true], 201);
Response::html('<h1>Hello</h1>');
Response::text('Hello');
Response::redirect('/other');
Response::notFound();
Response::badRequest();
Response::error('Server error', 500);
```

## Middleware

```php
use Contributte\Vercel\Middlewares;

$app->use(Middlewares::cors());
$app->use(Middlewares::cors(
    allowOrigin: 'https://example.com',
    allowMethods: 'GET, POST',
    allowHeaders: 'Content-Type',
    maxAge: 3600
));

// Custom middleware
$app->use(function (Request $request, callable $next) {
    // before
    $response = $next($request);
    // after
    return $response;
});
```

## Error Handling

```php
$app->onNotFound(fn(Request $r) => Response::json(['error' => 'Not found'], 404));
$app->onError(fn(Throwable $e, Request $r) => Response::error($e->getMessage()));
```
