<?php declare(strict_types = 1);

namespace Contributte\Vercel;

use Contributte\Vercel\Middleware\Middleware;
use Throwable;

class Application
{

	private Router $router;

	/** @var array<Middleware|callable> */
	private array $middlewares = [];

	/** @var callable|null */
	private $notFoundHandler = null;

	/** @var callable|null */
	private $errorHandler = null;

	public function __construct(?Router $router = null)
	{
		$this->router = $router ?? new Router();
	}

	public function getRouter(): Router
	{
		return $this->router;
	}

	/**
	 * Add middleware to the application.
	 */
	public function use(Middleware|callable $middleware): self
	{
		$this->middlewares[] = $middleware;

		return $this;
	}

	public function onNotFound(callable $handler): self
	{
		$this->notFoundHandler = $handler;

		return $this;
	}

	public function onError(callable $handler): self
	{
		$this->errorHandler = $handler;

		return $this;
	}

	public function run(?Request $request = null): void
	{
		$request ??= Request::fromGlobals();

		try {
			$response = $this->processMiddlewares($request);
			$this->sendResponse($response);
		} catch (Throwable $e) {
			$this->handleError($e, $request);
		}
	}

	public function handle(Request $request): mixed
	{
		return $this->processMiddlewares($request);
	}

	private function routeHandler(Request $request): mixed
	{
		$match = $this->router->match($request);

		if ($match === null) {
			return $this->handleNotFound($request);
		}

		$handler = $match['handler'];
		$params = $match['params'];

		// Add route params to request
		$request = $request->withParams($params);

		return $handler($request);
	}

	private function processMiddlewares(Request $request): mixed
	{
		$middlewares = $this->middlewares;
		$handler = fn (Request $req): mixed => $this->routeHandler($req);

		// Build middleware chain from end to start
		while ($middleware = array_pop($middlewares)) {
			$next = $handler;
			$handler = fn (Request $req): mixed => $middleware($req, $next);
		}

		return $handler($request);
	}

	private function handleNotFound(Request $request): mixed
	{
		if ($this->notFoundHandler !== null) {
			return ($this->notFoundHandler)($request);
		}

		http_response_code(404);

		return Response::notFound(sprintf('Endpoint not found: %s', $request->getPath()));
	}

	private function handleError(Throwable $e, Request $request): void
	{
		if ($this->errorHandler !== null) {
			$response = ($this->errorHandler)($e, $request);
			$this->sendResponse($response);

			return;
		}

		http_response_code(500);
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode([
			'error' => 'Internal Server Error',
			'message' => $e->getMessage(),
		], JSON_PRETTY_PRINT);
	}

	private function sendResponse(mixed $response): void
	{
		if ($response instanceof Response) {
			$response->send();

			return;
		}

		if (is_array($response) || is_object($response)) {
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

			return;
		}

		if (is_string($response)) {
			echo $response;

			return;
		}

		if (is_int($response) || is_float($response)) {
			echo (string) $response;
		}
	}

}
