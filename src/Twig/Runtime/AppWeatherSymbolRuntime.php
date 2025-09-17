<?php

namespace App\Twig\Runtime;

use App\Model\Weather\SymbolCode;
use Psr\Log\LoggerInterface;
use Twig\Extension\RuntimeExtensionInterface;

readonly class AppWeatherSymbolRuntime implements RuntimeExtensionInterface
{
    public function __construct(
        private LoggerInterface $logger,
    ) {}

    public function getWeatherSymbolImage(string $weatherSymbolCode): string
    {
        try {
            return sprintf('/images/weather/%s.svg', SymbolCode::valueFromName($weatherSymbolCode));
        } catch (\InvalidArgumentException) {
            $this->logger->alert(sprintf('Unknown weather symbol code: "%s"', $weatherSymbolCode));

            return '/images/question_mark.svg';
        }
    }
}
