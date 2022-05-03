<?php declare(strict_types=1);

namespace App\Parser;

interface CsvParserInterface
{
    public function parseRecord(array $csvRecord): array;
}
