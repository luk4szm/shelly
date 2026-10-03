<?php

namespace App\Command;

use App\Service\Shelly\Scene\ShellySceneService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:scene',
    description: 'Run a local Shelly lighting scene',
)]
class ShellySceneCommand extends Command
{
    public function __construct(
        private readonly ShellySceneService $sceneService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('scene_id', InputArgument::OPTIONAL, 'Scene id')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $sceneId = $input->getArgument('scene_id') ?: $io->ask('Please type scene id');
        try {
            $result = $this->sceneService->trigger((string) $sceneId);
        } catch (\InvalidArgumentException $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        $output->writeln(json_encode($result->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

        return $result->isSuccessful() ? Command::SUCCESS : Command::FAILURE;
    }
}
