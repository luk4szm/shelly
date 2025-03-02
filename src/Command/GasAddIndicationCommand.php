<?php

namespace App\Command;

use App\Entity\GasMeter;
use App\Repository\GasMeterRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:gas:add-indication',
    description: 'Dodaj wskazanie licznika gazu',
)]
class GasAddIndicationCommand extends Command
{
    public function __construct(private readonly GasMeterRepository $repository)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('indication', InputArgument::OPTIONAL, 'Gas meter indication');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $previousIndication = $this->repository->findLast();

        $indication = $input->getArgument('indication')
            ?: $io->ask(
                sprintf('Please provide the gas meter indication [previous: %.3f]', $previousIndication->getIndication()),
                null,
                function (string $indication) use ($previousIndication): float {
                    if (!is_numeric($indication = str_replace(',', '.', $indication))) {
                        throw new \RuntimeException('You must type a number.');
                    }

                    if ($previousIndication->getIndication() > $indication) {
                        throw new \RuntimeException('The current indication cannot be smaller than the previous one!');
                    }

                    return (float)$indication;
                }
            );

        $gasMeter = new GasMeter($indication);

        $this->repository->save($gasMeter);

        $io->success('Indication was saved');

        return Command::SUCCESS;
    }
}
