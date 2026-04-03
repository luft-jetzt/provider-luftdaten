<?php declare(strict_types=1);

namespace App\ArchiveFetcher;

interface ArchiveDataLoaderInterface
{
    public function load(\DateTimeInterface $fromDateTime, \DateTimeInterface $untilDateTime): array;
}
