<?php declare(strict_types=1);

namespace App\Parser;

interface JsonParserInterface
{
    public function parse(array $data): array;
}
