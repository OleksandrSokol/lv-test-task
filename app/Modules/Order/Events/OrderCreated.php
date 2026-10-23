<?php

namespace App\Modules\Order\Events;

class OrderCreated extends OrderEvent
{
    protected function name(): string
    {
        return 'order.created';
    }

    protected function details(): array
    {
        return ['status' => $this->order->status->value];
    }
}
