<?php

namespace App\Service\Gate;

use App\Service\Curl\Supla\SuplaCloudCurlRequest;

readonly class SuplaGateOpener
{
    public function __construct(
        private SuplaCloudCurlRequest $suplaCurl,
    ) {
    }

    public function open(): bool
    {
        if ($this->isOpen())
        {
//            dump('gate is already open');
            return true;
        }

//        dump('sending open 1nd open request');
        $this->suplaCurl->openClose();

        sleep(2);

        if ($this->isOpen())
        {
//            dump('gate is opening by 1st request');
            return true;
        }

//        dump('sending open 2nd open request');
        $this->suplaCurl->openClose();

        sleep(2);

        if ($this->isOpen())
        {
//            dump('gate is opening by 2nd request');
            return true;
        }

        return $this->isOpen();
    }

    private function isOpen(): bool
    {
        $status = $this->suplaCurl->read();

        return !$status['hi'];
    }
}
