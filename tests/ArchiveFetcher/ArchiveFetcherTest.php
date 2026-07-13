<?php

declare(strict_types=1);

namespace App\Tests\ArchiveFetcher;

use App\ArchiveFetcher\ArchiveDataLoaderInterface;
use App\ArchiveFetcher\ArchiveFetcher;
use App\Parser\CsvParser;
use Caldera\LuftModel\Model\Value;
use PHPUnit\Framework\TestCase;

class ArchiveFetcherTest extends TestCase
{
    private const string FIXTURE = __DIR__.'/../fixtures/archive_sample.csv';

    private ArchiveFetcher $fetcher;

    protected function setUp(): void
    {
        $this->fetcher = new ArchiveFetcher(new CsvParser(), $this->createStub(ArchiveDataLoaderInterface::class));
    }

    public function testFetchFiltersToTimeWindowInclusiveOfBoundaries(): void
    {
        $values = $this->fetcher->fetch(
            self::FIXTURE,
            new \DateTimeImmutable('2024-01-15 12:00:00'),
            new \DateTimeImmutable('2024-01-15 14:00:00'),
        );

        // Rows 12:00 and 14:00 (boundaries included), each yields pm10 + pm25.
        $this->assertCount(4, $values);

        $timestamps = array_unique(array_map(fn (Value $v) => $v->getDateTime()->format('H:i'), $values));
        sort($timestamps);
        $this->assertSame(['12:00', '14:00'], $timestamps);
    }

    public function testFetchWithEqualFromAndUntilReturnsSingleTimestamp(): void
    {
        $values = $this->fetcher->fetch(
            self::FIXTURE,
            new \DateTimeImmutable('2024-01-15 12:00:00'),
            new \DateTimeImmutable('2024-01-15 12:00:00'),
        );

        $this->assertCount(2, $values);
        $this->assertSame('12:00', $values[0]->getDateTime()->format('H:i'));
    }

    public function testFetchAppliesPollutantFilter(): void
    {
        $values = $this->fetcher->fetch(
            self::FIXTURE,
            new \DateTimeImmutable('2024-01-15 00:00:00'),
            new \DateTimeImmutable('2024-01-15 23:59:59'),
            'pm10',
        );

        // 4 valid rows, pm10 only.
        $this->assertCount(4, $values);
        $this->assertSame(['pm10'], array_values(array_unique(array_map(fn (Value $v) => $v->getPollutant(), $values))));
    }

    public function testFetchSkipsBrokenRowWithoutAbortingTheRest(): void
    {
        $values = $this->fetcher->fetch(
            self::FIXTURE,
            new \DateTimeImmutable('2024-01-15 00:00:00'),
            new \DateTimeImmutable('2024-01-15 23:59:59'),
        );

        // 4 parseable rows (the row with an invalid timestamp is skipped), 2 values each.
        $this->assertCount(8, $values);

        // Processing continued past the broken row: the 16:00 row is still present.
        $lastRowTimes = array_filter($values, fn (Value $v) => '16:00' === $v->getDateTime()->format('H:i'));
        $this->assertCount(2, $lastRowTimes);
    }
}
