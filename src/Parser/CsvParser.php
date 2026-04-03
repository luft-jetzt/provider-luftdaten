<?php

declare(strict_types=1);

namespace App\Parser;

use Caldera\LuftModel\Model\Value;

class CsvParser implements CsvParserInterface
{
    /**
     * @param array<string, string> $csvRecord
     *
     * @return Value[]
     */
    public function parseRecord(array $csvRecord): array
    {
        $pm10Value = $this->createGenericValueFromRecord($csvRecord);
        $pm10Value
            ->setPollutant('pm10')
            ->setValue((float) $csvRecord['P1']);

        $pm25Value = $this->createGenericValueFromRecord($csvRecord);
        $pm25Value
            ->setPollutant('pm25')
            ->setValue((float) $csvRecord['P2']);

        return [$pm10Value, $pm25Value];
    }

    /** @param array<string, string> $csvRecord */
    protected function createGenericValueFromRecord(array $csvRecord): Value
    {
        $value = new Value();

        $value
            ->setStationCode($this->generateStationCode((int) $csvRecord['location']))
            ->setDateTime(new \DateTime($csvRecord['timestamp']))
        ;

        return $value;
    }

    protected function generateStationCode(int $sensorId): string
    {
        return sprintf('LFTDTN%d', $sensorId);
    }
}
