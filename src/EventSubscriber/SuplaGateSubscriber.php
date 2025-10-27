<?php

namespace App\EventSubscriber;

use App\Entity\User;
use App\Entity\UserNotification;
use App\Enum\Channel;
use App\Event\SuplaGateOpenEvent;
use App\Repository\UserNotificationRepository;
use App\Service\SmsApi\SmsSender;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

readonly class SuplaGateSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private SmsSender                  $smsSender,
        private UserNotificationRepository $notificationRepository,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SuplaGateOpenEvent::class => 'uponSuplaGateOpen',
        ];
    }

    public function uponSuplaGateOpen(SuplaGateOpenEvent $event): void
    {
        $notifies = $this->notificationRepository->findToExecuteByEvent(\App\Enum\Event\SuplaGateOpenEvent::OPEN->value);

        if (empty($notifies)) {
            return;
        }

        $userIdentifier = $event->getUserEmail();

        /** @var UserNotification $notify */
        foreach ($notifies as $notify) {
            match ($notify->getChannel()) {
                Channel::SMS   => $this->sendSms($notify->getUser(), $userIdentifier),
                Channel::EMAIL => $this->sendEmail($notify->getUser(), $userIdentifier, $event->getMethod()),
                default               => throw new \RuntimeException('Unknown channel'),
            };

            $notify->setExecutedAt(new \DateTimeImmutable());

            $this->notificationRepository->save($notify);
        }
    }

    private function sendSms(User $recipient, string $userIdentifier): void
    {
        $this->smsSender->sendMessage(
            $recipient->getPhoneNumber(),
            sprintf('Użytkownik %s właśnie otworzył bramę!', $userIdentifier)
        );
    }

    private function sendEmail(User $recipient, string $userIdentifier, string $method): void
    {
        mail(
            $recipient->getEmail(),
            '[HA_MSG] The gate has been opened!',
            sprintf('Użytkownik %s właśnie otworzył bramę! (%s)', $userIdentifier, $method)
        );
    }

}
