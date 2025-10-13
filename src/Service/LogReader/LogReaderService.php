<?php

namespace App\Service\LogReader;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Filesystem;

abstract class LogReaderService
{
    private string $projectDir;

    public function __construct(ParameterBagInterface $parameterBag)
    {
        $this->projectDir = $parameterBag->get('kernel.project_dir');
    }

    /**
     * Zwraca ostatnie N linii z pliku cover_controller.log.
     *
     * @param int $linesCount Liczba linii do zwrócenia (domyślnie 10).
     * @return array<int, string> Tablica zawierająca ostatnie linie loga.
     */
    public function getLastLogLines(int $linesCount = 10): array
    {
        $fullPath = $this->projectDir . '/' . $this->getLogFilePath();

        // 1. Check if the file exists
        $filesystem = new Filesystem();
        if (!$filesystem->exists($fullPath)) {
            // Throwing an exception if the file is missing
            throw new FileNotFoundException(sprintf('Plik loga nie został znaleziony: "%s"', $fullPath));
        }

        // 2. Loading the entire content of the file (for small and medium-sized files this is acceptable)
        // For very large files (GB) it is better to use an iterator or fseek/fread.
        $content = file_get_contents($fullPath);

        if ($content === false) {
             // Returning an empty array in case of a read error (e.g. lack of permissions)
             return [];
        }

        // 3. Separating the content into lines
        // The FILE_IGNORE_NEW_LINES flag removes a newline character from the end of each line
        $lines = file($fullPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if ($lines === false) {
            // Return in case of reading error
            return [];
        }

        // 4. Fetch the last n lines
        // array_slice(array, offset, length, preserve_keys)
        // A negative offset (-linesCount) means fetching from the end.
        return array_reverse(array_slice($lines, -$linesCount));
    }

    /**
     * Path to controller log file.
     *
     * @return string
     */
    abstract protected function getLogFilePath(): string;
}
