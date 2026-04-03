<?php

declare(strict_types=1);

namespace App\Tests\Parser;

use App\Parser\JsonParser;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class JsonParserTest extends TestCase
{
    private JsonParser $parser;

    protected function setUp(): void
    {
        $this->parser = new JsonParser(new NullLogger());
    }

    public function testParseValidJsonWithSingleSensor(): void
    {
        $json = json_encode([$this->createSensorData()]);

        $values = $this->parser->parse($json);

        $this->assertCount(2, $values);
    }

    public function testParseSetsCorrectPollutants(): void
    {
        $json = json_encode([$this->createSensorData()]);

        $values = $this->parser->parse($json);

        $pollutants = array_map(fn ($v) => $v->getPollutant(), $values);
        $this->assertContains('pm10', $pollutants);
        $this->assertContains('pm25', $pollutants);
    }

    public function testParseSetsCorrectStationCode(): void
    {
        $json = json_encode([$this->createSensorData(locationId: 42)]);

        $values = $this->parser->parse($json);

        $this->assertSame('LFTDTN42', $values[0]->getStationCode());
    }

    public function testParseSetsUtcTimezone(): void
    {
        $json = json_encode([$this->createSensorData()]);

        $values = $this->parser->parse($json);

        $this->assertSame('UTC', $values[0]->getDateTime()->getTimezone()->getName());
    }

    public function testParseSetsCorrectValues(): void
    {
        $data = $this->createSensorData(p1Value: '55.2', p2Value: '23.8');
        $json = json_encode([$data]);

        $values = $this->parser->parse($json);

        $pm10 = array_values(array_filter($values, fn ($v) => 'pm10' === $v->getPollutant()));
        $pm25 = array_values(array_filter($values, fn ($v) => 'pm25' === $v->getPollutant()));

        $this->assertSame(55.2, $pm10[0]->getValue());
        $this->assertSame(23.8, $pm25[0]->getValue());
    }

    public function testParseWithMultipleSensors(): void
    {
        $json = json_encode([
            $this->createSensorData(locationId: 1),
            $this->createSensorData(locationId: 2),
        ]);

        $values = $this->parser->parse($json);

        $this->assertCount(4, $values);
    }

    public function testParseSkipsNonDustSensorTypes(): void
    {
        $data = $this->createSensorData();
        $data->sensordatavalues = [
            (object) ['value_type' => 'temperature', 'value' => '22.5'],
            (object) ['value_type' => 'humidity', 'value' => '65.0'],
        ];
        $json = json_encode([$data]);

        $values = $this->parser->parse($json);

        $this->assertCount(0, $values);
    }

    public function testParseWithEmptyArray(): void
    {
        $values = $this->parser->parse('[]');

        $this->assertCount(0, $values);
    }

    public function testParseWithInvalidJsonThrowsException(): void
    {
        $this->expectException(\JsonException::class);

        $this->parser->parse('invalid json');
    }

    public function testParseWithNonArrayJsonThrowsException(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->parser->parse('"just a string"');
    }

    #[\PHPUnit\Framework\Attributes\WithoutErrorHandler]
    public function testParseSkipsMalformedEntries(): void
    {
        $validData = $this->createSensorData(locationId: 1);
        $malformedData = (object) ['timestamp' => '2024-01-15 12:00:00'];
        $json = json_encode([$malformedData, $validData]);

        $values = $this->parser->parse($json);

        $this->assertCount(2, $values);
    }

    /**
     * @return object{location: object{id: int}, timestamp: string, sensordatavalues: list<object{value_type: string, value: string}>}
     */
    private function createSensorData(int $locationId = 100, string $p1Value = '30.0', string $p2Value = '15.0'): object
    {
        return (object) [
            'location' => (object) ['id' => $locationId],
            'timestamp' => '2024-01-15 12:00:00',
            'sensordatavalues' => [
                (object) ['value_type' => 'P1', 'value' => $p1Value],
                (object) ['value_type' => 'P2', 'value' => $p2Value],
            ],
        ];
    }
}
