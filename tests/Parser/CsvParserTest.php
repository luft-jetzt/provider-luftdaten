<?php

declare(strict_types=1);

namespace App\Tests\Parser;

use App\Parser\CsvParser;
use PHPUnit\Framework\TestCase;

class CsvParserTest extends TestCase
{
    private CsvParser $parser;

    protected function setUp(): void
    {
        $this->parser = new CsvParser();
    }

    public function testParseRecordReturnsTwoValues(): void
    {
        $record = $this->createValidRecord();

        $values = $this->parser->parseRecord($record);

        $this->assertCount(2, $values);
    }

    public function testParseRecordReturnsPm10AndPm25(): void
    {
        $record = $this->createValidRecord();

        $values = $this->parser->parseRecord($record);

        $this->assertSame('pm10', $values[0]->getPollutant());
        $this->assertSame('pm25', $values[1]->getPollutant());
    }

    public function testParseRecordSetsCorrectValues(): void
    {
        $record = $this->createValidRecord(['P1' => '42.5', 'P2' => '18.3']);

        $values = $this->parser->parseRecord($record);

        $this->assertSame(42.5, $values[0]->getValue());
        $this->assertSame(18.3, $values[1]->getValue());
    }

    public function testParseRecordGeneratesCorrectStationCode(): void
    {
        $record = $this->createValidRecord(['location' => '12345']);

        $values = $this->parser->parseRecord($record);

        $this->assertSame('LFTDTN12345', $values[0]->getStationCode());
        $this->assertSame('LFTDTN12345', $values[1]->getStationCode());
    }

    public function testParseRecordSetsCorrectDateTime(): void
    {
        $record = $this->createValidRecord(['timestamp' => '2024-01-15 10:30:00']);

        $values = $this->parser->parseRecord($record);

        $this->assertSame('2024-01-15 10:30:00', $values[0]->getDateTime()->format('Y-m-d H:i:s'));
    }

    public function testParseRecordWithZeroValues(): void
    {
        $record = $this->createValidRecord(['P1' => '0', 'P2' => '0']);

        $values = $this->parser->parseRecord($record);

        $this->assertSame(0.0, $values[0]->getValue());
        $this->assertSame(0.0, $values[1]->getValue());
    }

    /**
     * @param array<string, string> $overrides
     *
     * @return array<string, string>
     */
    private function createValidRecord(array $overrides = []): array
    {
        return array_merge([
            'location' => '1234',
            'timestamp' => '2024-01-15 12:00:00',
            'P1' => '25.5',
            'P2' => '12.3',
        ], $overrides);
    }
}
