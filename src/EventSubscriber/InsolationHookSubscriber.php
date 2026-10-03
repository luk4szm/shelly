<?php

namespace App\EventSubscriber;

use App\Enum\DaylightMode;
use App\Enum\InsolationLevel;
use App\Event\Hook\InsolationHookEvent;
use App\Model\Scene\TurnOffKitchenLightsScene;
use App\Model\Scene\TurnOffLightsScene;
use App\Model\Scene\TurnOffOutsideLightsScene;
use App\Model\Scene\TurnOnKitchenLightsScene;
use App\Model\Scene\TurnOnOutsideLightsScene;
use App\Model\Scene\TurnOnTvLedsScene;
use App\Repository\ConfigRepository;
use App\Service\Shelly\Scene\ShellySceneService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Cache\NamespacedPoolInterface;

readonly class InsolationHookSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private ConfigRepository        $configRepository,
        private ShellySceneService      $shellySceneService,
        private NamespacedPoolInterface $cache,
        private LoggerInterface         $logger,
        #[Autowire('%env(bool:LOCAL_LIGHTING_AUTOMATION_ENABLED)%')]
        private bool                    $localLightingAutomationEnabled,
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
        if (!$this->localLightingAutomationEnabled) {
            return;
        }

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
                    $this->triggerScene(TurnOnTvLedsScene::ID);
                }

                $this->triggerScene(TurnOnKitchenLightsScene::ID);
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
                $this->triggerScene(TurnOnOutsideLightsScene::ID);
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
            $this->triggerScene(TurnOffOutsideLightsScene::ID);
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
                $this->triggerScene(TurnOffKitchenLightsScene::ID);
            } else {
                // turn off all lights scene
                $this->triggerScene(TurnOffLightsScene::ID);
            }

            $this->configRepository->updateValueByName('daylight_mode', DaylightMode::Day);

            return;
        }
    }

    private function triggerScene(int $sceneId): void
    {
        $result = $this->shellySceneService->trigger($sceneId);

        if (!$result->isSuccessful()) {
            $this->logger->warning('Local lighting scene partially failed.', $result->toArray());
        }
    }
}
