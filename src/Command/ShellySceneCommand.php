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
    description: 'Control shelly switch command',
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
            ->addArgument('scene_id', InputArgument::OPTIONAL, 'Shelly scene id')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $sceneId = $input->getArgument('scene_id') ?: $io->ask('Please type shelly scene id');
        $status  = $this->sceneService->trigger($sceneId);

        dump(json_encode($status) ?? null);

        return Command::SUCCESS;
    }
}
