<?php

namespace App\EventSubscriber;

use App\Event\Hook\TvHookEvent;
use App\Model\Device\Tv;
use App\Model\Device\TvLedsBoard;
use App\Model\Device\TvLedsCabinet;
use App\Model\Device\TvLedsMonitor;
use App\Service\Shelly\Light\ShellyLightService;
use Psr\Cache\CacheItemInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Cache\NamespacedPoolInterface;

class TvHookSubscriber implements EventSubscriberInterface
{
    private const TV_ON_CACHE_KEY = 'tv_on';

    public function __construct(
        private readonly ShellyLightService      $shellyLightService,
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

            $this->shellyLightService->turnOn(TvLedsMonitor::DEVICE_ID, TvLedsMonitor::CHANNEL, 60);
            sleep(1);
            $this->shellyLightService->turnOn(TvLedsBoard::DEVICE_ID, TvLedsBoard::CHANNEL, 40);
            sleep(1);
            $this->shellyLightService->turnOn(TvLedsCabinet::DEVICE_ID, TvLedsCabinet::CHANNEL, 10);

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

            // Turn off lights sequence
            $this->shellyLightService->turnOff(TvLedsMonitor::DEVICE_ID, TvLedsMonitor::CHANNEL);
            sleep(1);
            $this->shellyLightService->turnOff(TvLedsBoard::DEVICE_ID, TvLedsBoard::CHANNEL);
            sleep(1);
            $this->shellyLightService->turnOff(TvLedsCabinet::DEVICE_ID, TvLedsCabinet::CHANNEL);

            // Remove cache entry
            $this->cache->deleteItem(self::TV_ON_CACHE_KEY);
        }
    }
}
