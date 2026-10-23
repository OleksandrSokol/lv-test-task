<?php

namespace Tests\Unit\Notification;

use App\Modules\Core\Models\Log;
use App\Modules\Notification\Dtos\SendLetterData;
use App\Modules\Notification\Enums\LetterEnum;
use App\Modules\Notification\Enums\LogLetterStatusEnum;
use App\Modules\Notification\Jobs\SendLetterJob;
use App\Modules\Notification\Mail\LetterMail;
use App\Modules\Notification\Models\LetterTemplate;
use App\Modules\Notification\Models\LogLetter;
use App\Modules\Notification\Services\LetterService;
use App\Modules\Order\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use RuntimeException;
use Tests\TestCase;

class LetterServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedLetterTemplates();

        Queue::fake();
    }

    public function test_template_placeholders_are_filled(): void
    {
        LetterTemplate::query()->where('key', LetterEnum::ORDER_PAID)->update([
            'subject' => 'Заказ №{{ number }}',
            'body'    => 'Итого {{total}} {{ currency }}, {{ unknown }}',
            'sender'  => 'shop@example.com',
        ]);

        $letter = $this->service()->send(new SendLetterData(
            key: LetterEnum::ORDER_PAID,
            to: 'anna@example.com',
            replace: ['number' => 'MH-1', 'total' => '10.00', 'currency' => 'RUB'],
        ));

        $this->assertSame('Заказ №MH-1', $letter->subject);
        $this->assertSame('Итого 10.00 RUB, {{ unknown }}', $letter->body);
        $this->assertSame('anna@example.com', $letter->receiver);
        $this->assertSame('shop@example.com', $letter->sender);
        $this->assertSame(LogLetterStatusEnum::QUEUED, $letter->status);
        Queue::assertPushed(SendLetterJob::class);
    }

    public function test_order_gets_a_letter_of_a_kind_once(): void
    {
        $order = Order::factory()->create();
        $data = new SendLetterData(key: LetterEnum::ORDER_PAID, to: 'a@example.com', orderId: $order->id);

        $this->assertNotNull($this->service()->send($data));
        $this->assertNull($this->service()->send($data));

        $this->assertSame(1, LogLetter::query()->count());
        Queue::assertPushed(SendLetterJob::class, 1);
    }

    public function test_pending_letters_are_skipped_except_kept_one(): void
    {
        $order = Order::factory()->create();
        $paid = $this->service()->send(new SendLetterData(key: LetterEnum::ORDER_PAID, to: 'a@example.com', orderId: $order->id));
        $cancelled = $this->service()->send(new SendLetterData(key: LetterEnum::ORDER_CANCELLED, to: 'a@example.com', orderId: $order->id));

        $this->service()->skipPending($order->id, keep: [LetterEnum::ORDER_CANCELLED], reason: 'cancelled');

        $this->assertSame(LogLetterStatusEnum::SKIPPED, $paid->refresh()->status);
        $this->assertSame(LogLetterStatusEnum::QUEUED, $cancelled->refresh()->status);
        $this->assertSame(1, Log::query()->where('event', 'letter.skipped')->count());
    }

    public function test_job_sends_queued_letter_and_marks_it_sent(): void
    {
        Mail::fake();
        $letter = $this->service()->send(new SendLetterData(key: LetterEnum::ORDER_PAID, to: 'a@example.com'));

        (new SendLetterJob($letter))->handle();

        Mail::assertSent(LetterMail::class, fn (LetterMail $mail) => $mail->hasTo('a@example.com'));
        $this->assertSame(LogLetterStatusEnum::SENT, $letter->refresh()->status);
    }

    public function test_job_does_not_send_withdrawn_letter(): void
    {
        Mail::fake();
        $letter = $this->service()->send(new SendLetterData(key: LetterEnum::ORDER_PAID, to: 'a@example.com'));
        $letter->update(['status' => LogLetterStatusEnum::SKIPPED]);

        (new SendLetterJob($letter))->handle();

        Mail::assertNothingSent();
    }

    public function test_failed_job_marks_letter_failed(): void
    {
        $letter = $this->service()->send(new SendLetterData(key: LetterEnum::ORDER_PAID, to: 'a@example.com'));

        (new SendLetterJob($letter))->failed(new RuntimeException('SMTP is down'));

        $this->assertSame(LogLetterStatusEnum::FAILED, $letter->refresh()->status);
        $this->assertSame('SMTP is down', $letter->error_message);
    }

    private function service(): LetterService
    {
        return app(LetterService::class);
    }
}
