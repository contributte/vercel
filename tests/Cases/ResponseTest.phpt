<?php declare(strict_types = 1);

namespace Tests\Cases;

use Contributte\Tester\Toolkit;
use Contributte\Vercel\Response;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

// JSON response
Toolkit::test(function (): void {
	$response = Response::json(['message' => 'Hello']);

	Assert::same(200, $response->getStatusCode());
	Assert::same('application/json; charset=utf-8', $response->getHeaders()['Content-Type']);
	Assert::contains('"message": "Hello"', $response->getBody());
});

// JSON response with status
Toolkit::test(function (): void {
	$response = Response::json(['created' => true], 201);

	Assert::same(201, $response->getStatusCode());
});

// HTML response
Toolkit::test(function (): void {
	$response = Response::html('<h1>Hello</h1>');

	Assert::same(200, $response->getStatusCode());
	Assert::same('text/html; charset=utf-8', $response->getHeaders()['Content-Type']);
	Assert::same('<h1>Hello</h1>', $response->getBody());
});

// Text response
Toolkit::test(function (): void {
	$response = Response::text('Hello World');

	Assert::same(200, $response->getStatusCode());
	Assert::same('text/plain; charset=utf-8', $response->getHeaders()['Content-Type']);
	Assert::same('Hello World', $response->getBody());
});

// Redirect response
Toolkit::test(function (): void {
	$response = Response::redirect('https://example.com');

	Assert::same(302, $response->getStatusCode());
	Assert::same('https://example.com', $response->getHeaders()['Location']);
});

// Redirect with custom status
Toolkit::test(function (): void {
	$response = Response::redirect('https://example.com', 301);

	Assert::same(301, $response->getStatusCode());
});

// Not found response
Toolkit::test(function (): void {
	$response = Response::notFound();

	Assert::same(404, $response->getStatusCode());
	Assert::contains('"error": "Not Found"', $response->getBody());
});

// Not found with message
Toolkit::test(function (): void {
	$response = Response::notFound('User not found');

	Assert::same(404, $response->getStatusCode());
	Assert::contains('"error": "User not found"', $response->getBody());
});

// Error response
Toolkit::test(function (): void {
	$response = Response::error();

	Assert::same(500, $response->getStatusCode());
	Assert::contains('"error": "Internal Server Error"', $response->getBody());
});

// Error with custom message
Toolkit::test(function (): void {
	$response = Response::error('Database error', 503);

	Assert::same(503, $response->getStatusCode());
	Assert::contains('"error": "Database error"', $response->getBody());
});

// Bad request response
Toolkit::test(function (): void {
	$response = Response::badRequest();

	Assert::same(400, $response->getStatusCode());
	Assert::contains('"error": "Bad Request"', $response->getBody());
});

// Unauthorized response
Toolkit::test(function (): void {
	$response = Response::unauthorized();

	Assert::same(401, $response->getStatusCode());
	Assert::contains('"error": "Unauthorized"', $response->getBody());
});

// Forbidden response
Toolkit::test(function (): void {
	$response = Response::forbidden();

	Assert::same(403, $response->getStatusCode());
	Assert::contains('"error": "Forbidden"', $response->getBody());
});

// No content response
Toolkit::test(function (): void {
	$response = Response::noContent();

	Assert::same(204, $response->getStatusCode());
	Assert::same('', $response->getBody());
});

// With status
Toolkit::test(function (): void {
	$response = Response::json(['data' => 'test']);
	$newResponse = $response->withStatus(201);

	Assert::same(200, $response->getStatusCode());
	Assert::same(201, $newResponse->getStatusCode());
});

// With header
Toolkit::test(function (): void {
	$response = Response::json(['data' => 'test']);
	$newResponse = $response->withHeader('X-Custom', 'value');

	Assert::false(isset($response->getHeaders()['X-Custom']));
	Assert::same('value', $newResponse->getHeaders()['X-Custom']);
});

// With body
Toolkit::test(function (): void {
	$response = Response::text('original');
	$newResponse = $response->withBody('modified');

	Assert::same('original', $response->getBody());
	Assert::same('modified', $newResponse->getBody());
});

// Immutability
Toolkit::test(function (): void {
	$response1 = Response::json(['a' => 1]);
	$response2 = $response1->withStatus(201);
	$response3 = $response2->withHeader('X-Test', 'value');

	Assert::notSame($response1, $response2);
	Assert::notSame($response2, $response3);
	Assert::same(200, $response1->getStatusCode());
	Assert::same(201, $response2->getStatusCode());
});
