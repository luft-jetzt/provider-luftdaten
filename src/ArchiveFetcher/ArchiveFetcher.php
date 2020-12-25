<?php declare(strict_types=1);

namespace App\ArchiveFetcher;

use App\Parser\CsvParserInterface;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use GuzzleHttp\Client;

class ArchiveFetcher implements ArchiveFetcherInterface
{
    protected array $csvLinkList = [];
    protected CsvParserInterface $csvParser;
    protected ArchiveDataLoaderInterface $archiveDataLoader;

    public function __construct(CsvParserInterface $csvParser, ArchiveDataLoaderInterface $archiveDataLoader)
    {
        $this->csvParser = $csvParser;
        $this->archiveDataLoader = $archiveDataLoader;
    }

    public function fetch(Carbon $fromDateTime, Carbon $untilDateTime): array
    {
        $valueList = [];

        $date = $fromDateTime->copy();
        $dayInterval = new CarbonInterval('P1D');

        do {
            $result = $this->archiveDataLoader->load($date);

            dump($result);
            $date = $date->add($dayInterval);
        } while ($date < $untilDateTime);

        return $valueList;
    }
}
