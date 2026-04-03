<?php

declare(strict_types=1);

namespace App\ArchiveFetcher;

interface ArchiveDataLoaderInterface
{
    /** @return string[] */
    public function load(\DateTimeInterface $fromDateTime, \DateTimeInterface $untilDateTime): array;
}
