<?php

declare(strict_types=1);

namespace Monoelf\Framework\tests\Unit;

use Monoelf\Framework\modules\calculation_mode_generator\dto\OperationDTO;
use Monoelf\Framework\modules\calculation_mode_generator\exceptions\StrategyNotExecutedException;
use Monoelf\Framework\modules\calculation_mode_generator\strategies\MultiplicationStrategy;
use PHPUnit\Framework\TestCase;

final class MultiplicationStrategyTest extends TestCase
{
    public function testSuccessfulExecute(): void
    {
        $strategy = new MultiplicationStrategy();
        $dto = new OperationDTO(3, 7, 100);

        $strategy->execute($dto);

        $this->assertEquals(21, $dto->currentResult);
    }

    public function testThrowExceptionOnWrongResult(): void
    {
        $strategy = new MultiplicationStrategy();
        $dto = new OperationDTO(3, 7, 0);

        $this->expectException(StrategyNotExecutedException::class);
        $strategy->execute($dto);
    }
}
