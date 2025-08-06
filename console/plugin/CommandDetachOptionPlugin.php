<?php

declare(strict_types=1);

namespace Monoelf\Framework\console\plugin;

use Monoelf\Framework\console\command\OptionDTO;
use Monoelf\Framework\console\ConsoleEvent;
use Monoelf\Framework\console\ConsoleInputInterface;
use Monoelf\Framework\console\ConsoleOutputInterface;
use Monoelf\Framework\console\plugin\ConsoleInputPluginInterface;
use Monoelf\Framework\event_dispatcher\EventDispatcherInterface;
use Monoelf\Framework\event_dispatcher\Message;
use Monoelf\Framework\event_dispatcher\ObserverInterface;

final class CommandDetachOptionPlugin implements ConsoleInputPluginInterface, ObserverInterface
{
    private OptionDTO $option;

    public function __construct(
        private readonly ConsoleOutputInterface $output,
    ) {
        $this->option = new OptionDTO('detach', false, 'Перевод процесса в фон');
    }

    public function init(ConsoleInputInterface $input, EventDispatcherInterface $dispatcher): void
    {
        $input->addDefaultOption($this->option);

        $dispatcher->attach(ConsoleEvent::CONSOLE_INPUT_AFTER_PARSE, self::class);
    }

    public function handle(string $eventName, Message $message): void
    {
        /**
         * @var ConsoleInputInterface $input
         */
        $input = $message->message;

        if ($input->hasOption($this->option->name) === false) {
            return;
        }

        $this->output->detach();
    }
}
