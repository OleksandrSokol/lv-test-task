<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\LetterTemplates\Pages\ManageLetterTemplates;
use App\Filament\Resources\Logs\Pages\ManageLogs;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Filament\Resources\WebhookEvents\Pages\ManageWebhookEvents;
use App\Modules\Core\Enums\ActivityEventEnum;
use App\Modules\Core\Models\Log;
use App\Modules\Marketplace\Models\WebhookEvent;
use App\Modules\Notification\Enums\LetterEnum;
use App\Modules\Notification\Models\LetterTemplate;
use App\Modules\Order\Models\Order;
use App\Modules\User\Models\User;
use Carbon\CarbonImmutable;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\Concerns\SendsMarketplaceWebhooks;
use Tests\TestCase;

class OrderAdminTest extends TestCase
{
    use RefreshDatabase, SendsMarketplaceWebhooks;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    public function test_site_root_leads_to_admin(): void
    {
        $this->get('/')->assertRedirect('/admin');
    }

    public function test_guest_is_sent_to_login(): void
    {
        auth()->logout();

        $this->get('/admin/orders')->assertRedirect('/admin/login');
    }

    public function test_order_list_opens_newest_placed_first(): void
    {
        $placed = CarbonImmutable::parse('2026-10-21T10:00:00+00:00');
        $newest = Order::factory()->create(['external_created_at' => $placed]);
        $oldest = Order::factory()->create(['external_created_at' => $placed->subDay()]);

        $this->get('/admin/orders')->assertOk();

        Livewire::test(ListOrders::class)->assertCanSeeTableRecords([$newest, $oldest], inOrder: true);
    }

    public function test_order_list_filters_by_status(): void
    {
        $created = Order::factory()->create();
        $paid = Order::factory()->paid()->create();

        Livewire::test(ListOrders::class)
            ->filterTable('status', 'paid')
            ->assertCanSeeTableRecords([$paid])
            ->assertCanNotSeeTableRecords([$created]);
    }

    public function test_order_list_searches_by_email_ignoring_case(): void
    {
        $anna = Order::factory()->create(['customer_email' => 'anna.smirnova@example.com']);
        $ivan = Order::factory()->create(['customer_email' => 'ivan@example.com']);

        Livewire::test(ListOrders::class)
            ->searchTable('Anna.Smirnova@Example.COM')
            ->assertCanSeeTableRecords([$anna])
            ->assertCanNotSeeTableRecords([$ivan]);
    }

    public function test_order_card_shows_items_history_and_letters(): void
    {
        Mail::fake();
        $this->seedLetterTemplates();
        $this->sendWebhook($this->exampleEvent('order-paid'))->assertOk();
        $this->sendWebhook($this->exampleEvent('order-shipped'))->assertOk();

        $order = Order::query()->where('external_id', 'ord_7Q2KX9M4')->firstOrFail();

        $this->get("/admin/orders/{$order->id}")
            ->assertOk()
            ->assertSeeText('MH-1048213')
            ->assertSeeText('Передан в доставку')
            ->assertSeeText('Футболка базовая, чёрная, M')
            ->assertSeeText('3 870,00')
            ->assertSeeText('СДЭК')
            ->assertSeeTextInOrder(['Оформлен', 'Оплачен', 'Передан в доставку'])
            ->assertSeeText('Заказ оплачен')
            ->assertSeeText('Заказ передан в доставку');
    }

    public function test_letter_template_can_be_edited_but_not_created(): void
    {
        $this->seedLetterTemplates();
        $template = LetterTemplate::query()->where('key', LetterEnum::ORDER_PAID)->firstOrFail();

        Livewire::test(ManageLetterTemplates::class)
            ->assertActionDoesNotExist('create')
            ->callTableAction('edit', $template, data: ['subject' => 'Оплата заказа №{{ number }} получена'])
            ->assertHasNoTableActionErrors();

        $this->assertSame('Оплата заказа №{{ number }} получена', $template->refresh()->subject);
    }

    public function test_letter_template_form_explains_placeholders(): void
    {
        $this->seedLetterTemplates();

        Livewire::test(ManageLetterTemplates::class)
            ->mountTableAction('edit', LetterTemplate::query()->firstOrFail())
            ->assertSchemaComponentExists(
                'placeholders',
                'mountedActionSchema0',
                fn (KeyValueEntry $entry) => str_starts_with($entry->getState()['{{ tracking_number }}'] ?? '', 'Трек-номер'),
            );
    }

    public function test_letter_log_opens(): void
    {
        $this->get('/admin/log-letters')->assertOk();
    }

