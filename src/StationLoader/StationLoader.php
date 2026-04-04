<?php

declare(strict_types=1);

namespace App\StationLoader;

use Caldera\LuftApiBundle\Api\StationApiInterface;
use Caldera\LuftModel\Model\Station;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class StationLoader implements StationLoaderInterface
{
    public function __construct(
        protected HttpClientInterface $httpClient,
        protected StationApiInterface $stationApi,
        #[Autowire('%env(LUFTDATEN_API_URL)%')]
        protected string $apiUrl,
    ) {
    }

    public function load(): StationLoaderResult
    {
        $result = new StationLoaderResult();

        $existingStationList = array_filter($this->stationApi->getStations(), fn (Station $station) => 'ld' === $station->getProvider());

        $response = $this->httpClient->request('GET', $this->apiUrl);
        $sensorDataList = json_decode($response->getContent(), false, 512, JSON_THROW_ON_ERROR);

        $parsedStations = $this->parseStations($sensorDataList);

        foreach ($parsedStations as $stationCode => $station) {
            if (!array_key_exists($stationCode, $existingStationList)) {
                $result->addNewStation($station);
            } elseif ($this->hasStationChanged($existingStationList[$stationCode], $station)) {
                $result->addChangedStation($station);
            }
        }

        $result->setExistingStationList(array_diff_key($existingStationList, $result->getNewStationList(), $result->getChangedStationList()));

        return $result;
    }

    /**
     * @param array<\stdClass> $sensorDataList
     *
     * @return array<string, Station>
     */
    protected function parseStations(array $sensorDataList): array
    {
        $stations = [];

        foreach ($sensorDataList as $sensorData) {
            if (!isset($sensorData->location->id, $sensorData->location->latitude, $sensorData->location->longitude)) {
                continue;
            }

            $stationCode = sprintf('LFTDTN%d', $sensorData->location->id);

            if (array_key_exists($stationCode, $stations)) {
                continue;
            }

            $station = new Station();
            $station
                ->setStationCode($stationCode)
                ->setLatitude((float) $sensorData->location->latitude)
                ->setLongitude((float) $sensorData->location->longitude)
                ->setAltitude((int) $sensorData->location->altitude)
                ->setProvider('ld');

            $stations[$stationCode] = $station;
        }

        return $stations;
    }

    protected function hasStationChanged(Station $existing, Station $new): bool
    {
        return $existing->getLatitude() !== $new->getLatitude()
            || $existing->getLongitude() !== $new->getLongitude()
            || $existing->getAltitude() !== $new->getAltitude();
    }
}
