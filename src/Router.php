<?php declare(strict_types = 1);

namespace Contributte\Vercel;

use Nette\Routing\RouteList;

class Router
{

	private RouteList $routeList;

	/** @var array<string, array{handler: callable, method: string|null}> */
	private array $routes = [];

	public function __construct()
	{
		$this->routeList = new RouteList();
	}

	public function getRouteList(): RouteList
	{
		return $this->routeList;
	}

	/**
	 * @param array<string, mixed> $metadata
	 */
	public function get(string $mask, callable $handler, array $metadata = []): self
	{
		return $this->addRoute('GET', $mask, $handler, $metadata);
	}

	/**
	 * @param array<string, mixed> $metadata
	 */
	public function post(string $mask, callable $handler, array $metadata = []): self
	{
		return $this->addRoute('POST', $mask, $handler, $metadata);
	}

	/**
	 * @param array<string, mixed> $metadata
	 */
	public function put(string $mask, callable $handler, array $metadata = []): self
	{
		return $this->addRoute('PUT', $mask, $handler, $metadata);
	}

	/**
	 * @param array<string, mixed> $metadata
	 */
	public function patch(string $mask, callable $handler, array $metadata = []): self
	{
		return $this->addRoute('PATCH', $mask, $handler, $metadata);
	}

	/**
	 * @param array<string, mixed> $metadata
	 */
	public function delete(string $mask, callable $handler, array $metadata = []): self
	{
		return $this->addRoute('DELETE', $mask, $handler, $metadata);
	}

	/**
	 * @param array<string, mixed> $metadata
	 */
	public function options(string $mask, callable $handler, array $metadata = []): self
	{
		return $this->addRoute('OPTIONS', $mask, $handler, $metadata);
	}

	/**
	 * @param array<string, mixed> $metadata
	 */
	public function any(string $mask, callable $handler, array $metadata = []): self
	{
		$routeId = $this->generateRouteId('ANY', $mask);

		$this->routes[$routeId] = [
			'handler' => $handler,
			'method' => null, // null means any method
		];

		$this->routeList->addRoute($mask, array_merge($metadata, [
			'_handler' => $routeId,
		]));

		return $this;
	}

	/**
	 * @param array<string, mixed> $metadata
	 */
	public function addRoute(string $method, string $mask, callable $handler, array $metadata = []): self
	{
		$routeId = $this->generateRouteId($method, $mask);

		$this->routes[$routeId] = [
			'handler' => $handler,
			'method' => $method,
		];

		$this->routeList->addRoute($mask, array_merge($metadata, [
			'_handler' => $routeId,
		]));

		return $this;
	}

	/**
	 * Match request against routes
	 *
	 * @return array{handler: callable, params: array<string, mixed>}|null
	 */
	public function match(Request $request): ?array
	{
		$httpRequest = $request->toNetteHttpRequest();
		$requestMethod = $request->getMethod();

		// Try to find a matching route with correct method
		foreach ($this->routeList->getRouters() as $route) {
			$params = $route->match($httpRequest);
			if ($params === null) {
				continue;
			}

			$handlerId = $params['_handler'] ?? null;
			if ($handlerId === null || !isset($this->routes[$handlerId])) {
				continue;
			}

			$routeData = $this->routes[$handlerId];
			$routeMethod = $routeData['method'];

			// Check HTTP method (null means any method allowed)
			if ($routeMethod !== null && $routeMethod !== $requestMethod) {
				continue;
			}

			// Remove internal params
			unset($params['_handler']);

			return [
				'handler' => $routeData['handler'],
				'params' => $params,
			];
		}

		return null;
	}

	private function generateRouteId(string $method, string $mask): string
	{
		return $method . ':' . $mask . ':' . count($this->routes);
	}

}
