<?php

namespace App\Modules\Order\Http\Resources;

use App\Modules\Order\Http\Resources\Concerns\FormatsMoments;
use App\Modules\Order\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The order card. Expects `items` and `history` loaded.
 *
 * @mixin Order
 */
class OrderResource extends JsonResource
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
                'phone' => $this->customer_phone,
            ],
            'currency'       => $this->currency,
            'items'          => OrderItemResource::collection($this->items),
            'delivery_price' => $this->delivery_price,
            'discount'       => $this->discount,
            'total'          => $this->total,
            'shipment'       => $this->shipment_carrier === null ? null : [
                'carrier'         => $this->shipment_carrier,
                'tracking_number' => $this->shipment_tracking_number,
                'tracking_url'    => $this->shipment_tracking_url,
            ],
            'paid_at'       => $this->moment($this->paid_at),
            'cancelled_at'  => $this->moment($this->cancelled_at),
            'cancel_reason' => $this->cancel_reason,
            'created_at'    => $this->moment($this->external_created_at),
            'updated_at'    => $this->moment($this->external_updated_at),
            'history'       => OrderStatusHistoryResource::collection($this->history),
        ];
    }
}
