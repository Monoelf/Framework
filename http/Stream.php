<?php

declare(strict_types=1);

namespace Monoelf\Framework\http;

use Psr\Http\Message\StreamInterface;

final class Stream implements StreamInterface
{
    /**
     * @var resource|null
     */
    private $stream;
    private bool $readable;
    private bool $writable;
    private bool $seekable;
    private ?int $size;

    /**
     * @param resource|string $stream
     */
    public function __construct($stream)
    {
        if (is_string($stream) === true) {
            $this->stream = fopen('php://temp', 'r+');
            fwrite($this->stream, $stream);
        }

        if (is_resource($stream) === true) {
            $this->stream = $stream;
        }

        $meta = stream_get_meta_data($this->stream);
        $this->seekable = $meta['seekable'];
        $this->readable = strpbrk($meta['mode'], 'r+') !== false;
        $this->writable = strpbrk($meta['mode'], 'waxc+') !== false;
        $this->size = null;
    }

    public function __toString(): string
    {
        if ($this->isReadable() === false || $this->isSeekable() === false) {
            return '';
        }

        $this->rewind();

        return stream_get_contents($this->stream);
    }

    public function close(): void
    {
        if (is_resource($this->stream) === true) {
            fclose($this->stream);
        }

        $this->detach();
    }

    public function detach()
    {
        $old = $this->stream;
        $this->stream = null;
        $this->size = null;
        $this->seekable = false;
        $this->readable = false;
        $this->writable = false;

        return $old;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function tell(): int
    {
        $result = ftell($this->stream);

        if ($result === false) {
            throw new \RuntimeException('Не получилось определить позицию указателя в потоке');
        }

        return $result;
    }

    public function eof(): bool
    {
        return feof($this->stream);
    }

    public function isSeekable(): bool
    {
        return $this->seekable === true;
    }

    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        if ($this->isSeekable() === false) {
            throw new \RuntimeException('Поток не доступен для поиска');
        }

        if (fseek($this->stream, $offset, $whence) === -1) {
            throw new \RuntimeException('Не получилось перейти на указанную позицию в потоке');
        }
    }

    public function rewind(): void
    {
        $this->seek(0);
    }

    public function isWritable(): bool
    {
        return $this->writable === true;
    }

    public function write(string $string): int
    {
        if ($this->isWritable() === false) {
            throw new \RuntimeException('Поток не доступен для записи');
        }

        $written = fwrite($this->stream, $string);

        if ($written === false) {
            throw new \RuntimeException('Невозможно выполнить запись в поток');
        }

        return $written;
    }

    public function isReadable(): bool
    {
        return $this->readable === true;
    }

    public function read(int $length): string
    {
        if ($this->isReadable() === false) {
            throw new \RuntimeException('Поток не доступен для чтения');
        }

        $data = fread($this->stream, $length);

        if ($data === false) {
            throw new \RuntimeException('Невозможно выполнить чтение из потока');
        }

        return $data;
    }

    public function getContents(): string
    {
        if ($this->isReadable() === false) {
            throw new \RuntimeException('Поток не доступен для чтения');
        }

        $contents = stream_get_contents($this->stream);

        if ($contents === false) {
            throw new \RuntimeException('Невозможно выполнить чтение из потока');
        }

        return $contents;
    }

    public function getMetadata(?string $key = null)
    {
        $meta = stream_get_meta_data($this->stream);

        if ($key === null) {
            return $meta;
        }

        return $meta[$key] ?? null;
    }
}
