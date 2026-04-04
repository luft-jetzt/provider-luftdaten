<?php

declare(strict_types=1);

namespace App\StationLoader;

use Caldera\LuftModel\Model\Station;

class StationLoaderResult
{
    /** @var array<string, Station> */
    protected array $existingStationList = [];

    /** @var array<string, Station> */
    protected array $newStationList = [];

    /** @var array<string, Station> */
    protected array $changedStationList = [];

    /** @param array<string, Station> $existingStationList */
    public function setExistingStationList(array $existingStationList): self
    {
        $this->existingStationList = $existingStationList;

        return $this;
    }

    /** @return array<string, Station> */
    public function getExistingStationList(): array
    {
        return $this->existingStationList;
    }

    public function addNewStation(Station $station): self
    {
        $this->newStationList[$station->getStationCode()] = $station;

        return $this;
    }

    /** @return array<string, Station> */
    public function getNewStationList(): array
    {
        return $this->newStationList;
    }

    public function addChangedStation(Station $station): self
    {
        $this->changedStationList[$station->getStationCode()] = $station;

        return $this;
    }

    /** @return array<string, Station> */
    public function getChangedStationList(): array
    {
        return $this->changedStationList;
    }
}
