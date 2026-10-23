<?php

namespace App\Modules\Core\Traits\Enums;

trait HasEnumHelpers
{
    /**
     * value => label, for selects and filters.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(static::cases())
            ->mapWithKeys(fn ($case) => [
                $case->value => method_exists($case, 'label') ? $case->label() : $case->value,
            ])
            ->toArray();
    }
}
