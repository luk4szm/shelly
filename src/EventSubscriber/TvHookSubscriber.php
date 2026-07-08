<?php

namespace App\EventSubscriber;

use App\Enum\InsolationLevel;
use App\Event\Hook\TvHookEvent;
use App\Model\Device\Light\TvLedsBoard;
use App\Model\Device\Light\TvLedsCabinet;
use App\Model\Device\Light\TvLedsMonitor;
use App\Model\Device\PowerMeter\Tv;
use App\Service\AirQuality\InsolationService;
use App\Service\Shelly\Light\ShellyLightService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Cache\NamespacedPoolInterface;

class TvHookSubscriber implements EventSubscriberInterface
{
    public const TV_ON_CACHE_KEY = 'tv_on';

    public function __construct(
        private readonly ShellyLightService      $shellyLightService,
        private readonly InsolationService       $insolationService,
        private readonly NamespacedPoolInterface $cache,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            TvHookEvent::class => 'onTvPowerChange',
        ];
    }

    public function onTvPowerChange(TvHookEvent $event): void
    {
        $hook = $event->getHook();

        if ($hook->getProperty() !== 'power') {
            return;
        }

        $power = (float)$hook->getValue();

        // When TV is turned ON (power above threshold)
        if ($power >= Tv::BOUNDARY_POWER) {
            $lightsStatusCache = $this->cache->getItem(self::TV_ON_CACHE_KEY);

            // If cache says lights already on, avoid redundant calls
            if ($lightsStatusCache->isHit() && $lightsStatusCache->get() === true) {
                return;
            }

            $this->shellyLightService->turnOn(new TvLedsMonitor(), white: 60);
            sleep(1);
            $this->shellyLightService->turnOn(new TvLedsBoard(), white: 40);
            sleep(1);
            $this->shellyLightService->turnOn(new TvLedsCabinet(), white: 10);

            // Mark in cache that lights are on
            $lightsStatusCache->set(true);
            // no expiration by default; you can set lifetime if desired: $lightsStatusCache->expiresAfter(3600);
            $this->cache->save($lightsStatusCache);

            return;
        }

        // When TV is turned OFF (power below off-threshold)
        if ($power < 7) {
            $lightsStatusCache = $this->cache->getItem(self::TV_ON_CACHE_KEY);

            // If cache doesn't indicate lights are on, avoid redundant off calls
            if (!$lightsStatusCache->isHit() || $lightsStatusCache->get() !== true) {
                return;
            }

            if ($this->insolationService->getActualInsolation() <= InsolationLevel::IndoorLightsOn->value) {
                // Sequence of turning on the mood light
                $this->shellyLightService->turnOn(new TvLedsMonitor(), white: 15);
                sleep(1);
                $this->shellyLightService->turnOn(new TvLedsBoard(), white: 10);
                sleep(1);
                $this->shellyLightService->turnOn(new TvLedsCabinet(), white: 5);
            } else {
                // Turn off lights sequence
                $this->shellyLightService->turnOff(new TvLedsMonitor());
                sleep(1);
                $this->shellyLightService->turnOff(new TvLedsBoard());
                sleep(1);
                $this->shellyLightService->turnOff(new TvLedsCabinet());
            }

            // Remove cache entry
            $this->cache->deleteItem(self::TV_ON_CACHE_KEY);
        }
    }
}
