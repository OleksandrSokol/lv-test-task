<?php

namespace App\Modules\Notification\Services;

use App\Modules\Notification\Dtos\SendLetterData;
use App\Modules\Notification\Enums\LetterEnum;
use App\Modules\Notification\Enums\LogLetterStatusEnum;
use App\Modules\Notification\Events\LetterQueued;
use App\Modules\Notification\Events\LetterSkipped;
use App\Modules\Notification\Jobs\SendLetterJob;
use App\Modules\Notification\Models\LetterTemplate;
use App\Modules\Notification\Models\LogLetter;

/**
 * Renders a template into `log_letters` and queues it for sending.
 */
class LetterService
{
    /**
     * Returns null when the order has already been given this letter.
     */
    public function send(SendLetterData $data): ?LogLetter
    {
        $template = LetterTemplate::query()->where('key', $data->key)->firstOrFail();

        $attributes = [
            'receiver'    => $data->to,
            'subject'     => $this->replace($template->subject, $data->replace),
            'sender'      => $template->sender,
            'sender_name' => $template->sender_name,
            'body'        => $this->replace($template->body, $data->replace),
            'status'      => LogLetterStatusEnum::QUEUED,
        ];

        $letter = $data->orderId === null
            ? LogLetter::query()->create(['template_key' => $data->key, ...$attributes])
            : LogLetter::query()->createOrFirst(['order_id' => $data->orderId, 'template_key' => $data->key], $attributes);

        if (!$letter->wasRecentlyCreated) {
            return null;
        }

        LetterQueued::dispatch($letter);
        SendLetterJob::dispatch($letter)->afterCommit();

        return $letter;
    }

    /**
     * Withdraws the order's letters still waiting in the queue, except those in `$keep`.
     *
     * @param  list<LetterEnum>  $keep
     */
    public function skipPending(int $orderId, array $keep, string $reason): void
    {
        $pending = LogLetter::query()
            ->where('order_id', $orderId)
            ->where('status', LogLetterStatusEnum::QUEUED)
            ->whereNotIn('template_key', $keep)
            ->lockForUpdate()
            ->get();

        foreach ($pending as $letter) {
            $letter->update(['status' => LogLetterStatusEnum::SKIPPED, 'error_message' => $reason]);

            LetterSkipped::dispatch($letter, $reason);
        }
    }

    /**
     * @param  array<string, string>  $replace
     */
    private function replace(string $content, array $replace): string
    {
        return preg_replace_callback(
            '/{{\s*(\w+)\s*}}/',
            fn (array $match) => array_key_exists($match[1], $replace) ? (string) $replace[$match[1]] : $match[0],
            $content,
        );
    }
}
