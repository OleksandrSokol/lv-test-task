<?php

namespace App\Modules\Order\Events;

use App\Modules\Order\Models\Order;

class OrderUpdated extends OrderEvent
{
    /**
     * @param  array<string, array{old: mixed, new: mixed}>  $changes
     */
    public function __construct(
        Order $order,
        public readonly array $changes,
    ) {
        parent::__construct($order);
    }

    protected function name(): string
    {
        return 'order.updated';
    }

    protected function details(): array
    {
        return ['changes' => $this->changes];
    }
}
