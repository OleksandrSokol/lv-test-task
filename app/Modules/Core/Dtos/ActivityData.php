<?php

namespace App\Modules\Core\Dtos;

use App\Modules\Core\Enums\ActivityEventEnum;
use App\Modules\Core\Enums\ActivitySourceEnum;
use Illuminate\Database\Eloquent\Model;
use Spatie\LaravelData\Data;

/**
 * One row of the activity log, as a domain event describes it.
 */
class ActivityData extends Data
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public ActivityEventEnum $event,
        public ActivitySourceEnum $source,
        public ?Model $subject = null,
        public array $data = [],
    ) {}
}
