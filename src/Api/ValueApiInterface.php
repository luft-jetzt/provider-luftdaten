<?php declare(strict_types=1);

namespace App\Api;

use App\Model\Value;

interface ValueApiInterface
{
    public function putValue(Value $value): void;
}