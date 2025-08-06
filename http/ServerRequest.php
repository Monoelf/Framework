<?php

declare(strict_types=1);

namespace Monoelf\Framework\http;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

final class ServerRequest extends Message implements ServerRequestInterface
{
    private null|array|object $parsedBody = null;
    private ?string $requestTarget = null;
    private array $queryParams = [];
    private array $cookieParams = [];
    private array $uploadedFiles = [];
    private array $attributes = [];

    /**
     * Инстансиирование из суперглобальных переменных
     * @return self
     */
    public static function fromGlobals(): self
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = new Uri((string)($_SERVER['REQUEST_URI'] ?? '/'));
        $body = new Stream(fopen('php://input', 'rb'));
        $headers = [];

        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_') === true) {
                $name = str_replace('_', '-', substr($key, 5));
                $headerValues = array_map(fn ($x) => trim(explode(';', $x)[0]), explode(',', $value));

                $headers[$name] = $headerValues;
            }
        }

        $request = new self(
            uri: $uri,
            body: $body,
            headers: $headers,
            protocolVersion: $_SERVER['SERVER_PROTOCOL'] ?? '1.1',
            method: $method,
            serverParams: $_SERVER
        );

        return $request
            ->withQueryParams($_GET)
            ->withCookieParams($_COOKIE)
            ->withParsedBody($_POST)
            ->withUploadedFiles($_FILES);
    }

    public function __construct(
        private UriInterface $uri,
        StreamInterface $body,
        array $headers = [],
        string $protocolVersion = '1.1',
        private string $method = 'GET',
        private readonly array $serverParams = [],
    )
    {
        parent::__construct(
            body: $body,
            headers: $headers,
            protocolVersion: $protocolVersion,
        );

        $this->trySetHostFromUri();
    }

    public function getRequestTarget(): string
    {
        if ($this->requestTarget !== null) {
            return $this->requestTarget;
        }

        if ('' === $target = $this->uri->getPath()) {
            $target = '/';
        }

        if ('' !== $this->uri->getQuery()) {
            $target .= '?' . $this->uri->getQuery();
        }

        return $target;
    }

    public function withRequestTarget(string $requestTarget): RequestInterface
    {
        $instance = clone $this;
        $instance->requestTarget = $requestTarget;

        return $instance;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function withMethod(string $method): RequestInterface
    {
        $instance = clone $this;
        $instance->method = $method;

        return $instance;
    }

    public function getUri(): UriInterface
    {
        return $this->uri;
    }

    public function withUri(UriInterface $uri, bool $preserveHost = false): RequestInterface
    {
        $instance = clone $this;
        $instance->uri = $uri;
        $instance->trySetHostFromUri($preserveHost);

        return $instance;
    }

    public function getServerParams(): array
    {
        return $this->serverParams;
    }

    public function getCookieParams(): array
    {
        return $this->cookieParams;
    }

    public function withCookieParams(array $cookies): ServerRequestInterface
    {
        $instance = clone $this;
        $instance->cookieParams = $cookies;

        return $instance;
    }

    public function getQueryParams(): array
    {
        return $this->queryParams;
    }

    public function withQueryParams(array $query): ServerRequestInterface
    {
        $instance = clone $this;
        $instance->queryParams = $query;

        return $instance;
    }

    public function getUploadedFiles(): array
    {
        return $this->uploadedFiles;
    }

    public function withUploadedFiles(array $uploadedFiles): ServerRequestInterface
    {
        $instance = clone $this;
        $instance->uploadedFiles = $uploadedFiles;

        return $instance;
    }

    public function getParsedBody(): object|array|null
    {
        return $this->parsedBody;
    }

    public function withParsedBody($data): ServerRequestInterface
    {
        $instance = clone $this;
        $instance->parsedBody = $data;

        return $instance;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getAttribute(string $name, $default = null): mixed
    {
        if (array_key_exists($name, $this->attributes) === true) {
            return $this->attributes[$name];
        }

        return $default;
    }

    public function withAttribute(string $name, mixed $value): ServerRequestInterface
    {
        $instance = clone $this;
        $instance->attributes[$name] = $value;

        return $instance;
    }

    public function withoutAttribute(string $name): ServerRequestInterface
    {
        $instance = clone $this;
        unset($instance->attributes[$name]);

        return $instance;
    }

    /**
     * @param bool $preserveHost
     * @return void
     */
    private function trySetHostFromUri(bool $preserveHost = false): void
    {
        $host = $this->uri->getHost();

        if ($host === '') {
            return;
        }

        if ($preserveHost === true && $this->getHeader('Host') !== []) {
            return;
        }

        $port = $this->uri->getPort();

        if ($port !== null) {
            $host .= ':' . $port;
        }

        $this->headerNames['host'] = 'Host';
        $this->headers[$this->headerNames['host']] = $host;
    }
}
