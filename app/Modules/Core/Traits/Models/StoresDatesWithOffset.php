<?php

namespace App\Modules\Core\Traits\Models;

/**
 * Writes dates to `timestamptz` columns together with their UTC offset.
 *
 * The default format (`Y-m-d H:i:s`) drops the offset, so `13:02:11+03:00`
 * would be stored as `13:02:11` in the session time zone — a different moment.
 */
trait StoresDatesWithOffset
{
    public function getDateFormat(): string
    {
        return 'Y-m-d H:i:sP';
    }
}
