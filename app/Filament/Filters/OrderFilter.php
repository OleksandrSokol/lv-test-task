<?php

namespace App\Filament\Filters;

use App\Modules\Order\Models\Order;
use Closure;
use Filament\Forms\Components\Select;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

/**
 * "Order" filter: pick an order by its number or marketplace id.
 *
 * Tables refer to an order differently — the activity log by our id inside `data`,
 * the webhook inbox by the marketplace id — so the caller says which attribute
 * the picked value is and how to apply it.
 */
class OrderFilter
{
    /**
     * @param  string  $name  form field, also the query string key: `filters[order][<name>]`
     * @param  string  $attribute  the order's attribute the value is: `id` or `external_id`
     * @param  Closure(Builder, string): mixed  $apply
     */
    public static function make(string $name, string $attribute, Closure $apply): Filter
    {
        $find = fn (mixed $value): ?Order => Order::query()->where($attribute, $value)->first();

        return Filter::make('order')
            ->schema([
                Select::make($name)
                    ->label(__('admin.filters.order'))
                    ->searchable()
                    ->getSearchResultsUsing(fn (string $search) => Order::query()
                        ->where('number', 'ilike', "%{$search}%")
                        ->orWhere('external_id', 'ilike', "%{$search}%")
                        ->orderByDesc('external_created_at')
                        ->limit(50)
                        ->get()
                        ->mapWithKeys(fn (Order $order) => [$order->getAttribute($attribute) => self::label($order)])
                        ->all())
                    ->getOptionLabelUsing(fn (mixed $value) => ($order = $find($value)) ? self::label($order) : $value),
            ])
            ->query(fn (Builder $query, array $data) => $query->when(
                $data[$name] ?? null,
                fn (Builder $query, mixed $value) => $apply($query, (string) $value),
            ))
            ->indicateUsing(fn (array $data) => filled($data[$name] ?? null)
                ? __('admin.filters.order_indicator', ['order' => ($order = $find($data[$name])) ? self::label($order) : $data[$name]])
                : null);
    }

    private static function label(Order $order): string
    {
        return "{$order->number} ({$order->external_id})";
    }
}
