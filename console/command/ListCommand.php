<?php

declare(strict_types=1);

namespace Monoelf\Framework\console\command;

Monoelf\Framework\console\ColorsEnum;
Monoelf\Framework\console\ConsoleInputInterface;
Monoelf\Framework\console\ConsoleKernelInterface;
Monoelf\Framework\console\ConsoleOutputInterface;

final class ListCommand implements ConsoleCommandInterface
{
    public function __construct(private readonly ConsoleKernelInterface $kernel) {}

    public static function getSignature(): string
    {
        return 'kernel:list';
    }

    public static function getDescription(): string
    {
        return 'Команда вывода информации о консольном ядре';
    }

    public function execute(ConsoleInputInterface $input, ConsoleOutputInterface $output): void
    {
        $output->info($this->kernel->getAppName());
        $output->info(' ' . $this->kernel->getVersion());
        $output->writeLn(2);
        $output->warning("Фреймворк создан {$this->kernel->getAppName()}.\nЯвляется платформой для изучения базового поведения приложения созданного на PHP.\nФреймворк не является production-ready реализацией и не предназначен для коммерческого использования.");
        $output->writeLn(2);

        $output->success('Доступные опции:');

        foreach ($input->getDefaultOptions() as $defaultOption) {
            $output->writeLn();
            $output->success('  --' . $defaultOption->name);

            if (is_null($defaultOption->description) === false) {
                $output->stdout(' - ' . $defaultOption->description);
            }
        }

        $output->writeLn(2);

        $output->success('Вызов:');
        $output->writeLn();
        $output->stdout('  команда [аргументы] [опции]');
        $output->writeLn(2);

        $output->stdout('Доступные команды:');

        foreach ($this->kernel->getCommands() as $commandsNamespace => $commands) {
            $output->writeLn();
            $output->stdout("  Неймспейс $commandsNamespace:", [ColorsEnum::FG_GREEN->value]);

            foreach ($commands as $commandName => $command) {
                $output->writeLn();
                $output->success('    ' . $commandName);
                $output->stdout(' - ' . $command::getDescription());
            }
        }

        $output->writeLn(2);
    }
}
