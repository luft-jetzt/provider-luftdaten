<?php declare(strict_types=1);

namespace App\ArchiveFetcher;

use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Symfony\Component\DomCrawler\Crawler;

class ArchiveDataLoader implements ArchiveDataLoaderInterface
{
    protected Client $client;
    protected array $sensorList = [
        'pms5003_sensor',
        'pms7003_sensor',
        'sds011_sensor',
        'sds018_sensor',
        'sds021_sensor',
        'ppd42ns_sensor',
        'hpm_sensor',
    ];

    public function __construct()
    {
        $this->client = new Client();
    }

    public function load(Carbon $date): array
    {
        $indexUri = $this->generateIndexUri($date);
        $indexPageResponse = $this->client->get($indexUri);
        $indexPage = $indexPageResponse->getBody()->getContents();

        $crawler = new Crawler($indexPage);

        $csvUriList = [];

        $crawler->filter('a')->each(function (Crawler $element) use (&$csvUriList, $indexUri): void {
            $href = $element->attr('href');

            if ($this->acceptsLink($href)) {
                $csvUri = sprintf('%s%s', $indexUri, $href);

                $csvUriList[] = $csvUri;
            }
        });

        foreach ($csvUriList as $csvUri) {
            $csvFileResponse = $this->client->get($csvUri);
            $csvFileContent = $csvFileResponse->getBody()->getContents();

            $csvContentList = $csvFileContent;
        }

        return $csvContentList;
    }

    protected function generateIndexUri(Carbon $date): string
    {
        return sprintf('https://archive.sensor.community/%s/', $date->format('Y-m-d'));
    }

    protected function acceptsLink(string $link): bool
    {
        if (strpos($link, '.csv') === false) {
            return false;
        }

        foreach ($this->sensorList as $sensorName) {
            if (strpos($link, $sensorName) !== false) {
                return true;
            }
        }

        return false;
    }
}
