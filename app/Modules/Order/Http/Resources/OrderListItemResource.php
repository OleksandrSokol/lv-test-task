<?php

namespace App\Modules\Order\Http\Resources;

use App\Modules\Order\Http\Resources\Concerns\FormatsMoments;
use App\Modules\Order\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Order
 */
class OrderListItemResource extends JsonResource
{
    use FormatsMoments;

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
            'created_at' => $this->moment($this->external_created_at),
            'updated_at' => $this->moment($this->external_updated_at),
        ];
    }
}
