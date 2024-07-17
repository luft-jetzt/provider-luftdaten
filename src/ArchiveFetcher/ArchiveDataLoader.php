<?php declare(strict_types=1);

namespace App\ArchiveFetcher;

use Carbon\Carbon;
use GuzzleHttp\Client;
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

    public function load(Carbon $fromDateTime, Carbon $untilDateTime): array
    {
        $currentDateTime = $fromDateTime->copy();

        $csvUriList = [];

        do {
            $newCsvList = $this->processDate($currentDateTime);

            $csvUriList = [...$csvUriList, ...$newCsvList];

            $currentDateTime->addDay();
        } while ($currentDateTime->format('Y-m-d') <= $untilDateTime->format('Y-m-d')); // ignore hours to make sure we catch every day

        return $csvUriList;
    }

    protected function processDate(Carbon $date): array
    {
        $indexUri = $this->generateIndexUri($date);
        //$indexPageResponse = $this->client->get($indexUri);
        //$indexPage = $indexPageResponse->getBody()->getContents();
        $indexPage = file_get_contents($indexUri.'/index.html');

        $crawler = new Crawler($indexPage);

        $csvUriList = [];

        $crawler->filter('a')->each(function (Crawler $element) use (&$csvUriList, $indexUri): void {
            $href = $element->attr('href');

            if ($this->acceptsLink($href)) {
                $csvUri = sprintf('%s%s', $indexUri, $href);

                $csvUriList[] = $csvUri;
            }
        });

        return $csvUriList;
    }

    protected function generateIndexUri(Carbon $date): string
    {
        //return sprintf('https://archive.sensor.community/%s/', $date->format('Y-m-d'));
        return sprintf('/volume1/Luftdaten-Archiv/archive.sensor.community/%s/', $date->format('Y-m-d'));
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
