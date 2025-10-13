<?php

namespace App\Service\LogReader;

final class GateLogReader extends LogReaderService
{
    public function getLogFilePath(): string
    {
        return 'var/log/gate_opener.log';
    }
}
