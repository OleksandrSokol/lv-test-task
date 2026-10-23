<?php

namespace App\Modules\Notification\Models;

use App\Modules\Core\Traits\Models\StoresDatesWithOffset;
use App\Modules\Notification\Enums\LetterEnum;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Editable subject and body of a letter; `{{ name }}` placeholders are filled on sending.
 */
#[Fillable(['key', 'sender', 'sender_name', 'subject', 'body'])]
class LetterTemplate extends Model
{
    use StoresDatesWithOffset;

    protected function casts(): array
    {
        return [
            'key' => LetterEnum::class,
        ];
    }
}
