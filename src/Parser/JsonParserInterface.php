<?php

declare(strict_types=1);

namespace App\Parser;

use Caldera\LuftModel\Model\Value;

interface JsonParserInterface
{
    /** @return Value[] */
    public function parse(string $data): array;
}
