<?php

namespace App\Modules\Order\Models;

use App\Modules\Core\Traits\Models\StoresDatesWithOffset;
use App\Modules\Notification\Models\LogLetter;
use App\Modules\Order\Enums\CurrencyEnum;
use App\Modules\Order\Enums\OrderStatusEnum;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Current state of a marketplace order, taken from the freshest snapshot received.
 *
 * `external_*` columns hold the marketplace's own values; `created_at` / `updated_at` are ours.
 */
#[Fillable([
    'external_id',
    'number',
    'status',
    'currency',
    'customer_email',
    'customer_name',
    'customer_phone',
    'delivery_price',
    'discount',
    'total',
    'shipment_carrier',
    'shipment_tracking_number',
    'shipment_tracking_url',
    'paid_at',
    'cancelled_at',
    'cancel_reason',
    'external_created_at',
    'external_updated_at',
])]
#[UseFactory(OrderFactory::class)]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory, StoresDatesWithOffset;

    protected function casts(): array
    {
        return [
            'status'              => OrderStatusEnum::class,
            'currency'            => CurrencyEnum::class,
            'delivery_price'      => 'decimal:2',
            'discount'            => 'decimal:2',
            'total'               => 'decimal:2',
            'paid_at'             => 'immutable_datetime',
            'cancelled_at'        => 'immutable_datetime',
            'external_created_at' => 'immutable_datetime',
            'external_updated_at' => 'immutable_datetime',
        ];
    }

    /**
     * Exact email, ignoring case; matches the lower(customer_email) index.
     */
    #[Scope]
    protected function whereCustomerEmail(Builder $query, string $email): void
    {
        $query->whereRaw('lower(customer_email) = ?', [mb_strtolower(trim($email))]);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class)->orderBy('position');
    }

    public function history(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->orderBy('changed_at')->orderBy('id');
    }

    public function letters(): HasMany
    {
        return $this->hasMany(LogLetter::class)->orderBy('id');
    }
}
