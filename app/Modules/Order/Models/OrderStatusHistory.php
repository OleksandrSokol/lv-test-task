<?php

namespace App\Modules\Order\Models;

use App\Modules\Core\Traits\Models\StoresDatesWithOffset;
use App\Modules\Order\Enums\OrderStatusEnum;
use Database\Factories\OrderStatusHistoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A status the order has been in, at the moment the marketplace reports.
 */
#[Fillable(['order_id', 'status', 'changed_at'])]
#[UseFactory(OrderStatusHistoryFactory::class)]
class OrderStatusHistory extends Model
{
    /** @use HasFactory<OrderStatusHistoryFactory> */
    use HasFactory, StoresDatesWithOffset;

    public const UPDATED_AT = null;

    protected $table = 'order_status_history';

    protected function casts(): array
    {
        return [
            'status'     => OrderStatusEnum::class,
            'changed_at' => 'immutable_datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
