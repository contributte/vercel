<?php declare(strict_types = 1);

namespace Tests\Cases\Middleware;

use Contributte\Tester\Toolkit;
use Contributte\Vercel\Middleware\CorsMiddleware;
use Contributte\Vercel\Middleware\Middleware;
use Contributte\Vercel\Request;
use Contributte\Vercel\Response;
use Tester\Assert;

require_once __DIR__ . '/../../bootstrap.php';

// Implements MiddlewareInterface
Toolkit::test(function (): void {
	$middleware = new CorsMiddleware();

	Assert::type(Middleware::class, $middleware);
});

// Preflight request returns 204 response
Toolkit::test(function (): void {
	$middleware = new CorsMiddleware();
	$request = new Request('OPTIONS', '/api/test');

	$response = $middleware($request, fn (Request $r): array => ['test' => true]);

	Assert::type(Response::class, $response);
	Assert::same(204, $response->getStatusCode());
	Assert::same('*', $response->getHeaders()['Access-Control-Allow-Origin']);
});

// Response has CORS headers
Toolkit::test(function (): void {
	$middleware = new CorsMiddleware();
	$request = new Request('GET', '/api/test');

	$response = $middleware($request, fn (Request $r): Response => Response::json(['test' => true]));

	Assert::type(Response::class, $response);
	Assert::same('*', $response->getHeaders()['Access-Control-Allow-Origin']);
	Assert::contains('GET', $response->getHeaders()['Access-Control-Allow-Methods']);
});

// Custom CORS configuration
Toolkit::test(function (): void {
	$middleware = new CorsMiddleware(
		allowOrigin: 'https://example.com',
		allowMethods: 'GET, POST',
		allowHeaders: 'Content-Type',
		maxAge: 3600
	);

	$request = new Request('OPTIONS', '/api/test');
	$response = $middleware($request, fn (Request $r): array => ['test' => true]);

	Assert::same('https://example.com', $response->getHeaders()['Access-Control-Allow-Origin']);
	Assert::same('GET, POST', $response->getHeaders()['Access-Control-Allow-Methods']);
	Assert::same('Content-Type', $response->getHeaders()['Access-Control-Allow-Headers']);
	Assert::same('3600', $response->getHeaders()['Access-Control-Max-Age']);
});

// Passes to next middleware
Toolkit::test(function (): void {
	$middleware = new CorsMiddleware();
	$request = new Request('GET', '/api/test');
	$tracker = new \stdClass();
	$tracker->called = false;

	$middleware($request, function (Request $r) use ($tracker): Response {
		$tracker->called = true;

		return Response::json(['test' => true]);
	});

	Assert::true($tracker->called);
});
