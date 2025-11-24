<?php

namespace App\EventSubscriber;

use App\Entity\User;
use App\Entity\UserNotification;
use App\Enum\Channel;
use App\Event\SuplaGateOpenEvent;
use App\Repository\UserNotificationRepository;
use App\Service\SmsApi\SmsSender;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

readonly class SuplaGateSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private SmsSender                  $smsSender,
        private UserNotificationRepository $notificationRepository,
        private MailerInterface            $mailer,
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
                default        => throw new \RuntimeException('Unknown channel'),
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
        $message = (new Email())
            ->from(new Address(
                $_ENV['MAILER_SENDER_NAME'],
                $_ENV['MAILER_SENDER_MAIL'],
            ))
            ->to($recipient->getEmail())
            ->subject('[HA_MSG] The gate has been opened!')
            ->html(sprintf('Użytkownik <b>%s</b> właśnie otworzył bramę! (%s)', $userIdentifier, $method));

        $this->mailer->send($message);
    }

}
