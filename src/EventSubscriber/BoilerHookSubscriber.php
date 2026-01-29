<?php

namespace App\EventSubscriber;

use App\Event\Hook\BoilerHookEvent;
use App\Service\Device\FireplacePumpService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

readonly class BoilerHookSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private FireplacePumpService $fireplacePumpService,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            BoilerHookEvent::class => 'turnOffFireplacePumps',
        ];
    }

    public function turnOffFireplacePumps(BoilerHookEvent $event): void
    {
        $hook  = $event->getHook();
        $power = (int)$hook->getValue();

        if (
            $hook->getProperty() !== 'power'
            || $power < 10
        ) {
            // Boiler is off
            // No need to check fireplace pump state

            return;
        }

        $fireplacePumpsStatus = $this->fireplacePumpService->getActualState();

        if ($fireplacePumpsStatus['active'] === false) {
            // the fireplace pumps are switched off
            // no action is required

            return;
        }

        $this->fireplacePumpService->setHeatingPumpState(false);
    }
}
