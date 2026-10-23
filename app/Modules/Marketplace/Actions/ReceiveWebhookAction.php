<?php

namespace App\Modules\Marketplace\Actions;

use App\Modules\Marketplace\Dtos\WebhookEventData;
use App\Modules\Marketplace\Enums\WebhookStatusEnum;
use App\Modules\Marketplace\Events\WebhookProcessed;
use App\Modules\Marketplace\Models\WebhookEvent;
use App\Modules\Order\Dtos\OrderSnapshotData;
use Illuminate\Support\Facades\DB;

/**
 * Records an event in the inbox and processes it exactly once.
 *
 * The inbox row and the order change commit together: if processing fails, the event
 * is not recorded and the marketplace's redelivery processes it again.
 */
class ReceiveWebhookAction
{
    public function handle(WebhookEventData $event, ?OrderSnapshotData $snapshot): WebhookStatusEnum
    {
        return DB::transaction(fn () => $this->receive($event, $snapshot));
    }

    private function receive(WebhookEventData $event, ?OrderSnapshotData $snapshot): WebhookStatusEnum
    {
        // A concurrent delivery of the same event waits on the unique index until this one commits.
        $inbox = WebhookEvent::query()->createOrFirst(['event_id' => $event->id], [
            'type'              => $event->type,
            'order_external_id' => $snapshot?->externalId,
            'payload'           => $event->toArray(),
            'occurred_at'       => $event->createdAt,
            'status'            => WebhookStatusEnum::RECEIVED,
        ]);

        if (!$inbox->wasRecentlyCreated) {
            WebhookProcessed::dispatch($inbox, WebhookStatusEnum::DUPLICATE);

            return WebhookStatusEnum::DUPLICATE;
        }

        $status = $snapshot === null ? WebhookStatusEnum::IGNORED : $this->apply($snapshot);

        $inbox->update(['status' => $status]);

        WebhookProcessed::dispatch($inbox, $status);

        return $status;
    }

    private function apply(OrderSnapshotData $snapshot): WebhookStatusEnum
    {
        return WebhookStatusEnum::APPLIED;
    }
}