    public function test_order_log_shows_its_letters_and_webhooks_with_the_same_order_ids(): void
    {
        Mail::fake();
        $this->seedLetterTemplates();
        $this->sendWebhook($this->exampleEvent('order-paid'))->assertOk();
        $this->sendWebhook($this->exampleEvent('order-cancelled'))->assertOk();
        $order = Order::query()->where('external_id', 'ord_7Q2KX9M4')->firstOrFail();

        $ofOrder = Log::query()->whereRaw("data->>'order_id' = ?", [(string) $order->id])->get();
        $this->assertEqualsCanonicalizing(
            ['order.created', 'order.status_changed', 'webhook.applied', 'letter.queued', 'letter.sent'],
            $ofOrder->pluck('event')->map->value->all(),
        );
        // One meaning per key, whatever the event is about.
        $ofOrder->each(fn (Log $log) => $this->assertSame(
            [$order->id, 'ord_7Q2KX9M4'],
            [$log->data['order_id'], $log->data['order_external_id']],
            $log->event->value,
        ));

        $url = Livewire::test(ViewOrder::class, ['record' => $order->id])->instance()->getAction('view_logs')->getUrl();
        $this->assertStringContainsString('filters[order][order_id]=' . $order->id, urldecode($url));

        $this->get($url)->assertOk();

        Livewire::test(ManageLogs::class)
            ->filterTable('order', ['order_id' => $order->id])
            ->assertCanSeeTableRecords($ofOrder)
            ->assertTableColumnFormattedStateSet('event', 'Письмо отправлено', $ofOrder->firstWhere('event', ActivityEventEnum::LETTER_SENT))
            ->assertCanNotSeeTableRecords(Log::query()->whereRaw("data->>'order_external_id' = ?", ['ord_3HV8PW2C'])->get());
    }

    public function test_webhook_inbox_shows_events_with_readable_type_and_result(): void
    {
        $this->sendWebhook($this->exampleEvent('order-created'))->assertOk();
        $this->sendWebhook($this->exampleEvent('order-created', ['id' => 'evt_new_type', 'type' => 'order.refund_requested']))
            ->assertJson(['status' => 'ignored']);

        $created = WebhookEvent::query()->where('type', 'order.created')->firstOrFail();
        $unknown = WebhookEvent::query()->where('event_id', 'evt_new_type')->firstOrFail();

        $this->get('/admin/webhook-events')->assertOk();

        Livewire::test(ManageWebhookEvents::class)
            ->assertCanSeeTableRecords([$created, $unknown])
            ->assertTableColumnFormattedStateSet('type', 'Заказ оформлен', $created)
            ->assertTableColumnFormattedStateSet('status', 'Применён', $created)
            ->assertTableColumnFormattedStateSet('type', 'Неизвестный тип: order.refund_requested', $unknown)
            // Only outcomes a stored event can have.
            ->assertTableFilterExists('status', fn (SelectFilter $filter) => array_keys($filter->getOptions()) === ['applied', 'stale', 'ignored'])
            ->filterTable('status', 'ignored')
            ->assertCanSeeTableRecords([$unknown])
            ->assertCanNotSeeTableRecords([$created]);
    }

    public function test_webhook_body_is_shown_formatted_and_copied_as_received(): void
    {
        $body = $this->exampleEvent('order-created');
        $this->sendWebhook($body)->assertOk();
        $event = WebhookEvent::query()->sole();

        Livewire::test(ManageWebhookEvents::class)
            ->mountTableAction('view', $event)
            ->assertSchemaComponentExists('raw_body', 'mountedActionSchema0', fn (TextEntry $entry) => $entry->getCopyableState($body) === $body
                && str_contains($entry->getState(), "\n    \"type\": \"order.created\"")
                && str_contains($entry->formatState($entry->getState()), '<span class="json-key">&quot;type&quot;</span>: <span class="json-string">&quot;order.created&quot;</span>'));
    }

    public function test_menu_is_grouped(): void
    {
        $this->get('/admin/orders')
            ->assertOk()
            ->assertSeeTextInOrder(['Заказы', 'Вебхуки', 'Письма', 'Шаблоны писем', 'Журнал писем', 'Система', 'Журнал действий', 'Пользователи']);
    }

    public function test_user_can_be_edited_but_not_created_or_deleted(): void
    {
        $user = User::factory()->create(['password' => 'old-password']);

        Livewire::test(ManageUsers::class)
            ->assertActionDoesNotExist('create')
            ->assertTableActionDoesNotExist('delete')
            ->callTableAction('edit', $user, data: ['name' => 'Новое имя', 'password' => ''])
            ->assertHasNoTableActionErrors();

        $user->refresh();
        $this->assertSame('Новое имя', $user->name);
        $this->assertTrue(Hash::check('old-password', $user->password), 'an empty field keeps the password');

        Livewire::test(ManageUsers::class)
            ->callTableAction('edit', $user, data: ['password' => 'new-password'])
            ->assertHasNoTableActionErrors();

        $this->assertTrue(Hash::check('new-password', $user->refresh()->password));
    }

    public function test_activity_log_is_newest_first_and_sortable_by_date(): void
    {
        foreach (['-1 hour', 'now', '-2 hours'] as $at) {
            Log::query()->create(['event' => 'order.updated', 'source' => 'webhook', 'created_at' => now()->modify($at)]);
        }

        $newestFirst = Log::query()->orderByDesc('created_at')->get();
        $this->assertCount(3, $newestFirst);

        Livewire::test(ManageLogs::class)
            ->assertCanSeeTableRecords($newestFirst, inOrder: true)
            ->sortTable('created_at')
            ->assertCanSeeTableRecords($newestFirst->reverse(), inOrder: true);
    }
}
