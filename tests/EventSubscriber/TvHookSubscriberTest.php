<?php

declare(strict_types=1);

namespace App\Tests\EventSubscriber;

use App\Entity\Hook;
use App\Event\Hook\TvHookEvent;
use App\EventSubscriber\TvHookSubscriber;
use App\Repository\AirQualityRepository;
use App\Service\AirQuality\InsolationService;
use App\Service\Curl\Shelly\ShellyCloudCurlRequest;
use App\Service\Shelly\Light\ShellyLightService;
use App\Service\Shelly\Local\ShellyLedWriter;
use App\Service\Shelly\Local\ShellyRgbwWriter;
use App\Service\Shelly\Local\ShellyRpcClient;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Contracts\Cache\NamespacedPoolInterface;

final class TvHookSubscriberTest extends TestCase
{
    public function testDisabledLightingAutomationDoesNotReactToTvWebhook(): void
    {
        $cloud = $this->createMock(ShellyCloudCurlRequest::class);
        $cloud->expects(self::never())->method('light');
        $http = new MockHttpClient();
        $rpc = new ShellyRpcClient($http);
        $lights = new ShellyLightService($cloud, new ShellyLedWriter($rpc), new ShellyRgbwWriter($rpc));
        $insolation = new InsolationService($this->createMock(AirQualityRepository::class));

        $subscriber = new TvHookSubscriber(
            $lights,
            $insolation,
            $this->createMock(NamespacedPoolInterface::class),
            false,
        );

        $subscriber->onTvPowerChange(new TvHookEvent(new Hook('tv', 'power', '120')));

        self::assertSame(0, $http->getRequestsCount());
    }
}
