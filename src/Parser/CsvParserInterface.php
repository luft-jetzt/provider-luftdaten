<?php

declare(strict_types=1);

namespace App\Parser;

use Caldera\LuftModel\Model\Value;

interface CsvParserInterface
{
    /**
     * @param array<string, string> $csvRecord
     *
     * @return Value[]
     */
    public function parseRecord(array $csvRecord): array;
}
