<?php declare(strict_types = 1);

namespace Tests\Cases;

use Contributte\Tester\Toolkit;
use Contributte\Vercel\Request;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

// Default values
Toolkit::test(function (): void {
	$request = new Request();

	Assert::same('GET', $request->getMethod());
	Assert::same('/', $request->getUri());
	Assert::same('/', $request->getPath());
	Assert::same([], $request->getParams());
	Assert::same([], $request->getQuery());
	Assert::same([], $request->getBody());
	Assert::same([], $request->getHeaders());
	Assert::same('', $request->getRawBody());
});

// Constructor with values
Toolkit::test(function (): void {
	$request = new Request(
		method: 'POST',
		uri: '/api/users?page=1',
		params: ['id' => '123'],
		query: ['page' => '1'],
		body: ['name' => 'John'],
		headers: ['Content-Type' => 'application/json'],
		rawBody: '{"name":"John"}'
	);

	Assert::same('POST', $request->getMethod());
	Assert::same('/api/users?page=1', $request->getUri());
	Assert::same('/api/users', $request->getPath());
	Assert::same(['id' => '123'], $request->getParams());
	Assert::same('123', $request->getParam('id'));
	Assert::null($request->getParam('nonexistent'));
	Assert::same('default', $request->getParam('nonexistent', 'default'));
	Assert::same(['page' => '1'], $request->getQuery());
	Assert::same('1', $request->getQueryParam('page'));
	Assert::same(['name' => 'John'], $request->getBody());
	Assert::same('John', $request->getBodyParam('name'));
	Assert::same(['Content-Type' => 'application/json'], $request->getHeaders());
	Assert::same('application/json', $request->getHeader('Content-Type'));
	Assert::same('{"name":"John"}', $request->getRawBody());
});

// Method uppercase
Toolkit::test(function (): void {
	$request = new Request('post', '/');
	Assert::same('POST', $request->getMethod());
});

// Is method helpers
Toolkit::test(function (): void {
	Assert::true((new Request('GET', '/'))->isGet());
	Assert::true((new Request('POST', '/'))->isPost());
	Assert::true((new Request('PUT', '/'))->isPut());
	Assert::true((new Request('PATCH', '/'))->isPatch());
	Assert::true((new Request('DELETE', '/'))->isDelete());

	Assert::false((new Request('POST', '/'))->isGet());
	Assert::false((new Request('GET', '/'))->isPost());
});

// Is method
Toolkit::test(function (): void {
	$request = new Request('GET', '/');

	Assert::true($request->isMethod('GET'));
	Assert::true($request->isMethod('get'));
	Assert::false($request->isMethod('POST'));
});

// With params
Toolkit::test(function (): void {
	$request = new Request('GET', '/', ['id' => '1']);

	$newRequest = $request->withParams(['name' => 'John']);

	// Original unchanged
	Assert::same(['id' => '1'], $request->getParams());

	// New request has merged params
	Assert::same(['id' => '1', 'name' => 'John'], $newRequest->getParams());
});

// Header case insensitive
Toolkit::test(function (): void {
	$request = new Request(
		headers: ['Content-Type' => 'application/json', 'X-Custom-Header' => 'value']
	);

	Assert::same('application/json', $request->getHeader('Content-Type'));
	Assert::same('application/json', $request->getHeader('content-type'));
	Assert::same('application/json', $request->getHeader('CONTENT-TYPE'));
	Assert::same('value', $request->getHeader('X-Custom-Header'));
	Assert::same('value', $request->getHeader('x-custom-header'));
	Assert::null($request->getHeader('NonExistent'));
});

// Is ajax
Toolkit::test(function (): void {
	$request1 = new Request(headers: ['X-Requested-With' => 'XMLHttpRequest']);
	Assert::true($request1->isAjax());

	$request2 = new Request();
	Assert::false($request2->isAjax());
});

// To Nette HTTP request
Toolkit::test(function (): void {
	$request = new Request('GET', '/api/users', query: ['page' => '1']);
	$netteRequest = $request->toNetteHttpRequest();

	Assert::type('Nette\Http\Request', $netteRequest);
	Assert::same('/api/users', $netteRequest->getUrl()->getPath());
});
