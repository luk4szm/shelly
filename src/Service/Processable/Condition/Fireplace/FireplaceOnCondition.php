<?php

namespace App\Service\Processable\Condition\Fireplace;

use App\Model\DeviceStatus;
use App\Model\Status;

final class FireplaceOnCondition extends FireplaceCondition
{
    public const NAME = 'fireplace_on_condition';

    public function isSatisfied(): bool
    {
        /** @var DeviceStatus $status */
        $status = $this->fireplaceStatusHelper->getHistory(1)[0];

        if ($status->getStatus() === Status::INACTIVE) {
            // fireplace must be on
            return false;
        }

        if ($status->getStatusDuration() < 5 * 60) {
            // fireplace must be on for at least 5 minutes
            return false;
        }

        return true;
    }
}
