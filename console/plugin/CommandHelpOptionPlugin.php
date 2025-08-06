<?php

declare(strict_types=1);

namespace Monoelf\Framework\console\plugin;

use Monoelf\Framework\console\command\CommandDefinition;
use Monoelf\Framework\console\command\OptionDTO;
use Monoelf\Framework\console\ConsoleEvent;
use Monoelf\Framework\console\ConsoleInputInterface;
use Monoelf\Framework\console\ConsoleKernelInterface;
use Monoelf\Framework\console\ConsoleOutputInterface;
use Monoelf\Framework\console\plugin\ConsoleInputPluginInterface;
use Monoelf\Framework\event_dispatcher\EventDispatcherInterface;
use Monoelf\Framework\event_dispatcher\Message;
use Monoelf\Framework\event_dispatcher\ObserverInterface;

final class CommandHelpOptionPlugin implements ConsoleInputPluginInterface, ObserverInterface
{
    private OptionDTO $option;

    public function __construct(
        private readonly ConsoleOutputInterface $output,
        private readonly ConsoleKernelInterface $kernel,
    ) {
        $this->option = new OptionDTO('help', false, 'Вывод информации о команде');
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

        $this->printCommandInfo($command);

        $this->printArgumentsInfo($command);

        $this->printOptionsInfo($command);

        $this->kernel->terminate(0);
    }

    /**
     * Вывод базовой информации о команде:
     * способ вызова и назначение
     *
     * @param CommandDefinition $command
     * @return void
     */
    private function printCommandInfo(CommandDefinition $command): void
    {
        $this->output->success('Вызов:');
        $this->output->writeLn();
        $this->output->stdout("  {$command->getCommandNamespace()}:{$command->getCommandName()}");

        foreach ($command->getArguments() as $argumentName) {
            $this->output->stdout(sprintf(" [%s]", $argumentName));
        }

        $this->output->stdout(' [опции]');
        $this->output->writeLn(2);

        $this->output->info('Назначение:');
        $this->output->writeLn();
        $this->output->stdout("  {$command->getCommandDescription()}");
        $this->output->writeLn(2);
    }

    /**
     * Вывод информации об аргументах команды
     *
     * @param CommandDefinition $command
     * @return void
     */
    private function printArgumentsInfo(CommandDefinition $command): void
    {
        $this->output->info('Аргументы:');
        $this->output->writeLn();

        foreach ($command->getArguments() as $argumentName) {
            $argument = $command->getArgumentDefinition($argumentName);

            $this->output->success("  {$argument->name} ");

            if (is_null($argument->description) === false) {
                $this->output->stdout("{$argument->description}, ");
            }

            $this->output->stdout(($argument->required === false ? 'не ' : '') . 'обязательный параметр');

            if (is_null($argument->default) === false) {
                $this->output->stdout(", значение по умолчанию: {$argument->default} ");
            }

            $this->output->writeLn();
        }

        $this->output->writeLn();
    }

    /**
     * Вывод информации об опциях команды
     *
     * @param CommandDefinition $command
     * @return void
     */
    private function printOptionsInfo(CommandDefinition $command): void
    {
        $this->output->info('Опции:');
        $this->output->writeLn();

        foreach ($command->getOptions() as $optionName) {
            $option = $command->getOptionDefinition($optionName);

            $this->output->success("  {$option->name} ");

            if (is_null($option->description) === false){
                $this->output->stdout("$option->description. ");
            }

            $this->output->stdout("Является опцией");
            $this->output->stdout($option->hasValue === false ? '-флагом' : ' с параметром');

            $this->output->writeLn();
        }
    }
}
