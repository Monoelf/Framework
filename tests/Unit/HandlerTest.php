<?php

declare(strict_types=1);

namespace Monoelf\Framework\tests\Unit;

use Monoelf\Framework\modules\calculation_mode_generator\dto\CalculatorDTO;
use Monoelf\Framework\modules\calculation_mode_generator\dto\OperationDTO;
use Monoelf\Framework\modules\calculation_mode_generator\exceptions\NextCombinationNotExists;
use Monoelf\Framework\modules\calculation_mode_generator\exceptions\StrategyNotExecutedException;
use Monoelf\Framework\modules\calculation_mode_generator\Handler;
use Monoelf\Framework\modules\calculation_mode_generator\strategies\OperationStrategy;
use Monoelf\Framework\modules\calculation_mode_generator\strategies\storage\StrategyStorageInterface;
use Codeception\Test\Unit;
use PHPUnit\Framework\MockObject\MockObject;

final class HandlerTest extends Unit
{
    public function testHandleSuccessCombination(): void
    {
        $strategy = $this->createSuccessStrategyMock('сложение', 1000);
        $strategy->expects($this->once())->method('execute');
        $storage = $this->createStorageMock([[$strategy]]);
        $storage->expects($this->once()) ->method('createCombination');
        $handler = new Handler($storage);
        $dto = new CalculatorDTO(300, 700);

        $handler->handle($dto);

        $this->assertSame(
            [['operationName' => 'сложение', 'result' => 1000.0]],
            $dto->succeedCombination);
    }

    public function testHandleNoBadCombination(): void
    {
        $strategy = $this->createSuccessStrategyMock('сложение', 1000);
        $strategy->expects($this->once())->method('execute');
        $storage = $this->createStorageMock([[$strategy]]);
        $storage->expects($this->once()) ->method('createCombination');
        $handler = new Handler($storage);
        $dto = new CalculatorDTO(300, 700);

        $handler->handle($dto);

        $this->assertEmpty($dto->badCombinations);
    }

    public function testHandleBadCombination(): void
    {
        $strategy = $this->createFailStrategyMock('невыполнение');
        $storage = $this->createStorageMock([[$strategy]]);
        $storage->expects($this->exactly(2)) ->method('createCombination');
        $handler = new Handler($storage);
        $dto = new CalculatorDTO(300, 700);

        $handler->handle($dto);

        $this->assertEmpty($dto->succeedCombination);
    }


    public function testHandleNoSuccessCombination(): void
    {
        $strategy = $this->createFailStrategyMock('невыполнение');
        $storage = $this->createStorageMock([[$strategy]]);
        $storage->expects($this->exactly(2)) ->method('createCombination');
        $handler = new Handler($storage);
        $dto = new CalculatorDTO(300, 700);

        $handler->handle($dto);

        $this->assertSame([['невыполнение']], $dto->badCombinations);
    }

    public function testHandleNoBadCombinationsOnEmpty(): void
    {
        $storage = $this->createStorageMock([]);
        $storage->expects($this->once())->method('createCombination');
        $handler = new Handler($storage);
        $dto = new CalculatorDTO(300, 700);

        $handler->handle($dto);

        $this->assertEmpty($dto->badCombinations);
    }

    public function testHandleNoSuccessCombinationsOnEmpty(): void
    {
        $storage = $this->createStorageMock([]);
        $storage->expects($this->once())->method('createCombination');
        $handler = new Handler($storage);
        $dto = new CalculatorDTO(300, 700);

        $handler->handle($dto);

        $this->assertEmpty($dto->succeedCombination);
    }

    public function testHandleSuccessWithTwoStrategies(): void
    {
        $strategyFirst = $this->createSuccessStrategyMock('сложение', 1000);
        $strategySecond = $this->createSuccessStrategyMock('вычитание', -400);
        $storage = $this->createStorageMock([
            [$strategyFirst, $strategySecond],
            [$strategySecond, $strategyFirst]
        ]);
        $storage->expects($this->once())->method('createCombination');
        $handler = new Handler($storage);
        $dto = new CalculatorDTO(300, 700);

        $handler->handle($dto);

        $this->assertSame([
            ['operationName' => 'сложение', 'result' => 1000.0],
            ['operationName' => 'вычитание', 'result' => -400.0],
        ], $dto->succeedCombination);
    }

    private function createSuccessStrategyMock(string $name, float $result): OperationStrategy & MockObject
    {
        $strategyMock = $this->createMock(OperationStrategy::class);
        $strategyMock->method('getName')->willReturn($name);
        $strategyMock->method('execute')->willReturnCallback(fn (OperationDTO $dto) => $dto->currentResult = $result);

        return $strategyMock;
    }

    private function createFailStrategyMock(string $name): OperationStrategy & MockObject
    {
        $strategyMock = $this->createMock(OperationStrategy::class);
        $strategyMock->method('getName')->willReturn($name);
        $strategyMock->method('execute')->willThrowException(new StrategyNotExecutedException());

        return $strategyMock;
    }

    private function createStorageMock(array $returns): StrategyStorageInterface & MockObject
    {
        $storageMock = $this->createMock(StrategyStorageInterface::class);
        $storageMock->method('createCombination')
            ->willReturnOnConsecutiveCalls(
                ...array_merge($returns, [$this->throwException(new NextCombinationNotExists())])
            );

        return $storageMock;
    }
}
