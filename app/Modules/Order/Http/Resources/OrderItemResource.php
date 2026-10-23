<?php

namespace App\Modules\Order\Http\Resources;

use App\Modules\Order\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin OrderItem
 */
class OrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'sku'      => $this->sku,
            'name'     => $this->name,
            'quantity' => $this->quantity,
            'price'    => $this->price,
            'subtotal' => $this->subtotal,
        ];
    }
}
