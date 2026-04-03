<?php declare(strict_types=1);

namespace App\ArchiveFetcher;

use Symfony\Component\DomCrawler\Crawler;

class ArchiveDataLoader implements ArchiveDataLoaderInterface
{
    protected array $sensorList = [
        'pms5003_sensor',
        'pms7003_sensor',
        'sds011_sensor',
        'sds018_sensor',
        'sds021_sensor',
        'ppd42ns_sensor',
        'hpm_sensor',
    ];

    public function __construct(
        #[\Symfony\Component\DependencyInjection\Attribute\Autowire('%env(ARCHIVE_BASE_PATH)%')]
        protected string $archiveBasePath,
    ) {
    }

    public function load(\DateTimeInterface $fromDateTime, \DateTimeInterface $untilDateTime): array
    {
        $currentDateTime = \DateTimeImmutable::createFromInterface($fromDateTime);

        $csvUriList = [];

        do {
            $newCsvList = $this->processDate($currentDateTime);

            $csvUriList = [...$csvUriList, ...$newCsvList];

            $currentDateTime = $currentDateTime->modify('+1 day');
        } while ($currentDateTime->format('Y-m-d') <= $untilDateTime->format('Y-m-d'));

        return $csvUriList;
    }

    protected function processDate(\DateTimeInterface $date): array
    {
        $indexUri = $this->generateIndexUri($date);
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

    protected function generateIndexUri(\DateTimeInterface $date): string
    {
        return sprintf('%s/%s/', rtrim($this->archiveBasePath, '/'), $date->format('Y-m-d'));
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
