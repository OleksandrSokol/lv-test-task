<?php

namespace App\Modules\Order\Http\Resources;

use App\Modules\Order\Models\OrderStatusHistory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin OrderStatusHistory
 */
class OrderStatusHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'status' => $this->status,
            'at'     => $this->changed_at->toIso8601String(),
        ];
    }
}
