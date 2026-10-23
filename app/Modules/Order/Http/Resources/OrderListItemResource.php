<?php

namespace App\Modules\Order\Http\Resources;

use App\Modules\Order\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Order
 */
class OrderListItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'       => $this->external_id,
            'number'   => $this->number,
            'status'   => $this->status,
            'customer' => [
                'email' => $this->customer_email,
                'name'  => $this->customer_name,
            ],
            'total'    => $this->total,
            'currency' => $this->currency,
            // The marketplace's moments, not when we received them.
            'created_at' => $this->external_created_at->toIso8601String(),
            'updated_at' => $this->external_updated_at->toIso8601String(),
        ];
    }
}
