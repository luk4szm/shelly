<?php

namespace App\Service\Shelly\Cover;

use App\Model\Controller\Cover;
use App\Service\Curl\Shelly\ShellyCloudCurlRequest;
use App\Service\Shelly\ShellyDeviceService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;

readonly class ShellyCoverService extends ShellyDeviceService
{
    public function __construct(
        ShellyCloudCurlRequest  $curlRequest,
        private LoggerInterface $coverControllerLogger,
        private Security        $security,
    )
    {
        parent::__construct($curlRequest);
    }

    public function open(): array
    {
        $this->coverControllerLogger->info(
            'Covers have been opened',
            [
                'user'   => $this->security->getUser()?->getUserIdentifier(),
                'device' => 'app',
            ],
        );

        $this->curlRequest->cover(Cover::DEVICE_ID, 'open');

        sleep(25);

        return $this->curlRequest->cover(Cover::DEVICE_ID, 'open');
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

        return $this->curlRequest->cover(Cover::DEVICE_ID, 'close');
    }

    public function stop(): array
    {
        return $this->curlRequest->cover(Cover::DEVICE_ID, 'stop');
    }

    public function getLastDirection(): ?string
    {
        $status = $this->getStatus(Cover::DEVICE_ID);

        try {
            return $status[0]['status']['cover:0']['last_direction'];
        } catch (\Exception) {
            return null;
        }
    }
}
