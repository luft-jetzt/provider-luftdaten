<?php declare(strict_types=1);

namespace App\ArchiveFetcher;

use Carbon\Carbon;

interface ArchiveDataLoaderInterface
{
    public function load(Carbon $fromDateTime, Carbon $untilDateTime): array;
}
