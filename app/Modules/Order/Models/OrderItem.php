<?php

namespace App\Modules\Order\Models;

use Database\Factories\OrderItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A line of the order.
 */
#[Fillable(['order_id', 'position', 'sku', 'name', 'quantity', 'price'])]
#[UseFactory(OrderItemFactory::class)]
class OrderItem extends Model
{
    /** @use HasFactory<OrderItemFactory> */
    use HasFactory;

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'quantity' => 'integer',
            'price'    => 'decimal:2',
        ];
    }

    /**
     * price × quantity, in exact decimal arithmetic.
     */
    protected function subtotal(): Attribute
    {
        return Attribute::get(fn (): string => bcmul($this->price, (string) $this->quantity, 2));
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
