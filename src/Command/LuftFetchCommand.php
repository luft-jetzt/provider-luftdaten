<?php declare(strict_types=1);

namespace App\Command;

use App\Api\ValueApiInterface;
use App\SourceFetcher\SourceFetcherInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class LuftFetchCommand extends Command
{
    protected static $defaultName = 'luft:fetch';

    protected SourceFetcherInterface $sourceFetcher;

    protected ValueApiInterface $valueApi;

    public function __construct(string $name = null, SourceFetcherInterface $sourceFetcher, ValueApiInterface $valueApi)
    {
        $this->sourceFetcher = $sourceFetcher;
        $this->valueApi = $valueApi;

        parent::__construct($name);
    }

    protected function configure()
    {
        $this
            ->setDescription('Add a short description for your command')
            ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $valueList = $this->sourceFetcher->fetch();

        $this->valueApi->putValues($valueList);

        return Command::SUCCESS;
    }
}
