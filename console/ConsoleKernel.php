<?php

declare(strict_types=1);

namespace Monoelf\Framework\console;

use Monoelf\Framework\common\ErrorHandlerInterface;
use Monoelf\Framework\common\ModuleInterface;
use Monoelf\Framework\console\command\CommandDefinition;
use Monoelf\Framework\console\command\ConsoleCommandInterface;
use Monoelf\Framework\console\command\ListCommand;
use Monoelf\Framework\container\ContainerInterface;
use Monoelf\Framework\logger\LoggerInterface;
final class ConsoleKernel implements ConsoleKernelInterface
{
    private string $defaultCommand = 'kernel:list';
    private array $commandMap = [];

    public function __construct(
        private readonly ContainerInterface $container,
        private readonly ConsoleInputInterface $input,
        private readonly ConsoleOutputInterface $output,
        private readonly LoggerInterface $logger,
        private readonly ErrorHandlerInterface $errorHandler,
        private readonly ?string $appName = null,
        private readonly ?string $version = null,
        private readonly array $inputPlugins = [],
        array $modules = [],
    ) {
        $this->initDefaultCommands();
        $this->initModules($modules);
    }

    public function getAppName(): ?string
    {
        return $this->appName;
    }

    public function getVersion(): ?string
    {
        return $this->version;
    }

    public function getCommands(): array
    {
        return $this->commandMap;
    }

    public function registerCommandNamespaces(array $commandNamespaces): void
    {
        foreach ($commandNamespaces as $commandNamespace) {
            $this->registerCommandNamespace($commandNamespace);
        }
    }

    public function handle(): int
    {
        $commandFullName = explode(':', $this->input->getFirstArgument() ?? $this->defaultCommand);

        $commandName = $this->commandMap[$commandFullName[0]][$commandFullName[1]]
            ?? throw new \InvalidArgumentException(sprintf("Команда %s не найдена", implode($commandFullName)));

        try {
            $this->input->addPlugins($this->inputPlugins);

            $command = $this->container->get($commandName);
            $this->input->bindDefinitions($command);
            $command->execute($this->input, $this->output);
        } catch (\Throwable $e) {
            $this->output->stdErr($this->errorHandler->handle($e));

            $this->logger->error($e);

            return ExitCodesEnum::ERROR->value;
        }

        return ExitCodesEnum::SUCCESS->value;
    }

    public function terminate(int $status): never
    {
        exit($status);
    }

    /**
     * Регистрация класса команды
     *
     * @param string $className
     * @return void
     */
    private function registerCommand(string $className): void
    {
        $commandDefinition = new CommandDefinition($className::getSignature(), $className::getDescription());

        $commandName = $commandDefinition->getCommandName();
        $commandNamespace = $commandDefinition->getCommandNamespace();

        $this->commandMap[$commandNamespace][$commandName] = $className;
    }

    /**
     * Регистрация неймспейса команды
     *
     * @param string $commandNameSpace
     * @return void
     */
    private function registerCommandNamespace(string $commandNameSpace): void
    {
        $possibleCommandFiles = glob($commandNameSpace . '/*.php');

        foreach ($possibleCommandFiles as $possibleCommandFile) {
            $commandPath = dirname($possibleCommandFile) . '/' . basename($possibleCommandFile, '.php');
            $commandPath = str_replace(PROJECT_ROOT, 'app\\', $commandPath);
            $commandPath = str_replace('/', '\\', $commandPath);

            if (class_exists($commandPath) === false) {
                continue;
            }

            if (is_subclass_of($commandPath, ConsoleCommandInterface::class) === false) {
                continue;
            }

            $this->registerCommand($commandPath);
        }
    }

    /**
     * Регистрация команд по-умолчанию
     *
     * @return void
     */
    private function initDefaultCommands(): void
    {
        $defaultCommands = [
            ListCommand::class,
        ];

        foreach ($defaultCommands as $className) {
            $this->registerCommand($className);
        }
    }

    /**
     * Инициализация модулей
     *
     * @param array $modules
     * @return void
     */
    private function initModules(array $modules): void
    {
        foreach ($modules as $module) {
            if (is_subclass_of($module, ModuleInterface::class) === false) {
                throw new \InvalidArgumentException("Модуль {$module} не реализует интерфейс " . ModuleInterface::class);
            }

            $this->container->call($module, 'init');
        }
    }
}
