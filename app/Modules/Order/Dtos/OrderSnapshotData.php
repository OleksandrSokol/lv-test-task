<?php

namespace App\Modules\Order\Dtos;

use App\Modules\Order\Enums\CurrencyEnum;
use App\Modules\Order\Enums\OrderStatusEnum;
use Carbon\CarbonImmutable;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\Validation\Date;
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
     * ISO 8601 with an offset; fractional seconds are allowed and kept.
     * A pattern rather than `date_format`: that rule formats the date back and compares,
     * so `…:47.25` would fail against `.u` (`…:47.250000`).
     */
    public const DATE_PATTERN = '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(\.\d{1,6})?(Z|[+-]\d{2}:\d{2})$/';

    public const DATE_FORMATS = [DATE_ATOM, 'Y-m-d\TH:i:s.uP'];

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
        #[Regex(self::DATE_PATTERN), Date, WithCast(DateTimeInterfaceCast::class, format: self::DATE_FORMATS, setTimeZone: 'UTC')]
        public ?CarbonImmutable $paidAt,
        #[Regex(self::DATE_PATTERN), Date, WithCast(DateTimeInterfaceCast::class, format: self::DATE_FORMATS, setTimeZone: 'UTC')]
        public ?CarbonImmutable $cancelledAt,
        public ?string $cancelReason,
        #[Regex(self::DATE_PATTERN), Date, WithCast(DateTimeInterfaceCast::class, format: self::DATE_FORMATS, setTimeZone: 'UTC')]
        public CarbonImmutable $createdAt,
        #[Regex(self::DATE_PATTERN), Date, WithCast(DateTimeInterfaceCast::class, format: self::DATE_FORMATS, setTimeZone: 'UTC')]
        public CarbonImmutable $updatedAt,
    ) {}
}
