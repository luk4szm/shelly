<?php

declare(strict_types=1);

namespace App\Tests\EventSubscriber;

use App\Event\Hook\InsolationHookEvent;
use App\EventSubscriber\InsolationHookSubscriber;
use App\Repository\ConfigRepository;
use App\Service\Shelly\Local\ShellyDeviceRegistry;
use App\Service\Shelly\Local\ShellyLedWriter;
use App\Service\Shelly\Local\ShellyRpcClient;
use App\Service\Shelly\Local\ShellySwitchWriter;
use App\Service\Shelly\Scene\SceneRegistry;
use App\Service\Shelly\Scene\ShellySceneService;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Contracts\Cache\NamespacedPoolInterface;

final class InsolationHookSubscriberTest extends TestCase
{
    public function testDisabledAutomationDoesNotReadConfigOrSendRpc(): void
    {
        $config = $this->createMock(ConfigRepository::class);
        $config->expects(self::never())->method('getAllValues');
        $http = new MockHttpClient();
        $rpc = new ShellyRpcClient($http);
        $devices = new ShellyDeviceRegistry([]);
        $scenes = new ShellySceneService(
            new SceneRegistry([]),
            $devices,
            new ShellyLedWriter($rpc),
            new ShellySwitchWriter($devices, $rpc),
        );
        $cache = $this->createMock(NamespacedPoolInterface::class);

        $subscriber = new InsolationHookSubscriber($config, $scenes, $cache, new NullLogger(), false);
        $subscriber->onInsolationChange(new InsolationHookEvent(0.0));

        self::assertSame(0, $http->getRequestsCount());
    }
}
