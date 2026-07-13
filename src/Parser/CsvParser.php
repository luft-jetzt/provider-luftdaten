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
        $valueList = [];

        foreach (['pm10' => 'P1', 'pm25' => 'P2'] as $pollutant => $column) {
            $rawValue = (float) $csvRecord[$column];

            if (!$this->isPlausibleValue($rawValue)) {
                continue;
            }

            $value = $this->createGenericValueFromRecord($csvRecord);
            $value
                ->setPollutant($pollutant)
                ->setValue($rawValue);

            $valueList[] = $value;
        }

        return $valueList;
    }

    /**
     * Guards against corrupt archive readings (NaN/Inf, negative or absurdly high
     * particulate concentrations).
     */
    protected function isPlausibleValue(float $value): bool
    {
        return is_finite($value) && $value >= 0.0 && $value <= 10000.0;
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
