<?php

namespace App\Controller\Weather;

use App\Repository\AirQualityRepository;
use App\Service\AirQuality\InvalidAirQualityReadingDetector;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/weather/invalid-readings', name: 'app_weather_')]
class InvalidWeatherReadingController extends AbstractController
{
    #[Route('/daily/preview', name: 'daily_invalid_readings_preview', methods: ['POST'])]
    public function previewInvalidReadings(
        Request $request,
        AirQualityRepository $airQualityRepository,
        InvalidAirQualityReadingDetector $invalidReadingDetector,
    ): Response {
        try {
            $payload = $this->decodePayload($request);
            $field = (string) ($payload['field'] ?? '');
            $date = $this->resolveRequestedDate($payload['date'] ?? null);
            $this->guardAgainstFutureDate($date);
            [$selectedFrom, $selectedTo] = $this->resolveSelectedRange($date, $payload['fromTime'] ?? null, $payload['toTime'] ?? null);
            [$dayFrom, $dayTo] = $this->resolveWholeDayRange($date);

            $readings = $airQualityRepository->findForPeriod($dayFrom, $dayTo);
            $metadata = $invalidReadingDetector->getFieldMetadata($field);
            $candidates = $invalidReadingDetector->preview($readings, $field, $selectedFrom, $selectedTo);

            return $this->json([
                'field' => $field,
                'fieldLabel' => $metadata['label'],
                'unit' => $metadata['unit'],
                'date' => $date->format('Y-m-d'),
                'fromTime' => $selectedFrom->format('H:i'),
                'toTime' => $selectedTo->format('H:i'),
                'count' => count($candidates),
                'candidates' => $candidates,
            ]);
        } catch (\InvalidArgumentException $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[Route('/daily/apply', name: 'daily_invalid_readings_apply', methods: ['POST'])]
    public function applyInvalidReadings(
        Request $request,
        AirQualityRepository $airQualityRepository,
        InvalidAirQualityReadingDetector $invalidReadingDetector,
    ): Response {
        try {
            $payload = $this->decodePayload($request);

            if (!$this->isCsrfTokenValid('weather_invalid_readings', (string) ($payload['_token'] ?? ''))) {
                return $this->json(['error' => 'Token CSRF jest nieprawidłowy.'], Response::HTTP_FORBIDDEN);
            }

            $field = (string) ($payload['field'] ?? '');
            $date = $this->resolveRequestedDate($payload['date'] ?? null);
            $this->guardAgainstFutureDate($date);
            [$selectedFrom, $selectedTo] = $this->resolveSelectedRange($date, $payload['fromTime'] ?? null, $payload['toTime'] ?? null);
            [$dayFrom, $dayTo] = $this->resolveWholeDayRange($date);
            $ids = $this->normalizeIds($payload['ids'] ?? null);

            if ($ids === []) {
                throw new \InvalidArgumentException('Brak odczytów do zatwierdzenia.');
            }

            $readings = $airQualityRepository->findForPeriod($dayFrom, $dayTo);
            $preview = $invalidReadingDetector->preview($readings, $field, $selectedFrom, $selectedTo);
            $allowedIds = [];

            foreach ($preview as $candidate) {
                if (($candidate['id'] ?? null) !== null) {
                    $allowedIds[(int) $candidate['id']] = true;
                }
            }

            $readingsById = [];
            foreach ($readings as $reading) {
                if ($reading->getId() !== null) {
                    $readingsById[$reading->getId()] = $reading;
                }
            }

            $updated = [];
            foreach ($ids as $id) {
                if (!isset($allowedIds[$id], $readingsById[$id])) {
                    continue;
                }

                $invalidReadingDetector->nullifyField($readingsById[$id], $field);
                $updated[$id] = $readingsById[$id];
            }

            if ($updated === []) {
                throw new \InvalidArgumentException('Nie znaleziono odczytów, które można zmienić na null.');
            }

            $airQualityRepository->save(array_values($updated));

            return $this->json([
                'updated' => count($updated),
                'date' => $date->format('Y-m-d'),
            ]);
        } catch (\InvalidArgumentException $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    private function decodePayload(Request $request): array
    {
        $content = trim($request->getContent());

        if ($content === '') {
            return [];
        }

        $payload = json_decode($content, true);

        if (!is_array($payload)) {
            throw new \InvalidArgumentException('Nie udało się odczytać danych żądania.');
        }

        return $payload;
    }

    private function resolveRequestedDate(mixed $dateValue): \DateTimeImmutable
    {
        if (is_string($dateValue) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateValue)) {
            return new \DateTimeImmutable($dateValue);
        }

        return new \DateTimeImmutable('today');
    }

    private function guardAgainstFutureDate(\DateTimeImmutable $date): void
    {
        if ($date > new \DateTimeImmutable('today')) {
            throw new \InvalidArgumentException('Nie można czyścić odczytów dla przyszłych dat.');
        }
    }

    private function resolveWholeDayRange(\DateTimeImmutable $date): array
    {
        return [
            $date->setTime(0, 0, 0),
            $date->setTime(23, 59, 59),
        ];
    }

    private function resolveSelectedRange(\DateTimeImmutable $date, mixed $fromTime, mixed $toTime): array
    {
        if ($fromTime !== null && $fromTime !== '' && (!is_string($fromTime) || !preg_match('/^\d{2}:\d{2}$/', $fromTime))) {
            throw new \InvalidArgumentException('Nieprawidłowa godzina początkowa.');
        }

        if ($toTime !== null && $toTime !== '' && (!is_string($toTime) || !preg_match('/^\d{2}:\d{2}$/', $toTime))) {
            throw new \InvalidArgumentException('Nieprawidłowa godzina końcowa.');
        }

        $from = is_string($fromTime) && $fromTime !== ''
            ? new \DateTimeImmutable($date->format('Y-m-d') . ' ' . $fromTime . ':00')
            : $date->setTime(0, 0, 0);

        $to = is_string($toTime) && $toTime !== ''
            ? new \DateTimeImmutable($date->format('Y-m-d') . ' ' . $toTime . ':59')
            : $date->setTime(23, 59, 59);

        if ($from > $to) {
            throw new \InvalidArgumentException('Godzina początkowa nie może być późniejsza niż końcowa.');
        }

        return [$from, $to];
    }

    /**
     * @return int[]
     */
    private function normalizeIds(mixed $rawIds): array
    {
        if (!is_array($rawIds)) {
            return [];
        }

        $ids = [];
        foreach ($rawIds as $rawId) {
            if (is_int($rawId) || (is_string($rawId) && ctype_digit($rawId))) {
                $ids[(int) $rawId] = (int) $rawId;
            }
        }

        return array_values($ids);
    }
}
