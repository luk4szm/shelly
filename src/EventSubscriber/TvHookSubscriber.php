<?php

namespace App\EventSubscriber;

use App\Event\Hook\TvHookEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class TvHookSubscriber implements EventSubscriberInterface
{
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

        if ($power > 0) {
            // TODO: on tv turn on
        } else {
            // TODO: on tv turn off
        }
    }
}
