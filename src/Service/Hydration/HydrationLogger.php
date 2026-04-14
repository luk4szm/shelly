<?php

namespace App\Service\Hydration;

use App\Entity\HydrationLog;
use App\Repository\HydrationLogRepository;
use App\Utils\TimeUtils;

readonly class HydrationLogger
{
    public function __construct(
        private HydrationLogRepository $repository,
    ) {}

    public function start(string $valve): void
    {
        $log = (new HydrationLog())
            ->setValve($valve)
            ->setStartsAt(new \DateTimeImmutable());

        $this->repository->save($log);
    }

    public function stop(string $valve): void
    {
        $log = $this->repository->findActiveLog($valve);

        if (!$log) {
            throw new \RuntimeException(sprintf('There is no active hydration log for valve "%s"', $valve));
        }

        $log->setEndsAt(new \DateTimeImmutable())
            ->setDuration(TimeUtils::diffInSeconds(
                $log->getStartsAt(),
                $log->getEndsAt()
            ));

        $this->repository->save($log);
    }
}
