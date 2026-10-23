<?php

namespace App\Modules\Order\Http\Resources\Concerns;

use DateTimeInterface;

/**
 * ISO 8601 with offset, as the marketplace sends it: fractional seconds only when there are any.
 */
trait FormatsMoments
{
    protected function moment(?DateTimeInterface $at): ?string
    {
        if ($at === null) {
            return null;
        }

        if ($at->format('u') === '000000') {
            return $at->format(DATE_ATOM);
        }

        return preg_replace('/\.(\d*?)0*(?=[+-])/', '.$1', $at->format('Y-m-d\TH:i:s.uP'));
    }
}
