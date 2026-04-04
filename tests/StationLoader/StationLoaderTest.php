<?php

declare(strict_types=1);

namespace App\Tests\StationLoader;

use App\StationLoader\StationLoader;
use Caldera\LuftApiBundle\Api\StationApiInterface;
use Caldera\LuftModel\Model\Station;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class StationLoaderTest extends TestCase
{
    public function testLoadDetectsNewStations(): void
    {
        $stationApi = $this->createMock(StationApiInterface::class);
        $stationApi->method('getStations')->willReturn([]);

        $loader = $this->createLoader([$this->createSensorData(locationId: 42)], $stationApi);

        $result = $loader->load();

        $this->assertCount(1, $result->getNewStationList());
        $this->assertArrayHasKey('LFTDTN42', $result->getNewStationList());
        $this->assertCount(0, $result->getChangedStationList());
        $this->assertCount(0, $result->getExistingStationList());
    }

    public function testLoadDetectsExistingUnchangedStations(): void
    {
        $existingStation = (new Station())
            ->setStationCode('LFTDTN42')
            ->setLatitude(52.52)
            ->setLongitude(13.405)
            ->setAltitude(34)
            ->setProvider('ld');

        $stationApi = $this->createMock(StationApiInterface::class);
        $stationApi->method('getStations')->willReturn(['LFTDTN42' => $existingStation]);

        $loader = $this->createLoader([$this->createSensorData(locationId: 42, latitude: '52.52', longitude: '13.405', altitude: '34')], $stationApi);

        $result = $loader->load();

        $this->assertCount(0, $result->getNewStationList());
        $this->assertCount(0, $result->getChangedStationList());
        $this->assertCount(1, $result->getExistingStationList());
    }

    public function testLoadDetectsChangedStations(): void
    {
        $existingStation = (new Station())
            ->setStationCode('LFTDTN42')
            ->setLatitude(52.52)
            ->setLongitude(13.405)
            ->setAltitude(34)
            ->setProvider('ld');

        $stationApi = $this->createMock(StationApiInterface::class);
        $stationApi->method('getStations')->willReturn(['LFTDTN42' => $existingStation]);

        $loader = $this->createLoader([$this->createSensorData(locationId: 42, latitude: '53.00', longitude: '13.405', altitude: '34')], $stationApi);

        $result = $loader->load();

        $this->assertCount(0, $result->getNewStationList());
        $this->assertCount(1, $result->getChangedStationList());
        $this->assertSame(53.0, $result->getChangedStationList()['LFTDTN42']->getLatitude());
    }

    public function testLoadDeduplicatesByStationCode(): void
    {
        $stationApi = $this->createMock(StationApiInterface::class);
        $stationApi->method('getStations')->willReturn([]);

        $loader = $this->createLoader([
            $this->createSensorData(locationId: 42),
            $this->createSensorData(locationId: 42),
            $this->createSensorData(locationId: 42),
        ], $stationApi);

        $result = $loader->load();

        $this->assertCount(1, $result->getNewStationList());
    }

    public function testLoadSetsCorrectStationProperties(): void
    {
        $stationApi = $this->createMock(StationApiInterface::class);
        $stationApi->method('getStations')->willReturn([]);

        $loader = $this->createLoader([$this->createSensorData(locationId: 99, latitude: '48.137', longitude: '11.576', altitude: '520')], $stationApi);

        $result = $loader->load();

        $station = $result->getNewStationList()['LFTDTN99'];
        $this->assertSame('LFTDTN99', $station->getStationCode());
        $this->assertSame(48.137, $station->getLatitude());
        $this->assertSame(11.576, $station->getLongitude());
        $this->assertSame(520, $station->getAltitude());
        $this->assertSame('ld', $station->getProvider());
    }

    public function testLoadSkipsEntriesWithMissingLocation(): void
    {
        $stationApi = $this->createMock(StationApiInterface::class);
        $stationApi->method('getStations')->willReturn([]);

        $malformed = (object) ['timestamp' => '2024-01-15 12:00:00'];
        $loader = $this->createLoader([$malformed, $this->createSensorData(locationId: 1)], $stationApi);

        $result = $loader->load();

        $this->assertCount(1, $result->getNewStationList());
    }

    public function testLoadHandlesMultipleStationsWithMixedStates(): void
    {
        $existingStation = (new Station())
            ->setStationCode('LFTDTN1')
            ->setLatitude(52.52)
            ->setLongitude(13.405)
            ->setAltitude(34)
            ->setProvider('ld');

        $changedStation = (new Station())
            ->setStationCode('LFTDTN2')
            ->setLatitude(48.0)
            ->setLongitude(11.0)
            ->setAltitude(500)
            ->setProvider('ld');

        $stationApi = $this->createMock(StationApiInterface::class);
        $stationApi->method('getStations')->willReturn([
            'LFTDTN1' => $existingStation,
            'LFTDTN2' => $changedStation,
        ]);

        $loader = $this->createLoader([
            $this->createSensorData(locationId: 1, latitude: '52.52', longitude: '13.405', altitude: '34'),
            $this->createSensorData(locationId: 2, latitude: '48.5', longitude: '11.0', altitude: '500'),
            $this->createSensorData(locationId: 3, latitude: '50.0', longitude: '8.0', altitude: '100'),
        ], $stationApi);

        $result = $loader->load();

        $this->assertCount(1, $result->getExistingStationList());
        $this->assertCount(1, $result->getChangedStationList());
        $this->assertCount(1, $result->getNewStationList());
    }

    /**
     * @param array<object> $sensorDataList
     */
    private function createLoader(array $sensorDataList, StationApiInterface $stationApi): StationLoader
    {
        $responseBody = json_encode($sensorDataList);
        $httpClient = new MockHttpClient(new MockResponse($responseBody));

        return new StationLoader($httpClient, $stationApi, 'https://api.luftdaten.info/static/v2/data.dust.min.json');
    }

    private function createSensorData(int $locationId = 100, string $latitude = '52.52', string $longitude = '13.405', string $altitude = '34'): object
    {
        return (object) [
            'location' => (object) [
                'id' => $locationId,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'altitude' => $altitude,
            ],
            'timestamp' => '2024-01-15 12:00:00',
            'sensordatavalues' => [
                (object) ['value_type' => 'P1', 'value' => '30.0'],
            ],
        ];
    }
}
