<?php

declare(strict_types=1);

namespace Monoelf\Framework\tests\Unit;

Monoelf\Framework\modules\calculation_mode_generator\exceptions\NextCombinationNotExists;
Monoelf\Framework\modules\calculation_mode_generator\strategies\storage\CombinationGenerator;
use Codeception\Test\Unit;

final class CombinationGeneratorTest extends Unit
{
    public function testCreateOneCombinationFromEmpty(): void
    {
        $generator = new CombinationGenerator();

        $result = $generator->next();

        $this->assertSame([], $result);
    }

    public function testThrowsExceptionAfterLastCombinationFromEmpty(): void
    {
        $generator = new CombinationGenerator();

        $this->expectException(NextCombinationNotExists::class);

        for ($i = 0; $i < 2; $i++) {
            $generator->next();
        }
    }
    public function testCreatedOneCombinationFromSingleElement(): void
    {
        $generator = new CombinationGenerator(['only element']);

        $result = $generator->next();

        $this->assertSame(['only element'], $result);
    }

    public function testThrowsExceptionAfterLastCombinationFromSingleElement(): void
    {
        $generator = new CombinationGenerator(['only element']);

        $this->expectException(NextCombinationNotExists::class);

        for ($i = 0; $i < 2; $i++) {
            $generator->next();
        }
    }

    public function testCreateAllCombinationsFromMultipleElements(): void
    {
        $generator = new CombinationGenerator(['x', 'y', 'z']);
        $result = [];

        for ($i = 0; $i < 6; $i++) {
            $result[] = $generator->next();
        }

        $this->assertCount(6, $result);
        $this->assertCount(6, array_unique($result, SORT_REGULAR));
    }

    public function testThrowsExceptionAfterLastCombinationFromMultipleElements(): void
    {
        $generator = new CombinationGenerator(['x', 'y', 'z']);

        $this->expectException(NextCombinationNotExists::class);

        for ($i = 0; $i < 7; $i++) {
            $generator->next();
        }
    }
}
