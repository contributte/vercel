<?php declare(strict_types = 1);

namespace Contributte\Vercel\Middleware;

use Contributte\Vercel\Request;
use Contributte\Vercel\Response;

interface Middleware
{

	/**
	 * Process the request and return a response or pass to next middleware.
	 *
	 * @param callable(Request): (Response|mixed) $next
	 * @return Response|array<mixed>|string|null
	 */
	public function __invoke(Request $request, callable $next): Response|array|string|null;

}
