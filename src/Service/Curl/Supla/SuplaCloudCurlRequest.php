<?php

namespace App\Service\Curl\Supla;

use App\Service\Curl\Curl;

class SuplaCloudCurlRequest extends Curl
{
    private const URL    = 'https://svr133.supla.org/direct/189';
    private const METHOD = 'PATCH';

    public function read(): array
    {
        return $this->request(self::METHOD, self::URL, body: [
            'code'   => $_ENV['GATE_DIRECT_LINK_CODE'],
            'action' => 'read',
        ]);
    }

    public function openClose(): array
    {
        return $this->request(self::METHOD, self::URL, body: [
            'code'   => $_ENV['GATE_DIRECT_LINK_CODE'],
            'action' => 'open-close',
        ]);
    }
}
