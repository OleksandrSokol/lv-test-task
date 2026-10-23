<?php

namespace App\Modules\Order\Enums;

enum SnapshotOutcomeEnum: string
{
    // The snapshot was newer than the stored state and replaced it.
    case APPLIED = 'applied';

    // The stored state is as new or newer; only the status history may have been completed.
    case STALE = 'stale';
}
