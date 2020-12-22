<?php declare(strict_types=1);

namespace App\ArchiveFetcher;

use App\Parser\CsvParserInterface;
use GuzzleHttp\Client;

class ArchiveFetcher implements ArchiveFetcherInterface
{
    protected Client $client;
    protected array $csvLinkList = [];
    protected CsvParserInterface $csvParser;

    public function __construct(CsvParserInterface $csvParser)
    {
        $this->csvParser = $csvParser;
        $this->client = new Client();
    }

    protected function checkSensorName(string $csvFilename): bool
    {
        $acceptedSensorNames = [
            'pms5003_sensor',
            'pms7003_sensor',
            'sds011_sensor',
            'sds018_sensor',
            'sds021_sensor',
            'ppd42ns_sensor',
            'hpm_sensor',
        ];
        
        $result = false;

        foreach ($acceptedSensorNames as $acceptedSensorName) {
            if (false !== strpos($csvFilename, $acceptedSensorName)) {
                $result = true;

                break;
            }
        }

        return $result;
    }

    public function setCsvLinkList(array $csvLinkList): ArchiveFetcherInterface
    {
        $this->csvLinkList = $csvLinkList;

        return $this;
    }

    public function fetch(callable $callback): array
    {
        $valueList = [];

        foreach ($this->csvLinkList as $csvLink) {
            $callback();

            if (!$this->checkSensorName($csvLink)) {
                continue;
            }

            $csvFileContent = $this->archiveSourceFetcher->loadCsvContent($csvLink);

            $valueList = array_merge($this->csvParser->parse($csvFileContent), $valueList);
        }

        return $valueList;
    }

    protected function query(): string
    {
        $result = $this->client->get('https://api.luftdaten.info/static/v2/data.dust.min.json');

        return $result->getBody()->getContents();
    }
}
