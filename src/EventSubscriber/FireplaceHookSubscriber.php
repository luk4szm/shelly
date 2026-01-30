<?php

namespace App\EventSubscriber;

use App\Event\Hook\FireplaceHookEvent;
use App\Model\Device\Boiler;
use App\Repository\HookRepository;
use App\Repository\UserRepository;
use App\Service\Device\FireplacePumpService;
use App\Service\SmsApi\SmsSender;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

readonly class FireplaceHookSubscriber implements EventSubscriberInterface
{
    private const TEMP_HANDICAP = 3;

    public function __construct(
        private HookRepository       $hookRepository,
        private UserRepository       $userRepository,
        private FireplacePumpService $fireplacePumpService,
        private SmsSender            $smsSender,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            FireplaceHookEvent::class => 'turnOnFireplacePumps',
        ];
    }

    public function turnOnFireplacePumps(FireplaceHookEvent $event): void
    {
        $hook          = $event->getHook();
        $fireplaceTemp = (int)$hook->getValue();

        if (
            $hook->getProperty() !== 'temp'
            || $fireplaceTemp < 30
        ) {
            // When it is below we can be sure that the fireplace is already extinguished,
            // all automatic processes have already taken place
            // and there is no need to further check the condition of the devices
            return;
        }

        if ($fireplaceTemp > 60) {
            $this->smsSender->sendMessage(
                $this->userRepository->findAdmin()->getPhoneNumber(),
                sprintf('Wysoka temperatura (%d) w kominku!', $fireplaceTemp),
                __CLASS__,
                15
            );
        }

        $bufferTemp           = $this->hookRepository->findActualTempForLocation('bufor');
        $fireplacePumpsStatus = $this->fireplacePumpService->getActualState();

        if ($fireplacePumpsStatus['active'] === true) {
            // fireplace pumps are already on
            if ($bufferTemp->getValue() > ($fireplaceTemp + self::TEMP_HANDICAP)) {
                // temperature in the buffer has dropped below the fireplace power -> turn off pumps
                $this->fireplacePumpService->setHeatingPumpState(false);
            }
        }

        if ($fireplacePumpsStatus['active'] === false) {
            // fireplace pumps are already off
            if ($bufferTemp->getValue() < ($fireplaceTemp + self::TEMP_HANDICAP)) {
                // temperature in the fireplace has risen enough to heat the buffer
                $boilerPowerHook = $this->hookRepository->findLastDevicePowerHook(Boiler::NAME);

                if ($boilerPowerHook->getValue() >= Boiler::BOUNDARY_POWER) {
                    // the boiler is running
                    // we cannot turn on the pump until it switches off
                    return;
                }

                $this->fireplacePumpService->setHeatingPumpState(true);
            }
        }
    }
}
