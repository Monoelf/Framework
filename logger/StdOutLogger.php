<?php

declare(strict_types=1);

namespace Monoelf\Framework\logger;

use Monoelf\Framework\config_storage\ConfigurationStorage;
use Monoelf\Framework\event_dispatcher\Message;
use Monoelf\Framework\event_dispatcher\ObserverInterface;
use DateTime;
use DateTimeZone;

final class StdOutLogger extends AbstractLogger implements ObserverInterface
{
    private array $context = [];
    private mixed $extras = null;
    private ?string $category = null;
    private array $listeners;

    /**
     * Поток вывода
     * @var resource
     */
    private $stdOut;

    public function __construct(
        private readonly DebugTagStorageInterface $debugTagStorage,
        private readonly ConfigurationStorage $configurationStorage,
        private readonly string $actionType,
        private readonly string $projectIndex,
    ) {
        $this->stdOut = fopen('php://stdout', 'w');

        $this->listeners = [
            LogContextEvent::ATTACH_CONTEXT => function (Message $event) {
                $this->context[$event->message] = $event->message;
            },
            LogContextEvent::DETACH_CONTEXT => function (Message $event) {
                if (isset($this->context[$event->message]) === false) {
                    return;
                }

                unset($this->context[$event->message]);
            },
            LogContextEvent::FLUSH_CONTEXT => function () {
                $this->context = [];
            },
            LogContextEvent::ATTACH_EXTRAS => function (Message $event) {
                $this->extras = $event->message;
            },
            LogContextEvent::FLUSH_EXTRAS => function () {
                $this->extras = null;
            },
            LogContextEvent::ATTACH_CATEGORY => function (Message $event) {
                $this->category = $event->message;
            },
            LogContextEvent::FLUSH_CATEGORY => function () {
                $this->category = null;
            }
        ];
    }

    protected function formatMessage(string $level, mixed $message): string
    {
        $messageLog = null;
        $exceptionLog = null;

        if ($message instanceof \Throwable === true) {
            $messageLog = $message->getMessage();
            $exceptionLog = $this->throwableToOptions($message);
        }

        $actionLog = match ($this->actionType) {
            'web' => parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? 'unknown-web',
            'cli' => $_SERVER['argv'][1] ?? 'unknown-cli',
            default => 'unknown',
        };

        $ip = $this->actionType === 'cli'
            ? null
            : ($_SERVER['REMOTE_ADDR'] ?? null);
        $realIp = $this->actionType === 'cli'
            ? null
            : ($_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? null);

        $data = [
            'index' => $this->projectIndex,
            'category' => $this->category,
            'context' => implode(';', $this->context),
            'level' => LogLevel::getIndex($level),
            'level_name' => strtolower($level),
            'user_id' => null,
            'user_login' => null,
            'action' => $actionLog,
            'action_type' => $this->actionType,
            'datetime' => (new DateTime('now', new DateTimeZone('UTC')))->format("Y-m-d\TH:i:s.vP"),
            'timestamp' => (new DateTime('now'))->format("Y-m-d\TH:i:s.vP"),
            'ip' => $ip,
            'real_ip' => $realIp,
            'x-debug-tag' => $this->debugTagStorage->getTag(),
            'message' => $messageLog ?? $message,
            'exception' => $exceptionLog,
            'extras' => $this->extras
        ];

        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function throwableToOptions(\Throwable $throwable): array
    {
        return [
            'file' => $throwable->getFile(),
            'line' => $throwable->getLine(),
            'code' => $throwable->getCode(),
            'trace' => explode(PHP_EOL, $throwable->getTraceAsString()),
        ];
    }

    protected function writeLog(string $log): void
    {
        fwrite($this->stdOut, $log . PHP_EOL);
    }

    public function handle(string $eventName, Message $message): void
    {
        if (isset($this->listeners[$eventName]) === false) {
            return;
        }

        $this->listeners[$eventName]($message);
    }
}
