<?php

namespace App\Modules\Core\Contracts;

use App\Modules\Core\Dtos\ActivityData;

/**
 * An event that leaves a row in the activity log (`logs`).
 */
interface RecordsActivity
{
    public function activity(): ActivityData;
}
