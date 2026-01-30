<?php

namespace App\Service\SmsApi;

use App\Entity\Sms;
use App\Repository\SmsRepository;
use Smsapi\Client\Curl\SmsapiHttpClient;
use Smsapi\Client\Feature\Sms\Bag\SendSmsBag;

readonly final class SmsSender
{
    public function __construct(
        private SmsRepository $smsRepository,
    ) {
    }

    /**
     * @param int         $phoneNumber
     * @param string      $message
     * @param string|null $trigger
     * @param int|null    $interval Time interval between subsequent shipments from the same trigger in minutes
     * @return void
     */
    public function sendMessage(
        int     $phoneNumber,
        string  $message,
        ?string $trigger = null,
        ?int    $interval = null,
    ): void
    {
        if ($trigger !== null && $interval !== null) {
            $previousSms = $this->smsRepository->findPreviousSmsForTrigger($trigger);

            if (
                $previousSms === null
                || $previousSms->getCreatedAt()->getTimestamp() + ($interval * 60) > time()
            ) {
                return;
            }
        }

        $smsBag = SendSmsBag::withMessage($phoneNumber, 'HA_MSG: ' . $message);
        $smsBag->test = $_ENV['APP_ENV'] === 'dev';

        $response = (new SmsapiHttpClient())
            ->smsapiPlService($_ENV['SMS_API_TOKEN'])
            ->smsFeature()
            ->sendSms($smsBag);

        $sms = (new Sms())
            ->setPhoneNumber($phoneNumber)
            ->setMessage($message)
            ->setDeveloper($trigger)
            ->setCost($response->points)
            ->setIsTest($_ENV['APP_ENV'] === 'dev')
            ->setRaport([$response]);

        $this->smsRepository->save($sms);
    }
}
