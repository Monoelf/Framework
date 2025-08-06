<?php

declare(strict_types=1);

namespace Monoelf\Framework\http;

use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;

class Message implements MessageInterface
{
    protected array $headerNames = [];

    public function __construct(
        protected StreamInterface $body,
        protected array $headers = [],
        protected string $protocolVersion = '1.1',
    ) {
        foreach ($this->headers as $name => $value) {
            $this->headerNames[strtolower($name)] = $name;
        }
    }

    public function getProtocolVersion(): string
    {
        return $this->protocolVersion;
    }

    public function withProtocolVersion(string $version): MessageInterface
    {
        $instance = clone $this;
        $instance->protocolVersion = $version;

        return $instance;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function hasHeader(string $name): bool
    {
        return array_key_exists(strtolower($name), $this->headerNames);
    }

    public function getHeader(string $name): array
    {
        if ($this->hasHeader($name) === false) {
            return [];
        }

        return $this->headers[$this->headerNames[strtolower($name)]] ?? [];
    }

    public function getHeaderLine(string $name): string
    {
        return implode(', ', $this->getHeader($name));
    }

    public function withHeader(string $name, $value): MessageInterface
    {
        $instance = clone $this;

        if (isset($instance->headers[$name]) === false) {
            $instance->headers[$name] = [];
        }

        $instance->headers[$name][] = $value;
        $instance->headerNames[strtolower($name)] = $name;

        return $instance;
    }

    public function withAddedHeader(string $name, $value): MessageInterface
    {
        $instance = clone $this;
        $oldHeader = $instance->getHeader($name);

        if (isset($instance->headers[$name]) === false) {
            $instance->headers[$name] = [];
        }

        if ($oldHeader === []) {
            $instance->headerNames[strtolower($name)] = $name;
            $instance->headers[$name][] = $value;

            return $instance;
        }

        $instance->headers[$instance->headerNames[strtolower($name)]] = array_merge($oldHeader, [$value]);

        return $instance;
    }

    public function withoutHeader(string $name): MessageInterface
    {
        $instance = clone $this;

        if ($instance->getHeader($name) === []) {
            return $instance;
        }

        $normalizedName = strtolower($name);
        unset($instance->headers[$instance->headerNames[$normalizedName]]);
        unset($instance->headerNames[$normalizedName]);

        return $instance;
    }

    public function getBody(): StreamInterface
    {
        return $this->body;
    }

    public function withBody(StreamInterface $body): MessageInterface
    {
        $instance = clone $this;
        $instance->body = $body;

        return $instance;
    }
}
