<?php declare(strict_types = 1);

namespace Tests\Cases;

use Contributte\Tester\Toolkit;
use Contributte\Vercel\Request;
use Contributte\Vercel\Router;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

// Simple GET route
Toolkit::test(function (): void {
	$router = new Router();
	$tracker = new \stdClass();
	$tracker->called = false;

	$router->get('/api/hello', function (Request $request) use ($tracker): array {
		$tracker->called = true;

		return ['message' => 'Hello'];
	});

	$request = new Request('GET', '/api/hello');
	$match = $router->match($request);

	Assert::notNull($match);
	Assert::type('callable', $match['handler']);
	Assert::same([], $match['params']);

	// Execute handler
	$result = $match['handler']($request);
	Assert::true($tracker->called);
	Assert::same(['message' => 'Hello'], $result);
});

// Route with parameter
Toolkit::test(function (): void {
	$router = new Router();

	$router->get('/api/users/<id>', fn (Request $request): array => ['id' => $request->getParam('id')]);

	$request = new Request('GET', '/api/users/123');
	$match = $router->match($request);

	Assert::notNull($match);
	Assert::same('123', $match['params']['id']);
});

// Route with multiple parameters
Toolkit::test(function (): void {
	$router = new Router();

	$router->get('/api/<module>/<action>', fn (Request $request): array => [
			'module' => $request->getParam('module'),
			'action' => $request->getParam('action'),
		]);

	$request = new Request('GET', '/api/users/list');
	$match = $router->match($request);

	Assert::notNull($match);
	Assert::same('users', $match['params']['module']);
	Assert::same('list', $match['params']['action']);
});

// Route with optional parameter
Toolkit::test(function (): void {
	$router = new Router();

	$router->get('/api/users[/<id>]', fn (Request $request): array => ['id' => $request->getParam('id')]);

	// With parameter
	$request1 = new Request('GET', '/api/users/42');
	$match1 = $router->match($request1);
	Assert::notNull($match1);
	Assert::same('42', $match1['params']['id']);

	// Without parameter
	$request2 = new Request('GET', '/api/users');
	$match2 = $router->match($request2);
	Assert::notNull($match2);
	Assert::null($match2['params']['id'] ?? null);
});

// Route with regex constraint
Toolkit::test(function (): void {
	$router = new Router();

	$router->get('/api/users/<id \d+>', fn (Request $request): array => ['id' => $request->getParam('id')]);

	// Valid numeric ID
	$request1 = new Request('GET', '/api/users/123');
	$match1 = $router->match($request1);
	Assert::notNull($match1);

	// Invalid non-numeric ID
	$request2 = new Request('GET', '/api/users/abc');
	$match2 = $router->match($request2);
	Assert::null($match2);
});

// POST route
Toolkit::test(function (): void {
	$router = new Router();

	$router->post('/api/users', fn (Request $request): array => ['created' => true]);

	// POST request should match
	$request1 = new Request('POST', '/api/users');
	$match1 = $router->match($request1);
	Assert::notNull($match1);

	// GET request should not match
	$request2 = new Request('GET', '/api/users');
	$match2 = $router->match($request2);
	Assert::null($match2);
});

// PUT route
Toolkit::test(function (): void {
	$router = new Router();

	$router->put('/api/users/<id>', fn (Request $request): array => ['updated' => true]);

	$request = new Request('PUT', '/api/users/1');
	$match = $router->match($request);

	Assert::notNull($match);
	Assert::same('1', $match['params']['id']);
});

// PATCH route
Toolkit::test(function (): void {
	$router = new Router();

	$router->patch('/api/users/<id>', fn (Request $request): array => ['patched' => true]);

	$request = new Request('PATCH', '/api/users/1');
	$match = $router->match($request);

	Assert::notNull($match);
});

// DELETE route
Toolkit::test(function (): void {
	$router = new Router();

	$router->delete('/api/users/<id>', fn (Request $request): array => ['deleted' => true]);

	$request = new Request('DELETE', '/api/users/1');
	$match = $router->match($request);

	Assert::notNull($match);
});

// Any route
Toolkit::test(function (): void {
	$router = new Router();

	$router->any('/api/any', fn (Request $request): array => ['method' => $request->getMethod()]);

	$methods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];

	foreach ($methods as $method) {
		$request = new Request($method, '/api/any');
		$match = $router->match($request);
		Assert::notNull($match, sprintf('Method %s should match', $method));
	}
});

// No match
Toolkit::test(function (): void {
	$router = new Router();

	$router->get('/api/hello', fn (Request $request): array => ['message' => 'Hello']);

	$request = new Request('GET', '/api/goodbye');
	$match = $router->match($request);

	Assert::null($match);
});

// Multiple routes
Toolkit::test(function (): void {
	$router = new Router();

	$router->get('/api/users', fn (Request $request): array => ['action' => 'list']);

	$router->get('/api/users/<id>', fn (Request $request): array => ['action' => 'detail']);

	$router->post('/api/users', fn (Request $request): array => ['action' => 'create']);

	// List
	$request1 = new Request('GET', '/api/users');
	$match1 = $router->match($request1);
	Assert::notNull($match1);
	$result1 = $match1['handler'](new Request());
	Assert::same('list', $result1['action']);

	// Detail
	$request2 = new Request('GET', '/api/users/1');
	$match2 = $router->match($request2);
	Assert::notNull($match2);
	$result2 = $match2['handler'](new Request());
	Assert::same('detail', $result2['action']);

	// Create
	$request3 = new Request('POST', '/api/users');
	$match3 = $router->match($request3);
	Assert::notNull($match3);
	$result3 = $match3['handler'](new Request());
	Assert::same('create', $result3['action']);
});

// Route with default value
Toolkit::test(function (): void {
	$router = new Router();

	$router->get('/api/page[/<page=1>]', fn (Request $request): array => ['page' => $request->getParam('page')]);

	// With page
	$request1 = new Request('GET', '/api/page/5');
	$match1 = $router->match($request1);
	Assert::notNull($match1);
	Assert::same('5', $match1['params']['page']);

	// Without page (default)
	$request2 = new Request('GET', '/api/page');
	$match2 = $router->match($request2);
	Assert::notNull($match2);
	Assert::same('1', $match2['params']['page']);
});

// Get route list
Toolkit::test(function (): void {
	$router = new Router();
	$routeList = $router->getRouteList();

	Assert::type('Nette\Routing\RouteList', $routeList);
});
