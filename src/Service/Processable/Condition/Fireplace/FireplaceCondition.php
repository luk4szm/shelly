<?php

namespace App\Service\Processable\Condition\Fireplace;

use App\Service\DeviceStatus\FireplaceStatusHelper;
use App\Service\Processable\Condition\Condition;

abstract class FireplaceCondition extends Condition
{
    public function __construct(
        protected readonly FireplaceStatusHelper $fireplaceStatusHelper,
    ) {}
}
