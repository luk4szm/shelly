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

    public function sendMessage(int $phoneNumber, string $message): true
    {
        $smsBag = SendSmsBag::withMessage($phoneNumber, $message);
        $smsBag->test = $_ENV['APP_ENV'] === 'dev';

        $response = (new SmsapiHttpClient())
            ->smsapiPlService($_ENV['SMS_API_TOKEN'])
            ->smsFeature()
            ->sendSms($smsBag);

        $sms = (new Sms())
            ->setPhoneNumber($phoneNumber)
            ->setMessage($message)
            ->setCost($response->points)
            ->setIsTest($_ENV['APP_ENV'] === 'dev')
            ->setRaport([$response]);

        $this->smsRepository->save($sms);

        return true;
    }
}
