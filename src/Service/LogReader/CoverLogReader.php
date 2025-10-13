<?php

namespace App\Service\LogReader;

final class CoverLogReader extends LogReaderService
{
    public function getLogFilePath(): string
    {
        return 'var/log/cover_controller.log';
    }
}
