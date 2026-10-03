<?php

declare(strict_types=1);

namespace App\Tests\Controller\Cover;

use App\Controller\Cover\CoverController;
use App\Model\Device\Cover\RollerCover;
use App\Service\Shelly\Cover\ShellyCoverService;
use App\Service\Shelly\Local\ShellyCoverStatusReader;
use App\Service\Shelly\Local\ShellyCoverWriter;
use App\Service\Shelly\Local\ShellyDeviceRegistry;
use App\Service\Shelly\Local\ShellyRpcClient;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\Request;

final class CoverControllerTest extends TestCase
{
    public function testReadKeepsLastDirectionAndExposesActualState(): void
    {
        $http = new MockHttpClient(new MockResponse(
            '{"id":1,"result":{"id":0,"state":"stopped","last_direction":"open"}}',
        ));
        $service = $this->service($http);
        $controller = new CoverController();
        $controller->setContainer(new Container());

        $response = $controller->read($service);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame([
            'last_direction' => 'open',
            'state' => 'stopped',
        ], json_decode($response->getContent(), true, flags: JSON_THROW_ON_ERROR));
        self::assertSame(1, $http->getRequestsCount());
    }

    public function testReadDoesNotInventAStateWhenDeviceIsUnavailable(): void
    {
        $service = $this->service(new MockHttpClient([
            new MockResponse('', ['error' => 'mDNS unavailable']),
            new MockResponse('', ['error' => 'IP unavailable']),
        ]));
        $controller = new CoverController();
        $controller->setContainer(new Container());

        $response = $controller->read($service);

        self::assertSame(503, $response->getStatusCode());
        $body = json_decode($response->getContent(), true, flags: JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('error', $body);
        self::assertArrayNotHasKey('state', $body);
    }

    public function testOtherReadFailuresRemainServerErrors(): void
    {
        $service = $this->service(new MockHttpClient(new MockResponse(
            '{"id":1,"result":{"id":0,"last_direction":"open"}}',
        )));
        $controller = new CoverController();
        $controller->setContainer(new Container());

        $response = $controller->read($service);

        self::assertSame(500, $response->getStatusCode());
        self::assertStringContainsString('no valid state', $response->getContent());
    }

    public function testInvalidDirectionDoesNotSendMovementCommand(): void
    {
        $http = new MockHttpClient();
        $controller = new CoverController();
        $controller->setContainer(new Container());

        $response = $controller->index(new Request([], ['direction' => 'sideways']), $this->service($http));

        self::assertSame(400, $response->getStatusCode());
        self::assertSame(0, $http->getRequestsCount());
    }

    private function service(MockHttpClient $http): ShellyCoverService
    {
        $registry = new ShellyDeviceRegistry([new RollerCover()]);
        $rpc = new ShellyRpcClient($http);

        return new ShellyCoverService(
            new ShellyCoverWriter($registry, $rpc),
            new ShellyCoverStatusReader($registry, $rpc),
            $this->createMock(LoggerInterface::class),
            $this->createMock(Security::class),
        );
    }
}
