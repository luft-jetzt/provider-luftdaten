<?php declare(strict_types=1);

namespace App\ArchiveFetcher;

use App\Parser\CsvParserInterface;
use Caldera\LuftApiBundle\Model\Value;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use GuzzleHttp\Client;
use League\Csv\Reader;

class ArchiveFetcher implements ArchiveFetcherInterface
{
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
            $csvFileContentList = $this->archiveDataLoader->load($date);

            foreach ($csvFileContentList as $csvFileContent) {
                $csvFile = Reader::createFromString($csvFileContent);

                $csvFile->setHeaderOffset(0)->setDelimiter(';');

                foreach ($csvFile->getRecords() as $record) {
                    $parsedValues = $this->csvParser->parseRecord($record);

                    /** @var Value $parsedValue */
                    foreach ($parsedValues as $parsedValue) {
                        if ($parsedValue->getDateTime() >= $fromDateTime && $parsedValue->getDateTime() <= $untilDateTime) {
                            $valueList[] = $parsedValue;
                        }
                    }
                }
            }

            $date = $date->add($dayInterval);
        } while ($date < $untilDateTime);

        return $valueList;
    }
}
