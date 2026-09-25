<?php

declare(strict_types=1);

namespace App\Command\ShellyRpc;

use App\Exception\ShellyRpcException;
use App\Service\Shelly\Local\ShellyDeviceRegistry;
use App\Service\Shelly\Local\ShellySwitchWriter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:shelly:rpc:switch',
    description: 'Set a Shelly switch through its local RPC API',
)]
final class ShellyRpcSwitchCommand extends Command
{
    public function __construct(
        private readonly ShellyDeviceRegistry $registry,
        private readonly ShellySwitchWriter $switchWriter,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('device', InputArgument::REQUIRED, 'Logical device name, e.g. girlanda')
            ->addArgument('action', InputArgument::REQUIRED, 'Desired state: on or off');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $deviceName = (string) $input->getArgument('device');
        $action = (string) $input->getArgument('action');

        if (!in_array($action, ['on', 'off'], true)) {
            $io->error('Action must be "on" or "off".');

            return self::INVALID;
        }

        try {
            $device = $this->registry->getDevice($deviceName);
            $result = $this->switchWriter->set($deviceName, $action === 'on');
        } catch (\InvalidArgumentException $exception) {
            $io->error($exception->getMessage());
            $io->writeln(sprintf('Available devices: %s', implode(', ', $this->registry->getDeviceNames())));

            return self::INVALID;
        } catch (ShellyRpcException $exception) {
            $io->error($exception->getMessage());

            return self::FAILURE;
        }

        $output->writeln(json_encode([
            'device'           => $device->getName(),
            'component'        => sprintf('%s:%d', $device->getComponentType()->value, $device->getChannel()),
            'action'           => $action,
            'endpoint'         => $result->endpoint,
            'connection'       => $result->connection,
            'response_time_ms' => $result->responseTimeMs,
            'total_time_ms'    => $result->totalTimeMs,
            'rpc_result'       => $result->data,
        ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return self::SUCCESS;
    }
}
