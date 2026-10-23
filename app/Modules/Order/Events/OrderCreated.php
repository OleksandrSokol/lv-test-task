<?php

namespace App\Modules\Order\Events;

use App\Modules\Core\Enums\ActivityEventEnum;

class OrderCreated extends OrderEvent
{
    protected function name(): ActivityEventEnum
    {
        return ActivityEventEnum::ORDER_CREATED;
    }

    protected function details(): array
    {
        return ['status' => $this->order->status->value];
    }
}
