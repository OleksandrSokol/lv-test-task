<?php

namespace Tests\Concerns;

use App\Modules\Order\Dtos\OrderSnapshotData;

trait BuildsOrderSnapshots
{
    /**
     * Order snapshot of an example event from task/examples, optionally changed.
     *
     * @param  array<string, mixed>  $override  merged recursively into the order
     */
    protected function snapshot(string $example, array $override = []): OrderSnapshotData
    {
        $event = json_decode(file_get_contents(base_path("task/examples/{$example}.json")), true);

        return OrderSnapshotData::validateAndCreate(array_replace_recursive($event['data']['order'], $override));
    }
}
