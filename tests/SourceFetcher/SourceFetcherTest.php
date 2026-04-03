<?php

declare(strict_types=1);

namespace App\Tests\SourceFetcher;

use App\Parser\JsonParserInterface;
use App\SourceFetcher\SourceFetcher;
use Caldera\LuftModel\Model\Value;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class SourceFetcherTest extends TestCase
{
    public function testFetchReturnsValuesFromApi(): void
    {
        $expectedValues = [new Value(), new Value()];

        $parser = $this->createMock(JsonParserInterface::class);
        $parser->expects($this->once())
            ->method('parse')
            ->with('{"data": "test"}')
            ->willReturn($expectedValues);

        $httpClient = new MockHttpClient([
            new MockResponse('{"data": "test"}'),
        ]);

        $fetcher = new SourceFetcher($parser, $httpClient, 'https://api.example.com/data.json');

        $values = $fetcher->fetch();

        $this->assertSame($expectedValues, $values);
    }

    public function testFetchUsesConfiguredUrl(): void
    {
        $apiUrl = 'https://custom.api.com/data.json';

        $parser = $this->createMock(JsonParserInterface::class);
        $parser->method('parse')->willReturn([]);

        $requestedUrl = null;
        $httpClient = new MockHttpClient(function (string $method, string $url) use (&$requestedUrl) {
            $requestedUrl = $url;

            return new MockResponse('[]');
        });

        $fetcher = new SourceFetcher($parser, $httpClient, $apiUrl);
        $fetcher->fetch();

        $this->assertSame($apiUrl, $requestedUrl);
    }
}
