<?php declare(strict_types=1);

namespace App\ArchiveFetcher;

use Carbon\Carbon;

interface ArchiveFetcherInterface
{
    public function fetch(Carbon $fromDateTime, Carbon $untilDateTime): array;
}
