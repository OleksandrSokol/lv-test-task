<?php

namespace App\Modules\Core\Models;

use App\Modules\Core\Enums\ActivitySourceEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * The activity log: one row per action, written by `WriteActivityLog`.
 */
class Log extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'data'       => 'array',
            'source'     => ActivitySourceEnum::class,
            'created_at' => 'immutable_datetime',
        ];
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
