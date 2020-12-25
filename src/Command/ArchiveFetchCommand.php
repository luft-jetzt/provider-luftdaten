<?php declare(strict_types=1);

namespace App\Command;

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

    protected ValueApiInterface $valueApi;

    public function __construct(string $name = null, ArchiveFetcherInterface $archiveFetcher, ValueApiInterface $valueApi)
    {
        $this->archiveFetcher = $archiveFetcher;
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

        $valueList = $this->archiveFetcher->fetch($fromDateTime, $untilDateTime);

        $io->success(sprintf('Fetched %d values from Luftdaten', count($valueList)));

        $this->valueApi->putValues($valueList);

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

        $io->success(sprintf('Send %d values to Luft api', count($valueList)));

        return Command::SUCCESS;
    }
}
