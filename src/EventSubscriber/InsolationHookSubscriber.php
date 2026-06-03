<?php

namespace App\EventSubscriber;

use App\Enum\DaylightMode;
use App\Enum\InsolationLevel;
use App\Event\Hook\InsolationHookEvent;
use App\Model\Device\Garland;
use App\Model\Device\KitchenLedsBottom;
use App\Model\Device\KitchenLedsTop;
use App\Model\Device\TvLedsBoard;
use App\Model\Device\TvLedsCabinet;
use App\Model\Device\TvLedsMonitor;
use App\Model\Scene\TurnOffLightsScene;
use App\Repository\ConfigRepository;
use App\Service\Shelly\Light\ShellyLightService;
use App\Service\Shelly\Scene\ShellySceneService;
use App\Service\Shelly\Switch\ShellySwitchService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Cache\NamespacedPoolInterface;

readonly class InsolationHookSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private ConfigRepository        $configRepository,
        private ShellyLightService      $shellyLightService,
        private ShellySwitchService     $shellySwitchService,
        private ShellySceneService      $shellySceneService,
        private NamespacedPoolInterface $cache,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            InsolationHookEvent::class => 'onInsolationChange',
        ];
    }

    public function onInsolationChange(InsolationHookEvent $event): void
    {
        $insolation = $event->getInsolation();
        $config     = $this->configRepository->getAllValues();

        // When we are at home, it starts to turn gray outside, and we have the automatic lights on
        if (
            $insolation < InsolationLevel::IndoorLightsOn->value
            && $config['daylight_mode'] === DaylightMode::Day->value
        ) {
            if (
                $config['occupancy_mode'] === 'home'
                && $config['auto_light_inside'] === '1'
            ) {
                $tvLightsStatusCache = $this->cache->getItem(TvHookSubscriber::TV_ON_CACHE_KEY);

                if (!$tvLightsStatusCache->isHit() || $tvLightsStatusCache->get() !== true) {
                    $this->shellyLightService->turnOn(new TvLedsMonitor(), white: 15);
                    sleep(1);
                    $this->shellyLightService->turnOn(new TvLedsBoard(), white: 10);
                    sleep(1);
                    $this->shellyLightService->turnOn(new TvLedsCabinet(), white: 5);
                    sleep(1);
                }

                $this->shellyLightService->turnOn(new KitchenLedsTop(), white: 65);
                sleep(1);
                $this->shellyLightService->turnOn(new KitchenLedsBottom(), white: 10);
            }

            $this->configRepository->updateValueByName('daylight_mode', DaylightMode::Twilight);

            return;
        }

        // When we are at home, it is dark outside and we have the automatic lights on
        if (
            $insolation < InsolationLevel::OutdoorLightsOn->value
            && $config['daylight_mode'] !== DaylightMode::Night
        ) {
            if (
                $config['occupancy_mode'] === 'home'
                && $config['auto_light_outside'] === '1'
            ) {
                $this->shellySwitchService->switch(Garland::DEVICE_ID, Garland::CHANNEL, 'on');
            }

            $this->configRepository->updateValueByName('daylight_mode', DaylightMode::Night);

            return;
        }

        // When we are at home, it is dark outside and we have the automatic lights on
        if (
            $insolation > InsolationLevel::OutdoorLightsOff->value
            && $config['daylight_mode'] === DaylightMode::Night
        ) {
            $this->shellySwitchService->switch(Garland::DEVICE_ID, Garland::CHANNEL, 'off');
            $this->configRepository->updateValueByName('daylight_mode', DaylightMode::Twilight);

            return;
        }

        // When we are at home, it is dark outside and we have the automatic lights on
        if (
            $insolation > InsolationLevel::IndoorLightsOff->value
            && $config['daylight_mode'] !== DaylightMode::Day
        ) {
            $this->shellySceneService->trigger(TurnOffLightsScene::ID); // turn off lights

            $this->configRepository->updateValueByName('daylight_mode', DaylightMode::Day);

            return;
        }
    }
}
