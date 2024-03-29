<?php declare(strict_types=1);

namespace App\ArchiveFetcher;

use App\Parser\CsvParserInterface;
use Caldera\LuftModel\Model\Value;
use Carbon\Carbon;
use League\Csv\Reader;

class ArchiveFetcher implements ArchiveFetcherInterface
{
    public function __construct(protected CsvParserInterface $csvParser, protected ArchiveDataLoaderInterface $archiveDataLoader)
    {

    }

    public function fetch(string $filename, Carbon $fromDateTime, Carbon $untilDateTime, string $pollutant = null): array
    {
        $valueList = [];

        $csvFile = Reader::createFromPath($filename);
        $csvFile->setHeaderOffset(0)->setDelimiter(';');

        foreach ($csvFile->getRecords() as $record) {
            try {
                $parsedValues = $this->csvParser->parseRecord($record);
            } catch (\Exception $exception) {
                continue;
            }

            /** @var Value $parsedValue */
            foreach ($parsedValues as $parsedValue) {
                if ($pollutant && $parsedValue->getPollutant() !== $pollutant) {
                    continue;
                }

                if ($parsedValue->getDateTime() >= $fromDateTime && $parsedValue->getDateTime() <= $untilDateTime) {
                    $valueList[] = $parsedValue;
                }
            }
        }

        return $valueList;
    }
}
