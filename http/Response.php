<?php

declare(strict_types=1);

namespace Monoelf\Framework\http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

final class Response extends Message implements ServerResponseInterface
{
    public function __construct(
        StreamInterface $body,
        array $headers = [],
        string $protocolVersion = '1.1',
        private int $statusCode = 200,
        private string $reasonPhrase = ''
    ) {
        parent::__construct(
            body: $body,
            headers: $headers,
            protocolVersion: $protocolVersion
        );

        $this->reasonPhrase = $reasonPhrase === '' ? StatusCodeEnum::tryFrom($this->statusCode)?->reasonPhrase() : $reasonPhrase;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function withStatus(int $code, string $reasonPhrase = ''): ResponseInterface
    {
        $instance = clone $this;
        $instance->statusCode = $code;
        $instance->reasonPhrase = $reasonPhrase === '' ? StatusCodeEnum::tryFrom($code)?->reasonPhrase() : $reasonPhrase;

        return $instance;
    }

    public function getReasonPhrase(): string
    {
        return $this->reasonPhrase;
    }

    public function send(): void
    {
        header(sprintf(
            'HTTP/%s %d %s',
            $this->getProtocolVersion(),
            $this->getStatusCode(),
            $this->getReasonPhrase()
        ));

        foreach ($this->headers as $name => $values) {
            if (is_array($values) === false) {
                header(sprintf('%s: %s', $name, $values), false);

                continue;
            }

            foreach ($values as $value) {
                header(sprintf('%s: %s', $name, $value), false);
            }
        }

        if ($this->body->isSeekable() === true) {
            $this->body->rewind();
        }

        echo $this->body->getContents();
    }
}
