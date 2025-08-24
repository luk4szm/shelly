<?php

namespace App\EventSubscriber;

use App\Entity\User;
use App\Event\Hook\CoHookEvent;
use App\Repository\UserRepository;
use App\Service\SmsApi\SmsSender;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

readonly class CoHookSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private SmsSender      $smsSender,
        private UserRepository $userRepository,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CoHookEvent::class => 'uponReceiptCoPressureData',
        ];
    }

    public function uponReceiptCoPressureData(CoHookEvent $event): void
    {
        $hook = $event->getHook();

        if ($hook->getProperty() !== 'pressure') {
            return;
        }

        // pressure given in kPa
        if ((int)$hook->getValue() < 250) {
            return;
        }

        /** @var User $user */
        foreach ($this->userRepository->findInmates() as $user) {
            $this->smsSender->sendMessage(
                $user->getPhoneNumber(),
                sprintf("Extremely high pressure (%s bar) in the system CO", number_format($hook->getValue() / 100, 1))
            );
        }
    }
}
