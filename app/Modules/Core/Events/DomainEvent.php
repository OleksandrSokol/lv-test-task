<?php

namespace App\Modules\Core\Events;

use App\Modules\Core\Contracts\RecordsActivity;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Something an action did. Delivered after the outermost commit,
 * so a rolled back action leaves neither log rows nor side effects.
 */
abstract class DomainEvent implements RecordsActivity, ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;
}
