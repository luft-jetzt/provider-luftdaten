<?php declare(strict_types=1);

namespace App\Provider\Luftdaten;

use App\Provider\AbstractProvider;
use App\Provider\Luftdaten\StationLoader\LuftdatenStationLoader;

class LuftdatenProvider extends AbstractProvider
{
    public function __construct(LuftdatenStationLoader $luftdatenStationLoader)
    {
        $this->stationLoader = $luftdatenStationLoader;
    }

    public function getIdentifier(): string
    {
        return 'ld';
    }
}
