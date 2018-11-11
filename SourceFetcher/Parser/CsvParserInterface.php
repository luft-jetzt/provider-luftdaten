<?php declare(strict_types=1);

namespace App\Provider\Luftdaten\SourceFetcher\Parser;

interface CsvParserInterface
{
    public function parse(string $csvFileContent): array;
}
