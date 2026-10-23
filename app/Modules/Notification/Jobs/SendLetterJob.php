<?php

namespace App\Modules\Notification\Jobs;

use App\Modules\Notification\Enums\LogLetterStatusEnum;
use App\Modules\Notification\Events\LetterFailed;
use App\Modules\Notification\Events\LetterSent;
use App\Modules\Notification\Mail\LetterMail;
use App\Modules\Notification\Models\LogLetter;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendLetterJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 10;

    public function __construct(
        public LogLetter $letter,
    ) {}

    public function handle(): void
    {
        DB::transaction(function () {
            // The lock keeps sending and withdrawing (LetterService::skipPending) of one letter apart.
            $letter = LogLetter::query()->lockForUpdate()->find($this->letter->id);

            if ($letter?->status !== LogLetterStatusEnum::QUEUED) {
                return;
            }

            Mail::to($letter->receiver)->send(new LetterMail($letter));

            $letter->update(['status' => LogLetterStatusEnum::SENT, 'error_message' => null]);

            LetterSent::dispatch($letter);
        });
    }

    public function failed(Throwable $e): void
    {
        $this->letter->update(['status' => LogLetterStatusEnum::FAILED, 'error_message' => $e->getMessage()]);

        LetterFailed::dispatch($this->letter);
    }
}
