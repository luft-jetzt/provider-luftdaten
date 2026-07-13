<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\LuftFetchCommand;
use App\SourceFetcher\SourceFetcherInterface;
use Caldera\LuftApiBundle\Api\ValueApiInterface;
use Caldera\LuftModel\Model\Value;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

class LuftFetchCommandTest extends TestCase
{
    public function testExecuteSendsFetchedValuesToApi(): void
    {
        $valueList = [$this->createValue(), $this->createValue()];

        $sourceFetcher = $this->createStub(SourceFetcherInterface::class);
        $sourceFetcher->method('fetch')->willReturn($valueList);

        $valueApi = $this->createMock(ValueApiInterface::class);
        $valueApi->expects($this->once())->method('putValues')->with($valueList);

        $tester = new CommandTester(new LuftFetchCommand($sourceFetcher, $valueApi));
        $tester->execute([]);

        $tester->assertCommandIsSuccessful();
    }

    /**
     * Regression test: the verbose output path renders a table typed against
     * Caldera\LuftModel\Model\Value. A missing import made `luft:fetch -v` crash
     * with a TypeError after values had already been published.
     */
    public function testExecuteVerboseRendersValueTableWithoutCrashing(): void
    {
        $sourceFetcher = $this->createStub(SourceFetcherInterface::class);
        $sourceFetcher->method('fetch')->willReturn([$this->createValue('LFTDTN42', 'pm10', 33.3)]);

        $valueApi = $this->createStub(ValueApiInterface::class);

        $tester = new CommandTester(new LuftFetchCommand($sourceFetcher, $valueApi));
        $tester->execute([], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]);

        $tester->assertCommandIsSuccessful();

        $display = $tester->getDisplay();
        $this->assertStringContainsString('LFTDTN42', $display);
        $this->assertStringContainsString('pm10', $display);
    }

    private function createValue(string $stationCode = 'LFTDTN1', string $pollutant = 'pm10', float $value = 10.0): Value
    {
        return (new Value())
            ->setStationCode($stationCode)
            ->setPollutant($pollutant)
            ->setValue($value)
            ->setDateTime(new \DateTime('2024-01-15 12:00:00'));
    }
}
