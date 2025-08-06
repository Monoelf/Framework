<?php

declare(strict_types=1);

namespace Monoelf\Framework\tests\Unit;

Monoelf\Framework\modules\calculation_mode_generator\dto\OperationDTO;
Monoelf\Framework\modules\calculation_mode_generator\exceptions\StrategyNotExecutedException;
Monoelf\Framework\modules\calculation_mode_generator\strategies\SubtractionStrategy;
use Codeception\Test\Unit;

final class SubtractionStrategyTest extends Unit
{
    public function testSuccessfulExecute(): void
    {
        $strategy = new SubtractionStrategy();
        $dto = new OperationDTO(700, 300, 0);

        $strategy->execute($dto);

        $this->assertEquals(400, $dto->currentResult);
    }

    public function testThrowExceptionOnWrongResult(): void
    {
        $strategy = new SubtractionStrategy();
        $dto = new OperationDTO(700, 300, 1000);

        $this->expectException(StrategyNotExecutedException::class);
        $strategy->execute($dto);
    }
}
