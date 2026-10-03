<?php

namespace App\Service\Shelly\Cover;

use App\Model\Device\Cover\RollerCover;
use App\Service\Shelly\Local\ShellyCoverStatusReader;
use App\Service\Shelly\Local\ShellyCoverWriter;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;

readonly class ShellyCoverService
{
    public function __construct(
        private ShellyCoverWriter       $coverWriter,
        private ShellyCoverStatusReader $statusReader,
        private LoggerInterface         $coverControllerLogger,
        private Security                $security,
    ) {}

    public function open(): array
    {
        $this->coverControllerLogger->info(
            'Covers have been opened',
            [
                'user'   => $this->security->getUser()?->getUserIdentifier(),
                'device' => 'app',
            ],
        );

        // The old cloud flow repeated Open after 25 seconds because the motors were too weak.
        // The replacement motors are expected to fully open with a single command.
        return $this->coverWriter->set(RollerCover::NAME, 'open')->data;
    }

    public function close(): array
    {
        $this->coverControllerLogger->info(
            'Covers have been closed',
            [
                'user'   => $this->security->getUser()?->getUserIdentifier(),
                'device' => 'app',
            ],
        );

        return $this->coverWriter->set(RollerCover::NAME, 'close')->data;
    }

    public function stop(): array
    {
        return $this->coverWriter->set(RollerCover::NAME, 'stop')->data;
    }

    /** @return array<string, mixed> */
    public function getStatus(): array
    {
        return $this->statusReader->read(RollerCover::NAME);
    }

    /**
     * @deprecated - use getStatus() instead
     * @return string|null
     */
    public function getLastDirection(): ?string
    {
        return $this->getStatus()['last_direction'];
    }
}
