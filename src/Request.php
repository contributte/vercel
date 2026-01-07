<?php declare(strict_types = 1);

namespace Contributte\Vercel;

use Nette\Http\Request as NetteHttpRequest;
use Nette\Http\UrlScript;

class Request
{

	private string $method;

	private string $uri;

	/** @var array<string, mixed> */
	private array $params;

	/** @var array<string, mixed> */
	private array $query;

	/** @var array<string, mixed> */
	private array $body;

	/** @var array<string, string> */
	private array $headers;

	private string $rawBody;

	/**
	 * @param array<string, mixed> $params
	 * @param array<string, mixed> $query
	 * @param array<string, mixed> $body
	 * @param array<string, string> $headers
	 */
	public function __construct(
		string $method = 'GET',
		string $uri = '/',
		array $params = [],
		array $query = [],
		array $body = [],
		array $headers = [],
		string $rawBody = ''
	)
	{
		$this->method = strtoupper($method);
		$this->uri = $uri;
		$this->params = $params;
		$this->query = $query;
		$this->body = $body;
		$this->headers = $headers;
		$this->rawBody = $rawBody;
	}

	public static function fromGlobals(): self
	{
		// phpcs:disable SlevomatCodingStandard.Variables.DisallowSuperGlobalVariable.DisallowedSuperGlobalVariable
		$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
		$uri = $_SERVER['REQUEST_URI'] ?? '/';

		$query = [];
		$queryString = parse_url($uri, PHP_URL_QUERY);
		if ($queryString !== null && $queryString !== false) {
			parse_str($queryString, $query);
		}

		$headers = [];
		foreach ($_SERVER as $key => $value) {
			if (str_starts_with($key, 'HTTP_')) {
				$name = str_replace('_', '-', substr($key, 5));
				$headers[$name] = $value;
			}
		}

		$rawBody = file_get_contents('php://input');
		$rawBody = $rawBody !== false ? $rawBody : '';
		$body = self::parseBody($rawBody, $_SERVER['CONTENT_TYPE'] ?? '');
		// phpcs:enable SlevomatCodingStandard.Variables.DisallowSuperGlobalVariable.DisallowedSuperGlobalVariable

		/** @phpstan-var array<string, mixed> $query */
		return new self($method, $uri, [], $query, $body, $headers, $rawBody);
	}

	/**
	 * @param array<string, mixed> $params
	 */
	public function withParams(array $params): self
	{
		$clone = clone $this;
		$clone->params = array_merge($clone->params, $params);

		return $clone;
	}

	public function getMethod(): string
	{
		return $this->method;
	}

	public function getUri(): string
	{
		return $this->uri;
	}

	public function getPath(): string
	{
		$path = parse_url($this->uri, PHP_URL_PATH);

		return $path !== false && $path !== null ? $path : '/';
	}

	/**
	 * @return array<string, mixed>
	 */
	public function getParams(): array
	{
		return $this->params;
	}

	public function getParam(string $name, mixed $default = null): mixed
	{
		return $this->params[$name] ?? $default;
	}

	/**
	 * @return array<string, mixed>
	 */
	public function getQuery(): array
	{
		return $this->query;
	}

	public function getQueryParam(string $name, mixed $default = null): mixed
	{
		return $this->query[$name] ?? $default;
	}

	/**
	 * @return array<string, mixed>
	 */
	public function getBody(): array
	{
		return $this->body;
	}

	public function getBodyParam(string $name, mixed $default = null): mixed
	{
		return $this->body[$name] ?? $default;
	}

	public function getRawBody(): string
	{
		return $this->rawBody;
	}

	/**
	 * @return array<string, string>
	 */
	public function getHeaders(): array
	{
		return $this->headers;
	}

	public function getHeader(string $name): ?string
	{
		$name = strtoupper(str_replace('-', '_', $name));

		foreach ($this->headers as $key => $value) {
			if (strtoupper(str_replace('-', '_', $key)) === $name) {
				return $value;
			}
		}

		return null;
	}

	public function isMethod(string $method): bool
	{
		return $this->method === strtoupper($method);
	}

	public function isGet(): bool
	{
		return $this->isMethod('GET');
	}

	public function isPost(): bool
	{
		return $this->isMethod('POST');
	}

	public function isPut(): bool
	{
		return $this->isMethod('PUT');
	}

	public function isPatch(): bool
	{
		return $this->isMethod('PATCH');
	}

	public function isDelete(): bool
	{
		return $this->isMethod('DELETE');
	}

	public function isAjax(): bool
	{
		return $this->getHeader('X-Requested-With') === 'XMLHttpRequest';
	}

	public function toNetteHttpRequest(): NetteHttpRequest
	{
		$path = $this->getPath();
		$queryString = http_build_query($this->query);
		$fullUrl = 'http://localhost' . $path . ($queryString !== '' ? '?' . $queryString : '');
		$url = new UrlScript($fullUrl);

		return new NetteHttpRequest($url);
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function parseBody(string $rawBody, string $contentType): array
	{
		if (str_contains($contentType, 'application/json')) {
			if ($rawBody === '') {
				return [];
			}

			$data = json_decode($rawBody, true);

			return is_array($data) ? $data : [];
		}

		if (str_contains($contentType, 'application/x-www-form-urlencoded')) {
			// phpcs:ignore SlevomatCodingStandard.Variables.DisallowSuperGlobalVariable.DisallowedSuperGlobalVariable
			return $_POST;
		}

		if (str_contains($contentType, 'multipart/form-data')) {
			// phpcs:ignore SlevomatCodingStandard.Variables.DisallowSuperGlobalVariable.DisallowedSuperGlobalVariable
			return $_POST;
		}

		return [];
	}

}
