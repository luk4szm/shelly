<?php

namespace App\Tests\Command;

use App\Command\ShellyProcessConsumeCommand;
use App\Entity\Process\Process;
use App\Entity\Process\RecurringProcess;
use App\Exception\ShellyRateLimitException;
use App\Repository\Process\HydrationProcessRepository;
use App\Repository\Process\RecurringProcessRepository;
use App\Repository\Process\ScheduledProcessRepository;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class ShellyProcessConsumeCommandTest extends TestCase
{
    /** @dataProvider failureStages */
    public function testContinuesAfterRateLimitAndReportsFailure(bool $failInCondition): void
    {
        $failed = (new RecurringProcess())->setName('failed');
        $next = (new RecurringProcess())->setName('next');
        $recurring = $this->createMock(RecurringProcessRepository::class);
        $recurring->method('findProcessToExecute')->willReturn([$failed, $next]);
        $scheduled = $this->createMock(ScheduledProcessRepository::class);
        $scheduled->method('findProcessToExecute')->willReturn([]);
        $hydration = $this->createMock(HydrationProcessRepository::class);
        $hydration->method('findProcessToExecute')->willReturn([]);
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('error')->with(
            (new ShellyRateLimitException())->getMessage(), ['process' => 'failed', 'process_id' => null],
        );
        $consumer = new class($failInCondition) {
            public array $completed = [];
            public function __construct(private bool $failInCondition) {}
            public function isSupported(string $name): bool { return true; }
            public function canBeExecuted(Process $process): bool
            {
                if ($this->failInCondition && $process->getName() === 'failed') {
                    throw new ShellyRateLimitException();
                }
                return true;
            }
            public function process(Process $process): void
            {
                if ($process->getName() === 'failed') {
                    throw new ShellyRateLimitException();
                }
                $this->completed[] = $process->getName();
            }
        };
        $tester = new CommandTester(new ShellyProcessConsumeCommand(
            [$consumer], [], [], $recurring, $scheduled, $hydration, $logger,
        ));
        self::assertSame(Command::FAILURE, $tester->execute([]));
        self::assertSame(['next'], $consumer->completed);
        self::assertNull($failed->getLastRunAt());
        self::assertStringContainsString('Executed 1 process(es); 1 failed', $tester->getDisplay());
    }

    public function failureStages(): iterable
    {
        yield 'condition' => [true];
        yield 'execution' => [false];
    }
}
