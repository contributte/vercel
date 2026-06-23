![](https://heatbadger.now.sh/github/readme/contributte/vercel/)

<p align=center>
	<a href="https://github.com/contributte/vercel/actions"><img src="https://badgen.net/github/checks/contributte/vercel/master"></a>
	<a href="https://coveralls.io/github/contributte/vercel"><img src="https://badgen.net/coveralls/c/github/contributte/vercel"></a>
	<a href="https://packagist.org/packages/contributte/vercel"><img src="https://badgen.net/packagist/dt/contributte/vercel"></a>
	<a href="https://packagist.org/packages/contributte/vercel"><img src="https://badgen.net/packagist/v/contributte/vercel"></a>
	<a href="https://github.com/phpstan/phpstan"><img src="https://badgen.net/badge/PHPStan/level%209/green"></a>
</p>
<p align=center>
	<a href="https://packagist.org/packages/contributte/vercel"><img src="https://badgen.net/packagist/php/contributte/vercel"></a>
	<a href="https://github.com/contributte/vercel"><img src="https://badgen.net/github/license/contributte/vercel"></a>
	<a href="https://bit.ly/ctteg"><img src="https://badgen.net/badge/support/gitter/cyan"></a>
	<a href="https://bit.ly/cttfo"><img src="https://badgen.net/badge/support/forum/yellow"></a>
	<a href="https://contributte.org/partners.html"><img src="https://badgen.net/badge/sponsor/donations/F96854"></a>
</p>

<p align=center>
Website <a href="https://contributte.org">contributte.org</a> | Contact <a href="https://f3l1x.io">f3l1x.io</a> | Twitter <a href="https://twitter.com/contributte">@contributte</a>
</p>

Simple PHP framework for routing Vercel serverless functions with requests, responses and middleware.

## Versions

| State | Version | Branch   | Nette  | PHP     |
|-------|---------|----------|--------|---------|
| dev   | `^0.1`  | `master` | `3.2+` | `>=8.2` |

## Installation

To install the latest version of `contributte/vercel` use [Composer](https://getcomposer.com).

```bash
composer require contributte/vercel
```

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

## Development

See [how to contribute](https://contributte.org/contributing.html) to this package.

This package is currently maintained by these authors.

<a href="https://github.com/f3l1x">
    <img width="80" height="80" src="https://avatars.githubusercontent.com/f3l1x">
</a>

---

Consider to [support](https://github.com/sponsors/f3l1x) **contributte** development team. Also thank you for using this package.
