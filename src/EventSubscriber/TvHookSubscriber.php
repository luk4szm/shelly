<?php

namespace App\EventSubscriber;

use App\Event\Hook\TvHookEvent;
use App\Model\Device\TvLedsBoard;
use App\Model\Device\TvLedsCabinet;
use App\Model\Device\TvLedsMonitor;
use App\Service\Shelly\Light\ShellyLightService;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Cache\ItemInterface;

class TvHookSubscriber implements EventSubscriberInterface
{
    private const TV_ON_CACHE_KEY = 'tv_on';

    public function __construct(
        private readonly ShellyLightService $shellyLightService
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
        $cache = new FilesystemAdapter();

        if ($power > 15) {
            $cache->get(self::TV_ON_CACHE_KEY, function (ItemInterface $item) {
                $item->expiresAfter(86400);
            });

            $this->shellyLightService->turnOn(TvLedsMonitor::DEVICE_ID, TvLedsMonitor::CHANNEL, 60);
            sleep(1);
            $this->shellyLightService->turnOn(TvLedsBoard::DEVICE_ID, TvLedsBoard::CHANNEL, 40);
            sleep(1);
            $this->shellyLightService->turnOn(TvLedsCabinet::DEVICE_ID, TvLedsCabinet::CHANNEL, 10);

            return;
        }

        if ($power < 4) {
            if ($cache->getItem(self::TV_ON_CACHE_KEY)->isHit()) {
                $cache->deleteItem(self::TV_ON_CACHE_KEY);

                $this->shellyLightService->turnOff(TvLedsMonitor::DEVICE_ID, TvLedsMonitor::CHANNEL);
                sleep(1);
                $this->shellyLightService->turnOff(TvLedsBoard::DEVICE_ID, TvLedsBoard::CHANNEL);
                sleep(1);
                $this->shellyLightService->turnOff(TvLedsCabinet::DEVICE_ID, TvLedsCabinet::CHANNEL);
            }
        }
    }
}
