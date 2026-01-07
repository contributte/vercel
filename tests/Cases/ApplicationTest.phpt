<?php declare(strict_types = 1);

namespace Tests\Cases;

use Contributte\Tester\Toolkit;
use Contributte\Vercel\Application;
use Contributte\Vercel\Middleware\Middleware;
use Contributte\Vercel\Middlewares;
use Contributte\Vercel\Request;
use Contributte\Vercel\Response;
use Contributte\Vercel\Router;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

// Create with default router
Toolkit::test(function (): void {
	$app = new Application();

	Assert::type(Router::class, $app->getRouter());
});

// Create with custom router
Toolkit::test(function (): void {
	$router = new Router();
	$app = new Application($router);

	Assert::same($router, $app->getRouter());
});

// Handle matching route
Toolkit::test(function (): void {
	$app = new Application();
	$app->getRouter()->get('/api/hello', fn (Request $request): array => ['message' => 'Hello']);

	$request = new Request('GET', '/api/hello');
	$result = $app->handle($request);

	Assert::same(['message' => 'Hello'], $result);
});

// Handle route with params
Toolkit::test(function (): void {
	$app = new Application();
	$app->getRouter()->get('/api/users/<id>', fn (Request $request): array => ['id' => $request->getParam('id')]);

	$request = new Request('GET', '/api/users/42');
	$result = $app->handle($request);

	Assert::same(['id' => '42'], $result);
});

// Handle not found
Toolkit::test(function (): void {
	$app = new Application();
	$app->getRouter()->get('/api/hello', fn (Request $request): array => ['message' => 'Hello']);

	$request = new Request('GET', '/api/goodbye');
	$result = $app->handle($request);

	Assert::type(Response::class, $result);
	Assert::same(404, $result->getStatusCode());
});

// Custom not found handler
Toolkit::test(function (): void {
	$app = new Application();
	$app->onNotFound(fn (Request $request): array => ['error' => 'Custom not found', 'path' => $request->getPath()]);

	$request = new Request('GET', '/unknown');
	$result = $app->handle($request);

	Assert::same(['error' => 'Custom not found', 'path' => '/unknown'], $result);
});

// Response object
Toolkit::test(function (): void {
	$app = new Application();
	$app->getRouter()->get('/api/json', fn (Request $request): Response => Response::json(['success' => true], 201));

	$request = new Request('GET', '/api/json');
	$result = $app->handle($request);

	Assert::type(Response::class, $result);
	Assert::same(201, $result->getStatusCode());
});

// Use middleware returns application
Toolkit::test(function (): void {
	$app = new Application();
	$result = $app->use(Middlewares::cors());

	Assert::same($app, $result);
});

// On not found returns application
Toolkit::test(function (): void {
	$app = new Application();
	$result = $app->onNotFound(function (): void {
	});

	Assert::same($app, $result);
});

// On error returns application
Toolkit::test(function (): void {
	$app = new Application();
	$result = $app->onError(function (): void {
	});

	Assert::same($app, $result);
});

// Middleware is called
Toolkit::test(function (): void {
	$app = new Application();
	$tracker = new \stdClass();
	$tracker->called = false;

	$app->use(function (Request $request, callable $next) use ($tracker): mixed {
		$tracker->called = true;

		return $next($request);
	});

	$app->getRouter()->get('/api/test', fn (Request $request): array => ['test' => true]);

	$request = new Request('GET', '/api/test');
	$result = $app->handle($request);

	Assert::true($tracker->called);
	Assert::same(['test' => true], $result);
});

// Middlewares factory creates CORS middleware
Toolkit::test(function (): void {
	$middleware = Middlewares::cors();

	Assert::type(Middleware::class, $middleware);
});
