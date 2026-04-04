<?php

declare(strict_types=1);

namespace App\Command;

use App\StationLoader\StationLoaderInterface;
use Caldera\LuftApiBundle\Api\StationApiInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'luft:load-stations',
    description: 'Load station list from sensor.community and push into Luft.jetzt api'
)]
class StationLoadCommand extends Command
{
    private const int BATCH_SIZE = 1000;

    public function __construct(protected StationLoaderInterface $stationLoader, protected StationApiInterface $stationApi)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $result = $this->stationLoader->load();

        $io->success(sprintf(
            'Found %d existing stations, %d new stations and %d changed stations',
            count($result->getExistingStationList()),
            count($result->getNewStationList()),
            count($result->getChangedStationList()),
        ));

        if (count($result->getNewStationList()) > 0) {
            $batches = array_chunk(array_values($result->getNewStationList()), self::BATCH_SIZE);

            $io->progressStart(count($batches));

            foreach ($batches as $batch) {
                $this->stationApi->putStations($batch);
                $io->progressAdvance();
            }

            $io->progressFinish();
        }

        if (count($result->getChangedStationList()) > 0) {
            $this->stationApi->postStations($result->getChangedStationList());
        }

        $io->success(sprintf(
            'Sent %d new and %d changed stations to Luft api',
            count($result->getNewStationList()),
            count($result->getChangedStationList()),
        ));

        return Command::SUCCESS;
    }
}
