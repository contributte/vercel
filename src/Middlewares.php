<?php declare(strict_types = 1);

namespace Contributte\Vercel;

use Contributte\Vercel\Middleware\CorsMiddleware;
use Contributte\Vercel\Middleware\Middleware;

/**
 * Factory for common middlewares.
 */
final class Middlewares
{

	/**
	 * Create CORS middleware with default settings.
	 */
	public static function cors(
		string $allowOrigin = '*',
		string $allowMethods = 'GET, POST, PUT, PATCH, DELETE, OPTIONS',
		string $allowHeaders = 'Content-Type, Authorization, X-Requested-With',
		int $maxAge = 86400
	): Middleware
	{
		return new CorsMiddleware($allowOrigin, $allowMethods, $allowHeaders, $maxAge);
	}

}
