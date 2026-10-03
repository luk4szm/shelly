<?php

declare(strict_types=1);

namespace App\Command\ShellyRpc;

use App\Enum\ShellyComponentType;
use App\Exception\ShellyRpcException;
use App\Model\Device\Light\LocalWhiteLightDevice;
use App\Model\Device\Light\LocalRgbwLightDevice;
use App\Model\Device\Valve\ValveDevice;
use App\Model\Device\Cover\RollerCover;
use App\Service\Shelly\Local\ShellyDeviceRegistry;
use App\Service\Shelly\Local\ShellyLedStatusReader;
use App\Service\Shelly\Local\ShellyRgbwStatusReader;
use App\Service\Shelly\Local\ShellyValveStatusReader;
use App\Service\Shelly\Local\ShellySwitchStatusReader;
use App\Service\Shelly\Local\ShellyCoverStatusReader;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:shelly:rpc:status',
    description: 'Read the status of a Shelly component over the local RPC API',
)]
final class ShellyRpcStatusCommand extends Command
{
    public function __construct(
        private readonly ShellyDeviceRegistry     $registry,
        private readonly ShellySwitchStatusReader $statusReader,
        private readonly ShellyValveStatusReader  $valveStatusReader,
        private readonly ShellyLedStatusReader    $ledStatusReader,
        private readonly ShellyRgbwStatusReader   $rgbwStatusReader,
        private readonly ShellyCoverStatusReader  $coverStatusReader,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('device', InputArgument::REQUIRED, 'Logical device name, e.g. girlanda');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io         = new SymfonyStyle($input, $output);
        $deviceName = (string)$input->getArgument('device');

        try {
            $device = $this->registry->getDevice($deviceName);
            $status = match (true) {
                $device instanceof ValveDevice => $this->valveStatusReader->read($deviceName),
                $device instanceof LocalWhiteLightDevice => $this->ledStatusReader->read($deviceName),
                $device instanceof LocalRgbwLightDevice => $this->rgbwStatusReader->read($deviceName),
                $device instanceof RollerCover => $this->coverStatusReader->read($deviceName),
                $device->getComponentType() === ShellyComponentType::Switch => $this->statusReader->read($deviceName)->toArray(),
                default => throw new ShellyRpcException(sprintf('Status is unsupported for "%s".', $deviceName)),
            };
        } catch (\InvalidArgumentException $exception) {
            $io->error($exception->getMessage());
            $io->writeln(sprintf('Available devices: %s', implode(', ', $this->registry->getDeviceNames())));

            return self::INVALID;
        } catch (ShellyRpcException $exception) {
            $io->error($exception->getMessage());

            return self::FAILURE;
        }

        $output->writeln(json_encode(
            $status,
            JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES,
        ));

        return self::SUCCESS;
    }
}
