<?php

declare(strict_types=1);

namespace Monoelf\Framework\console\plugin;

use Monoelf\Framework\console\command\ArgumentDTO;
use Monoelf\Framework\console\command\OptionDTO;
use Monoelf\Framework\console\ConsoleEvent;
use Monoelf\Framework\console\ConsoleInputInterface;
use Monoelf\Framework\console\ConsoleOutputInterface;
use Monoelf\Framework\console\plugin\ConsoleInputPluginInterface;
use Monoelf\Framework\event_dispatcher\EventDispatcherInterface;
use Monoelf\Framework\event_dispatcher\Message;
use Monoelf\Framework\event_dispatcher\ObserverInterface;

final class CommandInteractiveOptionPlugin implements ConsoleInputPluginInterface, ObserverInterface
{
    private OptionDTO $option;

    public function __construct(
        private readonly ConsoleOutputInterface $output,
    ) {
        $this->option = new OptionDTO('interactive', false, 'Интерактивный ввод аргументов');
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

        $command = $input->getDefinition();

        foreach ($command->getArguments() as $argumentName) {
            $argument = $command->getArgumentDefinition($argumentName);

            $this->printArgumentInfo($argument);

            $value = $argument->default;
            $userInput = trim(fgets(STDIN));

            if (strlen($userInput) !== 0) {
                $value = $userInput;
            }

            if (is_null($value) === false) {
                $input->setArgumentValue($argumentName, $value);
            }
        }
    }

    /**
     * Печать строки запроса ввода агрумента
     *
     * @param ArgumentDTO $argument
     * @return void
     */
    private function printArgumentInfo(ArgumentDTO $argument): void
    {
        $this->output->success("Введите аргумент {$argument->name}");

        if ($argument->description !== '') {
            $this->output->success(" ({$argument->description})");
        }

        if (is_null($argument->default) === false) {
            $this->output->success(" [{$argument->default}]");
        }

        $this->output->success(':');
        $this->output->writeLn();
    }
}
