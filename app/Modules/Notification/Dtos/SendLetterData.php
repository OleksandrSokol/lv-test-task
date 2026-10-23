<?php

namespace App\Modules\Notification\Dtos;

use App\Modules\Notification\Enums\LetterEnum;
use Spatie\LaravelData\Data;

class SendLetterData extends Data
{
    /**
     * @param  int  $orderId  the order the letter is about; one letter of a kind per order
     * @param  array<string, string>  $replace  values for the template's `{{ name }}` placeholders
     */
    public function __construct(
        public LetterEnum $key,
        public string $to,
        public int $orderId,
        public array $replace = [],
    ) {}
}
