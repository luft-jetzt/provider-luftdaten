<?php declare(strict_types=1);

namespace App\Parser;

use Caldera\LuftApiBundle\Model\Value;
use JMS\Serializer\SerializerInterface;

class JsonParser implements JsonParserInterface
{
    protected array $stationList = [];

    protected SerializerInterface $serializer;

    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    public function parse(string $dataString): array
    {
        $dataList = json_decode($dataString);

        $valueList = [];

        foreach ($dataList as $data) {
            try {
                $stationCode = sprintf('LFTDTN%d', $data->location->id);

                $dateTime = new \DateTime($data->timestamp);

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

    protected function getValues(array $sensorDataValues): array
    {
        $valueList = [];

        foreach ($sensorDataValues as $sensorDataValue) {
            $value = new Value();
            $value->setValue((float) $sensorDataValue->value);

            if ($sensorDataValue->value_type === 'P1') {
                $value->setPollutant('pm10');
            } elseif ($sensorDataValue->value_type === 'P2') {
                $value->setPollutant('pm25');
            } else {
                continue;
            }

            $valueList[] = $value;
        }

        return $valueList;
    }
}
