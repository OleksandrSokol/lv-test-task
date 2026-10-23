<?php

namespace App\Modules\Order\Dtos;

use App\Modules\Order\Enums\CurrencyEnum;
use App\Modules\Order\Enums\OrderStatusEnum;
use Carbon\CarbonImmutable;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\Validation\DateFormat;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Regex;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\DateTimeInterfaceCast;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Full state of an order at the moment of an event, as the marketplace sends it.
 *
 * Dates are converted to UTC on the way in: the query builder formats bound dates
 * without an offset, so a non-UTC date would compare as a different moment.
 */
#[MapInputName(SnakeCaseMapper::class)]
class OrderSnapshotData extends Data
{
    public const AMOUNT_PATTERN = '/^-?\d+\.\d{2}$/';

    /**
     * @param  array<int, OrderItemData>  $items
     */
    public function __construct(
        #[MapInputName('id')]
        public string $externalId,
        public string $number,
        public OrderStatusEnum $status,
        public CurrencyEnum $currency,
        public CustomerData $customer,
        #[DataCollectionOf(OrderItemData::class), Min(1)]
        public array $items,
        #[Regex(self::AMOUNT_PATTERN)]
        public string $deliveryPrice,
        #[Regex(self::AMOUNT_PATTERN)]
        public string $discount,
        #[Regex(self::AMOUNT_PATTERN)]
        public string $total,
        public ?ShipmentData $shipment,
        #[DateFormat(DATE_ATOM), WithCast(DateTimeInterfaceCast::class, setTimeZone: 'UTC')]
        public ?CarbonImmutable $paidAt,
        #[DateFormat(DATE_ATOM), WithCast(DateTimeInterfaceCast::class, setTimeZone: 'UTC')]
        public ?CarbonImmutable $cancelledAt,
        public ?string $cancelReason,
        #[DateFormat(DATE_ATOM), WithCast(DateTimeInterfaceCast::class, setTimeZone: 'UTC')]
        public CarbonImmutable $createdAt,
        #[DateFormat(DATE_ATOM), WithCast(DateTimeInterfaceCast::class, setTimeZone: 'UTC')]
        public CarbonImmutable $updatedAt,
    ) {}
}
