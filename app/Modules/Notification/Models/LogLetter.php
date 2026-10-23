<?php

namespace App\Modules\Notification\Models;

use App\Modules\Core\Traits\Models\StoresDatesWithOffset;
use App\Modules\Notification\Enums\LetterEnum;
use App\Modules\Notification\Enums\LogLetterStatusEnum;
use App\Modules\Order\Models\Order;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A letter as it was (or is going to be) sent: the rendered text and its delivery status.
 */
#[Fillable([
    'order_id',
    'template_key',
    'receiver',
    'subject',
    'sender',
    'sender_name',
    'body',
    'status',
    'error_message',
])]
class LogLetter extends Model
{
    use StoresDatesWithOffset;

    protected function casts(): array
    {
        return [
            'template_key' => LetterEnum::class,
            'status'       => LogLetterStatusEnum::class,
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
