<?php

declare(strict_types=1);

namespace App\ArchiveFetcher;

use App\Parser\CsvParserInterface;
use Caldera\LuftModel\Model\Value;
use League\Csv\Reader;
use Psr\Log\LoggerInterface;

class ArchiveFetcher implements ArchiveFetcherInterface
{
    public function __construct(protected CsvParserInterface $csvParser, protected ArchiveDataLoaderInterface $archiveDataLoader, protected LoggerInterface $logger)
    {
    }

    /**
     * @return Value[]
     */
    public function fetch(string $filename, \DateTimeInterface $fromDateTime, \DateTimeInterface $untilDateTime, ?string $pollutant = null): array
    {
        $valueList = [];
        $discardedCount = 0;

        $csvFile = Reader::createFromPath($filename);
        $csvFile->setHeaderOffset(0)->setDelimiter(';');

        foreach ($csvFile->getRecords() as $offset => $record) {
            try {
                $parsedValues = $this->csvParser->parseRecord($record);
            } catch (\Exception $exception) {
                ++$discardedCount;

                $this->logger->debug('Skipped unparseable archive CSV row', [
                    'file' => $filename,
                    'row' => $offset,
                    'exception' => $exception->getMessage(),
                ]);

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

        if ($discardedCount > 0) {
            $this->logger->warning('Discarded unparseable rows while reading archive file', [
                'file' => $filename,
                'discarded' => $discardedCount,
                'kept' => count($valueList),
            ]);
        }

        return $valueList;
    }
}
