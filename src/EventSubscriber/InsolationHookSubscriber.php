<?php

namespace App\EventSubscriber;

use App\Enum\DaylightMode;
use App\Enum\InsolationLevel;
use App\Event\Hook\InsolationHookEvent;
use App\Model\Device\Light\KitchenLedsBottom;
use App\Model\Device\Light\KitchenLedsTop;
use App\Model\Device\Light\TvLedsBoard;
use App\Model\Device\Light\TvLedsCabinet;
use App\Model\Device\Light\TvLedsMonitor;
use App\Model\Device\Relay\Garland;
use App\Model\Scene\TurnOffKitchenLightsScene;
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

        /**
         * Przejście z dnia w tryb zmierzchu
         * Kiedy jesteśmy w domu i automatyczne światła są włączone - zapalamy światła w kuchni i przy TV
         * Jeśli tv jest włączony zapalamy tylko te w kuchni
         */
        if (
            $insolation < InsolationLevel::IndoorLightsOn->value
            && $config['daylight_mode'] === DaylightMode::Day->value
        ) {
            if (
                $config['occupancy_mode'] === 'home'
                && $config['auto_light_inside'] === '1'
            ) {
                $tvLightsStatusCache = $this->cache->getItem(TvHookSubscriber::TV_ON_CACHE_KEY);

                if (!$tvLightsStatusCache->isHit() && $tvLightsStatusCache->get() !== true) {
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

        /**
         * Przejście ze zmierzchu w tryb nocny
         * Kiedy jesteśmy w domu i automatyczne światła zewnętrze są włączone - zapalamy światła na ogrodzie
         */
        if (
            $insolation < InsolationLevel::OutdoorLightsOn->value
            && $config['daylight_mode'] !== DaylightMode::Night->value
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

        /**
         * Poranek - przejście z nocy w tryb zmierzchu
         * Wyłączamy światła na ogrodzie
         */
        if (
            $insolation > InsolationLevel::OutdoorLightsOff->value
            && $config['daylight_mode'] === DaylightMode::Night->value
        ) {
            $this->shellySwitchService->switch(Garland::DEVICE_ID, Garland::CHANNEL, 'off');
            $this->configRepository->updateValueByName('daylight_mode', DaylightMode::Twilight);

            return;
        }

        /**
         * Pełny dzień - przejście zw zmierzchu w tryb dzienny
         * Wyłączamy światła w domu
         */
        if (
            $insolation > InsolationLevel::IndoorLightsOff->value
            && $config['daylight_mode'] !== DaylightMode::Day->value
        ) {
            $tvLightsStatusCache = $this->cache->getItem(TvHookSubscriber::TV_ON_CACHE_KEY);

            if ($tvLightsStatusCache->isHit() && $tvLightsStatusCache->get() === true) {
                // turn off kitchen lights scene
                $this->shellySceneService->trigger(TurnOffKitchenLightsScene::ID);
            } else {
                // turn off all lights scene
                $this->shellySceneService->trigger(TurnOffLightsScene::ID);
            }

            $this->configRepository->updateValueByName('daylight_mode', DaylightMode::Day);

            return;
        }
    }
}
