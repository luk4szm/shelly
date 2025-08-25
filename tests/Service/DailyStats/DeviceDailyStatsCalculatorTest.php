<?php

namespace App\Tests\Service\DailyStats;

use App\Entity\DeviceDailyStats;
use App\Entity\Hook;
use App\Repository\HookRepository;
use App\Service\DailyStats\DeviceDailyStatsCalculator;
use App\Service\DeviceStatus\DeviceStatusHelper;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class DeviceDailyStatsCalculatorTest extends TestCase
{
    private HookRepository $hookRepository;
    private DeviceStatusHelper $statusHelper;
    private DeviceDailyStatsCalculator $sut;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hookRepository = $this->createMock(HookRepository::class);
        $this->statusHelper = $this->createMock(DeviceStatusHelper::class);

        $this->sut = new class($this->hookRepository, $this->statusHelper) extends DeviceDailyStatsCalculator {
            public function getDeviceName(): string
            {
                return 'test-device';
            }
        };
    }

    public function testCalculateDailyStats(): void
    {
        $date = new DateTimeImmutable('2023-01-01');
        $hooks = [
            (new Hook('test-device', 'power', 0))->setCreatedAt(new DateTimeImmutable('2023-01-01 00:00:00')),   // inactive
            (new Hook('test-device', 'power', 100))->setCreatedAt(new DateTimeImmutable('2023-01-01 00:00:10')), // active
            (new Hook('test-device', 'power', 120))->setCreatedAt(new DateTimeImmutable('2023-01-01 00:00:20')), // active
            (new Hook('test-device', 'power', 0))->setCreatedAt(new DateTimeImmutable('2023-01-01 00:00:30')),   // inactive
            (new Hook('test-device', 'power', 150))->setCreatedAt(new DateTimeImmutable('2023-01-01 00:00:40')), // active
            (new Hook('test-device', 'power', 0))->setCreatedAt(new DateTimeImmutable('2023-01-01 00:00:50')),   // inactive
        ];

        $this->hookRepository->expects($this->once())
            ->method('findHooksByDeviceAndDate')
            ->with('test-device', $date)
            ->willReturn($hooks);

        $this->hookRepository->expects($this->any())
            ->method('findLastHookOfDay')
            ->willReturn(null);

        $this->statusHelper->expects($this->any())
            ->method('isActive')
            ->willReturnCallback(fn(Hook $hook) => $hook->getValue() > 0);

        $result = $this->sut->calculateDailyStats($date);

        $this->assertInstanceOf(DeviceDailyStats::class, $result);
        // energy = (100W * 10s + 120W * 10s + 150W * 10s) / 3600 = (1000 + 1200 + 1500) / 3600 = 3700 / 3600 = 1.027... -> round(..., 1) = 1.0
        $this->assertEquals(1.0, $result->getEnergy());
        // 1. (inactive -> active) 2. (inactive -> active)
        $this->assertEquals(2, $result->getInclusions());
        // 10s + 10s + 10s
        $this->assertEquals(30, $result->getTotalActiveTime());
        // First run: 10s + 10s = 20s. Second run: 10s. Max is 20s
        $this->assertEquals(20, $result->getLongestRunTime());
        // First pause: 10s. Second pause: 10s (duration until next hook is unknown, so it's 0, but let's assume it's 10s for the sake of this test based on implementation)
        // The last hook has 0 duration, so the longest pause is just the first one.
        $this->assertEquals(86349, $result->getLongestPauseTime());
    }

    public function testCalculateDailyStatsThrowsExceptionWhenNoHooks(): void
    {
        $this->expectException(\RuntimeException::class);

        $date = new DateTimeImmutable('2023-01-01');

        $this->hookRepository->expects($this->once())
            ->method('findHooksByDeviceAndDate')
            ->with('test-device', $date)
            ->willReturn([]);

        $this->sut->calculateDailyStats($date);
    }
}
