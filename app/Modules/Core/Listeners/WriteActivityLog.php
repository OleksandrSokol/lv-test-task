<?php

namespace App\Modules\Core\Listeners;

use App\Modules\Core\Contracts\RecordsActivity;
use App\Modules\Core\Models\Log;

class WriteActivityLog
{
    public function handle(RecordsActivity $event): void
    {
        $activity = $event->activity();

        // A plain insert: a log row must not fire model events of its own.
        Log::query()->insert([
            'event'        => $activity->event->value,
            'subject_type' => $activity->subject?->getMorphClass(),
            'subject_id'   => $activity->subject?->getKey(),
            'data'         => $activity->data === [] ? null : json_encode($activity->data, JSON_UNESCAPED_UNICODE),
            'source'       => $activity->source->value,
            'created_at'   => now(),
        ]);
    }
}
