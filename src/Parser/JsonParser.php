<?php

declare(strict_types=1);

namespace App\Parser;

use Caldera\LuftModel\Model\Value;
use Psr\Log\LoggerInterface;

class JsonParser implements JsonParserInterface
{
    public function __construct(protected LoggerInterface $logger)
    {
    }

    /** @return Value[] */
    public function parse(string $dataString): array
    {
        $dataList = json_decode($dataString, false, 512, \JSON_THROW_ON_ERROR);

        if (!is_array($dataList)) {
            throw new \RuntimeException('Failed to parse JSON response: expected array');
        }

        $valueList = [];

        foreach ($dataList as $data) {
            try {
                $stationCode = sprintf('LFTDTN%d', $data->location->id);

                $dateTime = new \DateTime($data->timestamp, new \DateTimeZone('UTC'));

                $newValueList = $this->getValues($data->sensordatavalues);

                /** @var Value $value */
                foreach ($newValueList as $value) {
                    $value
                        ->setStationCode($stationCode)
                        ->setDateTime($dateTime);
                }

                $valueList = array_merge($valueList, $newValueList);
            } catch (\Throwable $e) {
                $this->logger->warning('Failed to parse sensor data entry', ['exception' => $e]);
            }
        }

        return $valueList;
    }

    /**
     * @param array<int, object> $sensorDataValues
     *
     * @return Value[]
     */
    protected function getValues(array $sensorDataValues): array
    {
        $valueList = [];

        foreach ($sensorDataValues as $sensorDataValue) {
            $value = new Value();
            $value->setValue((float) $sensorDataValue->value);

            if ('P1' === $sensorDataValue->value_type) {
                $value->setPollutant('pm10');
            } elseif ('P2' === $sensorDataValue->value_type) {
                $value->setPollutant('pm25');
            } else {
                continue;
            }

            $valueList[] = $value;
        }

        return $valueList;
    }
}
