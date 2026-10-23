<?php

namespace App\Modules\Order\Queries;

use App\Modules\Order\Models\Order;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * An order with everything its card shows, by the marketplace's id.
 */
class FindOrderByExternalIdQuery
{
    /**
     * @throws ModelNotFoundException<Order>
     */
    public function handle(string $externalId): Order
    {
        return Order::query()
            ->with(['items', 'history'])
            ->where('external_id', $externalId)
            ->firstOrFail();
    }
}
