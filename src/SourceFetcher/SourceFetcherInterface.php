<?php

declare(strict_types=1);

namespace App\SourceFetcher;

use Caldera\LuftModel\Model\Value;

interface SourceFetcherInterface
{
    /** @return Value[] */
    public function fetch(): array;
}
