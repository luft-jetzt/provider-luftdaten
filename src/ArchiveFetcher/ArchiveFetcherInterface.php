<?php declare(strict_types=1);

namespace App\ArchiveFetcher;

use Carbon\Carbon;

interface ArchiveFetcherInterface
{
    public function fetch(string $filename, Carbon $fromDateTime, Carbon $untilDateTime, string $pollutant = null): array;
}
