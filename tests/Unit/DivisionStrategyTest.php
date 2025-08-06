<?php

declare(strict_types=1);

namespace Monoelf\Framework\tests\Unit;

use Monoelf\Framework\modules\calculation_mode_generator\dto\OperationDTO;
use Monoelf\Framework\modules\calculation_mode_generator\exceptions\StrategyNotExecutedException;
use Monoelf\Framework\modules\calculation_mode_generator\strategies\DivisionStrategy;
use Codeception\Test\Unit;

final class DivisionStrategyTest extends Unit
{
    public function testSuccessfulExecute(): void
    {
        $strategy = new DivisionStrategy();
        $dto = new OperationDTO(600, 300, 2000);

        $strategy->execute($dto);

        $this->assertEquals(2, $dto->currentResult);
    }

    public function testThrowExceptionOnWrongResult(): void
    {
        $strategy = new DivisionStrategy();
        $dto = new OperationDTO(600, 300, 1000);

        $this->expectException(StrategyNotExecutedException::class);

        $strategy->execute($dto);
    }
}
