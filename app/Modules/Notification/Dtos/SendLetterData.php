<?php

namespace App\Modules\Notification\Dtos;

use App\Modules\Notification\Enums\LetterEnum;
use Spatie\LaravelData\Data;

class SendLetterData extends Data
{
    /**
     * @param  array<string, string>  $replace  values for the template's `{{ name }}` placeholders
     * @param  int|null  $orderId  the order the letter is about; one letter of a kind per order
     */
    public function __construct(
        public LetterEnum $key,
        public string $to,
        public array $replace = [],
        public ?int $orderId = null,
    ) {}
}
