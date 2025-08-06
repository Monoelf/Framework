<?php

declare(strict_types=1);

namespace Monoelf\Framework\http;

use Psr\Http\Message\UriInterface;

final class Uri implements UriInterface
{
    private string $scheme = '';
    private string $userInfo = '';
    private string $host = '';
    private ?int $port = null;
    private string $path = '';
    private string $query = '';
    private string $fragment = '';

    public function __construct(string $uri = '')
    {
        if ($uri !== '') {
            $parts = parse_url($uri);
            if ($parts === false) {
                throw new \InvalidArgumentException('Invalid URI');
            }
            $this->scheme = $parts['scheme'] ?? '';
            $this->userInfo = isset($parts['user']) === true ? $parts['user'] : '';
            if (isset($parts['pass']) === true){
                $this->userInfo .= ':' . $parts['pass'];
            }
            $this->host = $parts['host'] ?? '';
            $this->port = $parts['port'] ?? null;
            $this->path = $parts['path'] ?? '';
            $this->query = $parts['query'] ?? '';
            $this->fragment = $parts['fragment'] ?? '';
        }
    }

    public function __toString(): string
    {
        $uri = '';

        if ($this->scheme !== '') {
            $uri .= $this->scheme . ':';
        }

        if ($this->host !== '') {
            $uri .= '//';

            if ($this->userInfo !== '') {
                $uri .= $this->userInfo . '@';
            }

            $uri .= $this->host;

            if ($this->port !== null) {
                $uri .= ':' . $this->port;
            }
        }

        $uri .= $this->path;

        if ($this->query !== '') {
            $uri .= '?' . $this->query;
        }

        if ($this->fragment !== '') {
            $uri .= '#' . $this->fragment;
        }

        return $uri;
    }

    public function getScheme(): string
    {
        return $this->scheme;
    }

    public function getAuthority(): string
    {
        $authority = '';

        if ($this->host === '') {
            return $authority;
        }

        if ($this->userInfo !== '') {
            $authority .= $this->userInfo . '@';
        }

        $authority .= $this->host;

        if ($this->port !== null) {
            $authority .= ':' . $this->port;
        }

        return $authority;
    }

    public function getUserInfo(): string
    {
        return $this->userInfo;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getPort(): ?int
    {
        return $this->port;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getQuery(): string
    {
        return $this->query;
    }

    public function getFragment(): string
    {
        return $this->fragment;
    }

    public function withScheme(string $scheme): UriInterface
    {
        $clone = clone $this;
        $clone->scheme = strtolower($scheme);

        return $clone;
    }

    public function withUserInfo(string $user, ?string $password = null): UriInterface
    {
        $instance = clone $this;
        $instance->userInfo = $user;

        if ($password !== null){
            $instance->userInfo .= ':' . $password;
        }

        return $instance;
    }

    public function withHost(string $host): UriInterface
    {
        $instance = clone $this;
        $instance->host = strtolower($host);

        return $instance;
    }

    public function withPort(?int $port): UriInterface
    {
        $instance = clone $this;
        $instance->port = $port;

        return $instance;
    }

    public function withPath(string $path): UriInterface
    {
        $instance = clone $this;
        $instance->path = $path;

        return $instance;
    }

    public function withQuery(string $query): UriInterface
    {
        $instance = clone $this;
        $instance->query = $query;

        return $instance;
    }

    public function withFragment(string $fragment): UriInterface
    {
        $instance = clone $this;
        $instance->fragment = $fragment;

        return $instance;
    }
}
