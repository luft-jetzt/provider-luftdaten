<?php declare(strict_types=1);

namespace App\ArchiveFetcher;

interface ArchiveFetcherInterface
{
    public function fetch(string $filename, \DateTimeInterface $fromDateTime, \DateTimeInterface $untilDateTime, string $pollutant = null): array;
}
