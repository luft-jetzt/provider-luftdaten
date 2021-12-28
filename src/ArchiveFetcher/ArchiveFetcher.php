<?php declare(strict_types=1);

namespace App\ArchiveFetcher;

use App\Parser\CsvParserInterface;
use Caldera\LuftApiBundle\Model\Value;
use Carbon\Carbon;
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

    public function fetch(string $filename, Carbon $fromDateTime, Carbon $untilDateTime): array
    {
        $valueList = [];

        $csvFile = Reader::createFromPath($filename);
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

        return $valueList;
    }
}
