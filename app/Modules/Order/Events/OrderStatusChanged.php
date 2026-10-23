<?php

namespace App\Modules\Order\Events;

use App\Modules\Order\Enums\OrderStatusEnum;
use App\Modules\Order\Models\Order;

/**
 * The order's current status became `$to`. `$from` is null for an order seen for the first time.
 */
class OrderStatusChanged extends OrderEvent
{
    public function __construct(
        Order $order,
        public readonly ?OrderStatusEnum $from,
        public readonly OrderStatusEnum $to,
    ) {
        parent::__construct($order);
    }

    protected function name(): string
    {
        return 'order.status_changed';
    }

    protected function details(): array
    {
        return ['from' => $this->from?->value, 'to' => $this->to->value];
    }
}
