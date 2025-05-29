<?php

namespace App\Service\Gate;

use App\Service\Curl\Supla\SuplaCloudCurlRequest;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\User\UserInterface;

readonly class SuplaGateOpener
{
    private const SLEEP_TIME = 2;
    private ?UserInterface $user;

    public function __construct(
        private SuplaCloudCurlRequest $suplaCurl,
        private LoggerInterface       $gateOpenerLogger,
        private Security              $security,
    ) {
    }

    public function open(): bool
    {
        $this->user = $this->security->getUser();

        if ($this->isOpen())
        {
            $this->gateOpenerLogger->info(
                'Request refused. Gate is already open',
                ['user' => $this->user?->getUserIdentifier()],
            );

            return true;
        }

        $this->tryOpen();

        sleep(self::SLEEP_TIME);

        if ($this->isOpen())
        {
            $this->gateOpenerLogger->info(
                'Gate is opening by 1st request',
                ['user' => $this->user?->getUserIdentifier()],
            );

            return true;
        }

        $this->tryOpen();

        sleep(self::SLEEP_TIME);

        if ($this->isOpen())
        {
            $this->gateOpenerLogger->info(
                'Gate is opening by 2nd request',
                ['user' => $this->user?->getUserIdentifier()],
            );

            return true;
        }

        $this->gateOpenerLogger->error(
            'Error occurred. Cannot open gate',
            ['user' => $this->user?->getUserIdentifier()],
        );

        return false;
    }

    public function sendOpenCloseSimpleRequest(): bool
    {
        $this->user = $this->security->getUser();

        return $this->tryOpen();
    }

    public function read(): array
    {
        return $this->suplaCurl->read();
    }

    private function tryOpen(): bool
    {
        $status = $this->suplaCurl->openClose();

        if (!isset($status['success']) || $status['success'] === false) {
            $this->gateOpenerLogger->error(
                'Couldn\'t open gate',
                ['user' => $this->user?->getUserIdentifier()],
            );

            throw new \Exception('Couldn\'t open gate');
        }

        return true;
    }

    private function isOpen(): bool
    {
        $status = $this->suplaCurl->read();

        if (!isset($status['connected']) || $status['connected'] === false) {
            $this->gateOpenerLogger->error(
                'Couldn\'t connect with Supla',
                ['user' => $this->user?->getUserIdentifier()],
            );

            throw new \Exception('Couldn\'t connect with Supla');
        }

        return !$status['hi'];
    }
}
