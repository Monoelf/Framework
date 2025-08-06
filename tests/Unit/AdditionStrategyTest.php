<?php

declare(strict_types=1);

namespace Monoelf\Framework\tests\Unit;

Monoelf\Framework\modules\calculation_mode_generator\dto\OperationDTO;
Monoelf\Framework\modules\calculation_mode_generator\exceptions\StrategyNotExecutedException;
Monoelf\Framework\modules\calculation_mode_generator\strategies\AdditionStrategy;
use Codeception\Test\Unit;

final class AdditionStrategyTest extends Unit
{
    public function testSuccessfulExecute(): void
    {
        $strategy = new AdditionStrategy();
        $dto = new OperationDTO(300, 700, 0);

        $strategy->execute($dto);

        $this->assertEquals(1000, $dto->currentResult);
    }

    public function testThrowExceptionOnWrongResult(): void
    {
        $strategy = new AdditionStrategy();
        $dto = new OperationDTO(300, 700, -100);

        $this->expectException(StrategyNotExecutedException::class);
        $strategy->execute($dto);
    }
}
