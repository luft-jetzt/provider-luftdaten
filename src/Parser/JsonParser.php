<?php

declare(strict_types=1);

namespace App\Parser;

use Caldera\LuftModel\Model\Value;
use JMS\Serializer\SerializerInterface;

class JsonParser implements JsonParserInterface
{
    /** @var array<string, mixed> */
    protected array $stationList = [];

    public function __construct(protected SerializerInterface $serializer)
    {
    }

    /** @return Value[] */
    public function parse(string $dataString): array
    {
        $dataList = json_decode($dataString);

        if (!is_array($dataList)) {
            throw new \RuntimeException(sprintf('Failed to parse JSON response: %s', json_last_error_msg()));
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
            } catch (\Exception $e) {
                var_dump($e);
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
