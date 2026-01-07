<?php declare(strict_types = 1);

namespace Contributte\Vercel;

class Response
{

	private int $statusCode = 200;

	/** @var array<string, string> */
	private array $headers = [];

	private string $body = '';

	public static function json(mixed $data, int $status = 200): self
	{
		$response = new self();
		$response->statusCode = $status;
		$response->headers['Content-Type'] = 'application/json; charset=utf-8';
		$encoded = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		$response->body = $encoded !== false ? $encoded : '';

		return $response;
	}

	public static function html(string $html, int $status = 200): self
	{
		$response = new self();
		$response->statusCode = $status;
		$response->headers['Content-Type'] = 'text/html; charset=utf-8';
		$response->body = $html;

		return $response;
	}

	public static function text(string $text, int $status = 200): self
	{
		$response = new self();
		$response->statusCode = $status;
		$response->headers['Content-Type'] = 'text/plain; charset=utf-8';
		$response->body = $text;

		return $response;
	}

	public static function redirect(string $url, int $status = 302): self
	{
		$response = new self();
		$response->statusCode = $status;
		$response->headers['Location'] = $url;

		return $response;
	}

	public static function notFound(string $message = 'Not Found'): self
	{
		return self::json(['error' => $message], 404);
	}

	public static function error(string $message = 'Internal Server Error', int $status = 500): self
	{
		return self::json(['error' => $message], $status);
	}

	public static function badRequest(string $message = 'Bad Request'): self
	{
		return self::json(['error' => $message], 400);
	}

	public static function unauthorized(string $message = 'Unauthorized'): self
	{
		return self::json(['error' => $message], 401);
	}

	public static function forbidden(string $message = 'Forbidden'): self
	{
		return self::json(['error' => $message], 403);
	}

	public static function noContent(): self
	{
		$response = new self();
		$response->statusCode = 204;

		return $response;
	}

	public function withStatus(int $status): self
	{
		$clone = clone $this;
		$clone->statusCode = $status;

		return $clone;
	}

	public function withHeader(string $name, string $value): self
	{
		$clone = clone $this;
		$clone->headers[$name] = $value;

		return $clone;
	}

	public function withBody(string $body): self
	{
		$clone = clone $this;
		$clone->body = $body;

		return $clone;
	}

	public function send(): void
	{
		http_response_code($this->statusCode);

		foreach ($this->headers as $name => $value) {
			header(sprintf('%s: %s', $name, $value));
		}

		echo $this->body;
	}

	public function getStatusCode(): int
	{
		return $this->statusCode;
	}

	/**
	 * @return array<string, string>
	 */
	public function getHeaders(): array
	{
		return $this->headers;
	}

	public function getBody(): string
	{
		return $this->body;
	}

}
