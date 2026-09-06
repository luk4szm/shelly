<?php

namespace App\EventSubscriber;

use App\Exception\ShellyRateLimitException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class ShellyRateLimitSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::EXCEPTION => ['onException', 10]];
    }

    public function onException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if ($exception instanceof ShellyRateLimitException) {
            $event->setResponse(new JsonResponse(
                ['error' => $exception->getMessage()],
                Response::HTTP_TOO_MANY_REQUESTS,
            ));
        }
    }
}
