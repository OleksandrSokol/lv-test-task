<?php

namespace App\Modules\Marketplace\Dtos;

use Carbon\CarbonImmutable;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Attributes\Validation\DateFormat;
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
        #[DateFormat(DATE_ATOM), WithCast(DateTimeInterfaceCast::class, setTimeZone: 'UTC')]
        public CarbonImmutable $createdAt,
        public array $data = [],
    ) {}
}
