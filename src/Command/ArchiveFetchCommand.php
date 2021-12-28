<?php declare(strict_types=1);

namespace App\Command;

use App\ArchiveFetcher\ArchiveDataLoaderInterface;
use App\ArchiveFetcher\ArchiveFetcherInterface;
use App\SourceFetcher\SourceFetcherInterface;
use Caldera\LuftApiBundle\Api\ValueApiInterface;
use Caldera\LuftApiBundle\Model\Value;
use Carbon\Carbon;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ArchiveFetchCommand extends Command
{
    protected static $defaultName = 'luft:archive';

    protected ArchiveFetcherInterface $archiveFetcher;
    protected ArchiveDataLoaderInterface $archiveDataLoader;
    protected ValueApiInterface $valueApi;

    public function __construct(string $name = null, ArchiveFetcherInterface $archiveFetcher, ValueApiInterface $valueApi, ArchiveDataLoaderInterface $archiveDataLoader)
    {
        $this->archiveFetcher = $archiveFetcher;
        $this->archiveDataLoader = $archiveDataLoader;
        $this->valueApi = $valueApi;

        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this->setDescription('Load archive data from luftdaten and push into Luft.jetzt api')
            ->addArgument('from-date-time', InputArgument::REQUIRED)
            ->addArgument('until-date-time', InputArgument::REQUIRED)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $fromDateTime = new Carbon($input->getArgument('from-date-time'));
        $untilDateTime = new Carbon($input->getArgument('until-date-time'));

        $filenameList = $this->archiveDataLoader->load($fromDateTime, $untilDateTime);

        $io->success(sprintf('Found %d files from %s until %s', count($filenameList), $fromDateTime->format('Y-m-d'), $untilDateTime->format('Y-m-d')));
        $totalValueCount = 0;

        $io->progressStart(count($filenameList));

        foreach ($filenameList as $filename) {
            $valueList = $this->archiveFetcher->fetch($filename, $fromDateTime, $untilDateTime);

            if ($output->isVerbose()) {
                $io->table(['StationCode', 'DateTime', 'Value', 'Pollutant'], array_map(function (Value $value) {
                    return [
                        $value->getStationCode(),
                        $value->getDateTime()->format('Y-m-d H:i:s'),
                        $value->getValue(),
                        $value->getPollutant()
                    ];
                }, $valueList));
            }

            //$this->valueApi->putValues($valueList);
            $totalValueCount += count($valueList);
            $io->progressAdvance();
        }

        $io->success(sprintf('Send %d values to Luft api', $totalValueCount));
        $io->progressFinish();

        return Command::SUCCESS;
    }
}
