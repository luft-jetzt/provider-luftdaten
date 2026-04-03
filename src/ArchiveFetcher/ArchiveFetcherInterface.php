<?php

declare(strict_types=1);

namespace App\ArchiveFetcher;

use Caldera\LuftModel\Model\Value;

interface ArchiveFetcherInterface
{
    /**
     * @return Value[]
     */
    public function fetch(string $filename, \DateTimeInterface $fromDateTime, \DateTimeInterface $untilDateTime, ?string $pollutant = null): array;
}
