<?php

namespace App\Service\Shelly\Cover;

use App\Model\Controller\Garage;
use App\Service\Curl\Shelly\ShellyCloudCurlRequest;
use App\Service\Shelly\ShellyDeviceService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;

readonly class ShellyGarageService extends ShellyDeviceService
{
    public function __construct(
        ShellyCloudCurlRequest  $curlRequest,
        private LoggerInterface $garageControllerLogger,
        private Security        $security,
    ) {
        parent::__construct($curlRequest);
    }

    public function move(): array
    {
        $this->garageControllerLogger->info(
            'The garage roller blind changed its position',
            [
                'user'   => $this->security->getUser()?->getUserIdentifier(),
                'device' => 'app',
            ],
        );

        return $this->curlRequest->switch(Garage::DEVICE_ID, 0, 'on');
    }

    public function isOpen(): ?bool
    {
        $status = $this->getStatus(Garage::DEVICE_ID);

        try {
            return $status[0]['status']['input:100']['state'];
        } catch (\Exception $exception) {
            return null;
        }
    }
}
