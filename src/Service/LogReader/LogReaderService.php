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

    public function getParsedLogs(int $linesCount = 10): array
    {
        foreach ($this->getLastLogLines($linesCount) as $line) {
            $parsedLogs[] = $this->parseLogLine($line);
        }

        return $parsedLogs ?? [];
    }

    /**
     * Parses a single log line using regex and returns an array with extracted data.
     *
     * @param string $logLine The log line to parse, e.g., "[2025-10-17T18:14:37.734881+02:00] garage_controller.INFO: The button was clicked {"device":"switch"} []"
     * @return array<string, mixed> Array containing 'timestamp', 'controller_info', 'message', and 'data'.
     * @throws \Exception If the log format doesn't match the regex pattern.
     * @throws \JsonException If JSON decoding fails.
     */
    public function parseLogLine(string $logLine): array
    {
        // Regex pattern breakdown:
        // 1. \[ (.*?) \]  - Group 1: The timestamp within square brackets.
        // 2. \s (.*?):    - Group 2: Controller/level info after a space, ending with a colon.
        // 3. \s (.*)      - Group 3: The rest of the line (message, JSON data, and trailing '[]').
        $pattern = '/^\[(.*?)\]\s(.*?):\s(.*)$/';

        if (preg_match($pattern, $logLine, $matches) !== 1) {
            throw new \Exception("Log line does not match the expected format.");
        }

        // --- 1. Extract Main Components ---
        $timestampString = $matches[1]; // e.g., "2025-10-17T18:14:37.734881+02:00"
        $controllerInfo  = $matches[2]; // e.g., "garage_controller.INFO"
        $messageAndJson  = $matches[3]; // e.g., "The button was clicked {"device":"switch"} []"

        // --- 2. Separate Message from JSON Data ---
        $jsonData = '[]'; // Default to an empty JSON array

        // Find the position of the first curly brace, which marks the start of the JSON data.
        $jsonStartPos = strpos($messageAndJson, '{');

        if ($jsonStartPos !== false) {
            // Message is the part before the JSON start position
            $message = trim(substr($messageAndJson, 0, $jsonStartPos));

            // JSON string starts from here. It includes the actual JSON and the trailing ' []'
            $jsonStringWithBrackets = substr($messageAndJson, $jsonStartPos);

            // Remove the trailing, often empty, context array ' []' (Symfony Monolog context)
            $jsonData = trim(str_replace(' []', '', $jsonStringWithBrackets));

            if ($jsonData === '') {
                 $jsonData = '[]';
            }

        } else {
             // If no JSON data is present, the whole rest is the message (including the final ' []')
             $message = trim(str_replace(' []', '', $messageAndJson));
        }

        // --- 3. Type Casting and Final Conversion ---
        // Convert the timestamp string into a DateTimeImmutable object
        $timestamp = new \DateTimeImmutable($timestampString);

        // Decode the JSON string into an associative PHP array
        /** @var array<string, mixed> $decodedData */
        $decodedData = json_decode($jsonData, true, 512, JSON_THROW_ON_ERROR);

        return [
            'timestamp'       => $timestamp,
            'controller_info' => $controllerInfo,
            'message'         => $message,
            'data'            => $decodedData,
        ];
    }

    /**
     * Zwraca ostatnie N linii z pliku cover_controller.log.
     *
     * @param int $linesCount Liczba linii do zwrócenia (domyślnie 10).
     * @return array<int, string> Tablica zawierająca ostatnie linie loga.
     */
    private function getLastLogLines(int $linesCount = 10): array
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
