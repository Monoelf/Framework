<?php

declare(strict_types=1);

namespace Monoelf\Framework\console\plugin;

Monoelf\Framework\console\command\OptionDTO;
Monoelf\Framework\console\ConsoleEvent;
Monoelf\Framework\console\ConsoleInputInterface;
Monoelf\Framework\console\ConsoleOutputInterface;
Monoelf\Framework\console\plugin\ConsoleInputPluginInterface;
Monoelf\Framework\event_dispatcher\EventDispatcherInterface;
Monoelf\Framework\event_dispatcher\Message;
Monoelf\Framework\event_dispatcher\ObserverInterface;

final class CommandSaveFilePlugin implements ObserverInterface, ConsoleInputPluginInterface
{
    private OptionDTO $option;

    public function __construct(
        private readonly ConsoleOutputInterface $output,
    ) {
        $this->option = new OptionDTO('save-file', true, 'Сохранение вывода команды в файл');
    }

    public function init(ConsoleInputInterface $input, EventDispatcherInterface $dispatcher): void
    {
        $input->addDefaultOption($this->option);

        $dispatcher->attach(ConsoleEvent::CONSOLE_INPUT_AFTER_VALIDATE, self::class);
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

        $file = $input->getOptionValue($this->option->name);

        $this->output->setStdOut($file);
    }
}
