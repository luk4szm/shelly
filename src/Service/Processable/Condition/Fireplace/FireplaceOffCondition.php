<?php

namespace App\Service\Processable\Condition\Fireplace;

use App\Model\DeviceStatus;
use App\Model\Status;

final class FireplaceOffCondition extends FireplaceCondition
{
    public const NAME = 'fireplace_off_condition';

    public function isSatisfied(): bool
    {
        /** @var DeviceStatus $status */
        $status = $this->fireplaceStatusHelper->getHistory(1)[0];

        if ($status->getStatus() === Status::ACTIVE) {
            // fireplace must be off
            return false;
        }

        if ($status->getStatusDuration() < 5 * 60) {
            // fireplace must be off for at least 5 minutes
            return false;
        }

        return true;
    }
}
