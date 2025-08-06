<?php

declare(strict_types=1);

namespace Monoelf\Framework\console;

use Monoelf\Framework\console\ConsoleOutputInterface;

final class AnsiConsoleOutput implements ConsoleOutputInterface
{
    /**
     * Поток вывода
     * @var resource
     */
    private $stdOut;

    /**
     * Поток вывода ошибок
     * @var resource
     */
    private $stdErr;

    public function __construct(
        private readonly AnsiDecorator $decorator,
        $stdOut = STDOUT,
        $stdErr = STDERR
    ) {
        $this->stdOut = $stdOut;
        $this->stdErr = $stdErr;
    }

    public function stdout(string $message, array $format = []): void
    {
        fwrite($this->stdOut, $this->decorator->decorate($message, $format));
    }

    public function stdErr(string $message, array $format = []): void
    {
        fwrite($this->stdErr, $this->decorator->decorate($message, $format));
    }

    public function success(string $message): void
    {
        $this->stdout($message, [ColorsEnum::FG_LIGHT_GREEN->value]);
    }

    public function info(string $message): void
    {
        $this->stdout($message, [ColorsEnum::FG_BLUE->value]);
    }

    public function warning(string $message): void
    {
        $this->stdout($message, [ColorsEnum::FG_LIGHT_YELLOW->value]);
    }

    public function writeLn(int $count = 1): void
    {
        for ($i = 0; $i < $count; $i++) {
            $this->stdout("\n");
        }
    }

    public function setStdOut(string $resource): void
    {
        if (is_resource($this->stdOut) === true && $this->stdOut !== STDOUT) {
            fclose($this->stdOut);
        }

        $this->stdOut = fopen($resource, 'ab');
    }

    public function setStdErr(string $resource): void
    {
        if (is_resource($this->stdErr) === true && $this->stdOut !== STDERR) {
            fclose($this->stdErr);
        }

        $this->stdErr = fopen($resource, 'ab');
    }

    public function detach($resource = '/dev/null'): void
    {
        if (pcntl_fork() > 0) {
            exit(0);
        }

        posix_setsid();

        $stream = fopen($resource, 'c');

        $this->stdOut = $stream;
        $this->stdErr = $stream;
    }
}
