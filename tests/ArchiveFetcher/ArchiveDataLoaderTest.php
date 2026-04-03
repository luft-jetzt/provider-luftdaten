<?php

declare(strict_types=1);

namespace App\Tests\ArchiveFetcher;

use App\ArchiveFetcher\ArchiveDataLoader;
use PHPUnit\Framework\TestCase;

class ArchiveDataLoaderTest extends TestCase
{
    public function testAcceptsLinkWithValidSds011CsvLink(): void
    {
        $loader = $this->createLoader();

        $this->assertTrue($this->invokeAcceptsLink($loader, '2024-01-15_sds011_sensor_12345.csv'));
    }

    public function testAcceptsLinkWithValidPms7003CsvLink(): void
    {
        $loader = $this->createLoader();

        $this->assertTrue($this->invokeAcceptsLink($loader, '2024-01-15_pms7003_sensor_67890.csv'));
    }

    public function testRejectsLinkWithoutCsvExtension(): void
    {
        $loader = $this->createLoader();

        $this->assertFalse($this->invokeAcceptsLink($loader, '2024-01-15_sds011_sensor_12345.json'));
    }

    public function testRejectsLinkWithUnknownSensorType(): void
    {
        $loader = $this->createLoader();

        $this->assertFalse($this->invokeAcceptsLink($loader, '2024-01-15_bme280_sensor_12345.csv'));
    }

    public function testRejectsCsvLinkWithoutSensorName(): void
    {
        $loader = $this->createLoader();

        $this->assertFalse($this->invokeAcceptsLink($loader, 'random_data.csv'));
    }

    public function testGenerateIndexUriFormatsDateCorrectly(): void
    {
        $loader = $this->createLoader('/archive/path');

        $date = new \DateTimeImmutable('2024-03-15');
        $uri = $this->invokeGenerateIndexUri($loader, $date);

        $this->assertSame('/archive/path/2024-03-15/', $uri);
    }

    public function testGenerateIndexUriTrimsTrailingSlash(): void
    {
        $loader = $this->createLoader('/archive/path/');

        $date = new \DateTimeImmutable('2024-03-15');
        $uri = $this->invokeGenerateIndexUri($loader, $date);

        $this->assertSame('/archive/path/2024-03-15/', $uri);
    }

    public function testAllSupportedSensorTypesAccepted(): void
    {
        $loader = $this->createLoader();

        $sensorTypes = [
            'pms5003_sensor',
            'pms7003_sensor',
            'sds011_sensor',
            'sds018_sensor',
            'sds021_sensor',
            'ppd42ns_sensor',
            'hpm_sensor',
        ];

        foreach ($sensorTypes as $sensorType) {
            $link = sprintf('2024-01-15_%s_12345.csv', $sensorType);
            $this->assertTrue($this->invokeAcceptsLink($loader, $link), "Sensor type $sensorType should be accepted");
        }
    }

    private function createLoader(string $basePath = '/test/archive'): ArchiveDataLoader
    {
        return new ArchiveDataLoader($basePath);
    }

    private function invokeAcceptsLink(ArchiveDataLoader $loader, string $link): bool
    {
        $method = new \ReflectionMethod($loader, 'acceptsLink');

        return $method->invoke($loader, $link);
    }

    private function invokeGenerateIndexUri(ArchiveDataLoader $loader, \DateTimeInterface $date): string
    {
        $method = new \ReflectionMethod($loader, 'generateIndexUri');

        return $method->invoke($loader, $date);
    }
}
