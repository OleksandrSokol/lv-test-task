<?php

namespace App\Modules\Marketplace\Dtos;

use App\Modules\Order\Dtos\OrderSnapshotData;
use Carbon\CarbonImmutable;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Attributes\Validation\Date;
use Spatie\LaravelData\Attributes\Validation\Regex;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\DateTimeInterfaceCast;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * The event envelope. `data` stays raw: its shape depends on the type,
 * and a type we do not know must still be accepted.
 */
#[MapName(SnakeCaseMapper::class)]
class WebhookEventData extends Data
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public string $id,
        public string $type,
        #[Regex(OrderSnapshotData::DATE_PATTERN), Date, WithCast(DateTimeInterfaceCast::class, format: OrderSnapshotData::DATE_FORMATS, setTimeZone: 'UTC')]
        public CarbonImmutable $createdAt,
        public array $data = [],
    ) {}
}
