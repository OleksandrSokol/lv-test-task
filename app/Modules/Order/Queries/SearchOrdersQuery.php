<?php

namespace App\Modules\Order\Queries;

use App\Modules\Order\Dtos\OrderFilterData;
use App\Modules\Order\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Orders for the admin's list: newest placed first, by the marketplace's time.
 */
class SearchOrdersQuery
{
    /**
     * @return LengthAwarePaginator<int, Order>
     */
    public function handle(OrderFilterData $filter): LengthAwarePaginator
    {
        return Order::query()
            ->when($filter->status, fn ($query, $status) => $query->where('status', $status))
            ->when($filter->email, fn ($query, $email) => $query->whereCustomerEmail($email))
            // id keeps the order stable for orders placed in the same second.
            ->orderByDesc('external_created_at')
            ->orderByDesc('id')
            ->paginate(perPage: $filter->perPage, page: $filter->page)
            ->withQueryString();
    }
}
