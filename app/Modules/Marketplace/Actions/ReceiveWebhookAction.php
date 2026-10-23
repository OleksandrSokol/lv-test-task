<?php

namespace App\Modules\Marketplace\Actions;

use App\Modules\Marketplace\Dtos\WebhookEventData;
use App\Modules\Marketplace\Enums\WebhookStatusEnum;
use App\Modules\Marketplace\Events\WebhookProcessed;
use App\Modules\Marketplace\Models\WebhookEvent;
use App\Modules\Order\Actions\ApplyOrderSnapshotAction;
use App\Modules\Order\Dtos\OrderSnapshotData;
use App\Modules\Order\Enums\SnapshotOutcomeEnum;
use Illuminate\Support\Facades\DB;

/**
 * Records an event in the inbox and processes it exactly once.
 *
 * The inbox row and the order change commit together: if processing fails, the event
 * is not recorded and the marketplace's redelivery processes it again.
 */
class ReceiveWebhookAction
{
    public function __construct(
        private readonly ApplyOrderSnapshotAction $applySnapshot,
    ) {}

    /**
     * @param  string  $rawBody  the request body as received, kept for investigations
     */
    public function handle(WebhookEventData $event, ?OrderSnapshotData $snapshot, string $rawBody): WebhookStatusEnum
    {
        return DB::transaction(fn () => $this->receive($event, $snapshot, $rawBody));
    }

    private function receive(WebhookEventData $event, ?OrderSnapshotData $snapshot, string $rawBody): WebhookStatusEnum
    {
        // A concurrent delivery of the same event waits on the unique index until this one commits.
        $inbox = WebhookEvent::query()->createOrFirst(['event_id' => $event->id], [
            'type'              => $event->type,
            'order_external_id' => $snapshot?->externalId,
            'raw_body'          => $rawBody,
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
        return match ($this->applySnapshot->handle($snapshot)) {
            SnapshotOutcomeEnum::APPLIED => WebhookStatusEnum::APPLIED,
            SnapshotOutcomeEnum::STALE   => WebhookStatusEnum::STALE,
        };
    }
}
