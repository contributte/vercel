<?php declare(strict_types = 1);

namespace Contributte\Vercel\Middleware;

use Contributte\Vercel\Request;
use Contributte\Vercel\Response;

class CorsMiddleware implements Middleware
{

	private string $allowOrigin;

	private string $allowMethods;

	private string $allowHeaders;

	private int $maxAge;

	public function __construct(
		string $allowOrigin = '*',
		string $allowMethods = 'GET, POST, PUT, PATCH, DELETE, OPTIONS',
		string $allowHeaders = 'Content-Type, Authorization, X-Requested-With',
		int $maxAge = 86400
	)
	{
		$this->allowOrigin = $allowOrigin;
		$this->allowMethods = $allowMethods;
		$this->allowHeaders = $allowHeaders;
		$this->maxAge = $maxAge;
	}

	private function createPreflightResponse(): Response
	{
		return Response::noContent()
			->withHeader('Access-Control-Allow-Origin', $this->allowOrigin)
			->withHeader('Access-Control-Allow-Methods', $this->allowMethods)
			->withHeader('Access-Control-Allow-Headers', $this->allowHeaders)
			->withHeader('Access-Control-Max-Age', (string) $this->maxAge);
	}

	private function addCorsHeaders(Response $response): Response
	{
		return $response
			->withHeader('Access-Control-Allow-Origin', $this->allowOrigin)
			->withHeader('Access-Control-Allow-Methods', $this->allowMethods)
			->withHeader('Access-Control-Allow-Headers', $this->allowHeaders)
			->withHeader('Access-Control-Max-Age', (string) $this->maxAge);
	}

	private function sendCorsHeaders(): void
	{
		header('Access-Control-Allow-Origin: ' . $this->allowOrigin);
		header('Access-Control-Allow-Methods: ' . $this->allowMethods);
		header('Access-Control-Allow-Headers: ' . $this->allowHeaders);
		header('Access-Control-Max-Age: ' . $this->maxAge);
	}

	/**
	 * @return Response|array<mixed>|string|null
	 */
	public function __invoke(Request $request, callable $next): Response|array|string|null
	{
		// Handle preflight request
		if ($request->isMethod('OPTIONS')) {
			return $this->createPreflightResponse();
		}

		// Process request
		$response = $next($request);

		// Add CORS headers to response
		if ($response instanceof Response) {
			return $this->addCorsHeaders($response);
		}

		// For non-Response returns, headers will be added by Application
		$this->sendCorsHeaders();

		if (is_array($response) || is_string($response) || $response === null) {
			return $response;
		}

		// Convert other types to array for JSON encoding
		return ['data' => $response];
	}

}
