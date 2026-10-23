<?php

namespace Tests\Feature\Order;

use App\Modules\Notification\Enums\LetterEnum;
use App\Modules\Notification\Enums\LogLetterStatusEnum;
use App\Modules\Notification\Jobs\SendLetterJob;
use App\Modules\Notification\Mail\LetterMail;
use App\Modules\Notification\Models\LogLetter;
use App\Modules\Order\Events\OrderStatusChanged;
use App\Modules\Order\Listeners\SendOrderStatusLetter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\SendsMarketplaceWebhooks;
use Tests\TestCase;

class CustomerLettersTest extends TestCase
{
    use RefreshDatabase, SendsMarketplaceWebhooks;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedLetterTemplates();

        Mail::fake();
    }

    public function test_paid_order_gets_letter_with_items_and_total(): void
    {
        $this->sendWebhook($this->exampleEvent('order-paid'))->assertOk();

        Mail::assertSent(LetterMail::class, 1);
        Mail::assertSent(LetterMail::class, function (LetterMail $mail) {
            $body = $mail->letter->body;

            return $mail->hasTo('anna.smirnova@example.com')
                && $mail->hasSubject('Заказ №MH-1048213 оплачен')
                && str_contains($body, 'Футболка базовая, чёрная, M × 3 — 3870.00 RUB')
                && str_contains($body, 'Бейсболка "Classic" / синяя × 1 — 890.50 RUB')
                && str_contains($body, 'Итого: 4909.50 RUB');
        });
    }

    public function test_letter_is_sent_once_for_redelivery_and_newer_snapshot_of_same_status(): void
    {
        $this->sendWebhook($this->exampleEvent('order-paid'))->assertOk();
        $this->sendWebhook($this->exampleEvent('order-paid'))->assertJson(['status' => 'duplicate']);
        $this->sendWebhook($this->exampleEvent('order-paid', [
            'id'   => 'evt_paid_again',
            'data' => ['order' => ['updated_at' => '2026-10-21T13:10:00+03:00']],
        ]))->assertJson(['status' => 'applied']);

        Mail::assertSent(LetterMail::class, 1);
    }

    public function test_shipped_order_gets_carrier_and_tracking(): void
    {
        $this->sendWebhook($this->exampleEvent('order-shipped'))->assertOk();

        Mail::assertSent(LetterMail::class, function (LetterMail $mail) {
            $body = $mail->letter->body;

            return $mail->hasSubject('Заказ №MH-1048213 передан в доставку')
                && str_contains($body, 'Служба доставки: СДЭК')
                && str_contains($body, 'Трек-номер: 1098765432')
                && str_contains($body, 'https://www.cdek.ru/ru/tracking?order_id=1098765432');
        });
    }

    public function test_cancelled_paid_order_letter_mentions_refund(): void
    {
        $this->sendWebhook($this->exampleEvent('order-cancelled'))->assertOk();

        Mail::assertSent(LetterMail::class, function (LetterMail $mail) {
            return $mail->hasSubject('Заказ №MH-1048377 отменён')
                && str_contains($mail->letter->body, 'Причина: Покупатель отказался от заказа')
                && str_contains($mail->letter->body, 'вернутся в течение 10 дней');
        });
    }

    public function test_cancelled_unpaid_order_letter_has_no_refund(): void
    {
        $this->sendWebhook($this->exampleEvent('order-cancelled', ['data' => ['order' => ['paid_at' => null]]]))->assertOk();

        Mail::assertSent(LetterMail::class, fn (LetterMail $mail) => !str_contains($mail->letter->body, 'вернутся'));
    }

    /**
     * @return array<string, array{string, array<string, mixed>}>
     */
    public static function silentEvents(): array
    {
        return [
            'created'   => ['order-created', []],
            'updated'   => ['order-updated', []],
            'delivered' => ['order-shipped', ['type' => 'order.delivered', 'data' => ['order' => ['status' => 'delivered']]]],
        ];
    }

    #[DataProvider('silentEvents')]
    public function test_status_without_letter_sends_nothing(string $example, array $override): void
    {
        $this->sendWebhook($this->exampleEvent($example, $override))->assertOk();

        Mail::assertNothingSent();
    }

    public function test_paid_letter_is_sent_when_order_is_already_shipped_by_then(): void
    {
        Event::fake([OrderStatusChanged::class]);

        $this->sendWebhook($this->exampleEvent('order-paid'))->assertOk();
        $this->sendWebhook($this->exampleEvent('order-shipped'))->assertOk();

        // The queue gets to both listeners only now, when the order is already shipped.
        $listener = app(SendOrderStatusLetter::class);
        Event::dispatched(OrderStatusChanged::class)->each(fn (array $args) => $listener->handle($args[0]));

        Mail::assertSent(LetterMail::class, 2);
        Mail::assertSent(LetterMail::class, fn (LetterMail $mail) => $mail->hasSubject('Заказ №MH-1048213 оплачен'));
        Mail::assertSent(LetterMail::class, fn (LetterMail $mail) => $mail->hasSubject('Заказ №MH-1048213 передан в доставку'));
    }

    public function test_paid_letter_is_not_sent_when_order_is_cancelled_by_then(): void
    {
        Event::fake([OrderStatusChanged::class]);

        $this->sendWebhook($this->exampleEvent('order-paid'))->assertOk();
        $this->sendWebhook($this->cancelledAfterPaid())->assertOk();

        $listener = app(SendOrderStatusLetter::class);
        Event::dispatched(OrderStatusChanged::class)->each(fn (array $args) => $listener->handle($args[0]));

        Mail::assertSent(LetterMail::class, 1);
        Mail::assertSent(LetterMail::class, fn (LetterMail $mail) => $mail->hasSubject('Заказ №MH-1048213 отменён'));
    }

    public function test_paid_letter_still_queued_is_withdrawn_when_order_is_cancelled(): void
    {
        Queue::fake([SendLetterJob::class]);

        $this->sendWebhook($this->exampleEvent('order-paid'))->assertOk();
        $this->sendWebhook($this->cancelledAfterPaid())->assertOk();

        Queue::pushed(SendLetterJob::class)->each(fn (SendLetterJob $job) => $job->handle());

        Mail::assertSent(LetterMail::class, 1);
        Mail::assertSent(LetterMail::class, fn (LetterMail $mail) => $mail->hasSubject('Заказ №MH-1048213 отменён'));
        $this->assertSame(
            LogLetterStatusEnum::SKIPPED,
            LogLetter::query()->where('template_key', LetterEnum::ORDER_PAID)->sole()->status,
        );
    }

    private function cancelledAfterPaid(): string
    {
        return $this->exampleEvent('order-paid', [
            'id'   => 'evt_cancelled',
            'type' => 'order.cancelled',
            'data' => ['order' => [
                'status'        => 'cancelled',
                'cancelled_at'  => '2026-10-21T14:00:00+03:00',
                'cancel_reason' => 'Нет в наличии',
                'updated_at'    => '2026-10-21T14:00:00+03:00',
            ]],
        ]);
    }
}
