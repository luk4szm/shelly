<?php

namespace App\Tests\EventSubscriber;

use App\Controller\Device\ShellySwitchController;
use App\EventSubscriber\ShellyRateLimitSubscriber;
use App\Exception\ShellyRateLimitException;
use App\Model\Request\DeviceSwitchRequestPayload;
use App\Service\Curl\Shelly\ShellyCloudCurlRequest;
use App\Service\Shelly\Switch\ShellySwitchService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver;
use Symfony\Component\HttpKernel\Controller\ControllerResolver;
use Symfony\Component\HttpKernel\HttpKernel;

final class ShellyRateLimitSubscriberTest extends TestCase
{
    public function testSwitchFailureBecomesJson429(): void
    {
        $cloud = $this->createMock(ShellyCloudCurlRequest::class);
        $cloud->expects(self::once())->method('switch')->willThrowException(new ShellyRateLimitException());
        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new ShellyRateLimitSubscriber());
        $kernel = new HttpKernel($dispatcher, new ControllerResolver(), new RequestStack(), new ArgumentResolver());
        $request = Request::create('/device/switch/turn', 'PATCH');
        $request->attributes->set('_controller', function () use ($cloud) {
            return (new ShellySwitchController())->turn(
                new DeviceSwitchRequestPayload(deviceId: 'test-device', action: 'on', channel: 0), new ShellySwitchService($cloud),
            );
        });
        $response = $kernel->handle($request);
        self::assertSame(429, $response->getStatusCode());
        self::assertSame(['error' => (new ShellyRateLimitException())->getMessage()], json_decode($response->getContent(), true));
    }
}
