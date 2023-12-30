<?php declare(strict_types=1);

namespace App\Command;

use App\SourceFetcher\SourceFetcherInterface;
use Caldera\LuftApiBundle\Api\ValueApiInterface;
use JMS\Serializer\SerializerInterface;
use OldSound\RabbitMqBundle\RabbitMq\ProducerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'luft:fetch',
    description: 'Load data from luftdaten and push into Luft.jetzt api'
)]
class LuftFetchCommand extends Command
{
    public function __construct(protected SerializerInterface $serializer, protected SourceFetcherInterface $sourceFetcher, protected ValueApiInterface $valueApi, protected ProducerInterface $producer)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $valueList = $this->sourceFetcher->fetch();

        $io->success(sprintf('Fetched %d values from Luftdaten', count($valueList)));

        foreach ($valueList as $value) {
            $this->producer->publish($this->serializer->serialize($value, 'json'));

        }

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
