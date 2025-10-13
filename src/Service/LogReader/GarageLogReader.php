<?php

namespace App\Service\LogReader;

final class GarageLogReader extends LogReaderService
{
    public function getLogFilePath(): string
    {
        return 'var/log/garage_controller.log';
    }
}
