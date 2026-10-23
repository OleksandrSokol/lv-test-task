<?php

namespace Tests\Feature\Scenarios;

use App\Modules\Marketplace\Models\WebhookEvent;
use App\Modules\Notification\Enums\LetterEnum;
use App\Modules\Notification\Models\LogLetter;
use App\Modules\Order\Enums\OrderStatusEnum;
use App\Modules\Order\Models\Order;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\SendsMarketplaceWebhooks;
use Tests\TestCase;

/**
 * Deliveries that really run at the same time: forked processes, each with its own
 * database connection, released together. Data has to be committed for the others
 * to see it, so the database is truncated instead of wrapped in a transaction.
 */
class ConcurrentDeliveryTest extends TestCase
{
    use DatabaseTruncation, SendsMarketplaceWebhooks;

    private const ROUNDS = 5;

    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();

        if (!function_exists('pcntl_fork')) {
            $this->markTestSkipped('pcntl is needed to run deliveries in parallel.');
        }

        $this->seedLetterTemplates();

        Mail::fake();

        $this->dir = storage_path('framework/testing/concurrency-' . uniqid());
        File::ensureDirectoryExists($this->dir);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->dir);

        // Leave nothing behind for the tests that run in a transaction.
        $this->truncateTablesForAllConnections();

        parent::tearDown();
    }

    public function test_same_event_delivered_twice_at_once_is_processed_once(): void
    {
        for ($round = 1; $round <= self::ROUNDS; $round++) {
            $body = $this->exampleEvent('order-paid', [
                'id'   => "evt_race_{$round}",
                'data' => ['order' => ['id' => "ord_race_{$round}"]],
            ]);

            $statuses = $this->deliverAtOnce([$body, $body]);

            sort($statuses);
            $this->assertSame(['applied', 'duplicate'], $statuses, "round {$round}");
            $this->assertSame(1, WebhookEvent::query()->where('event_id', "evt_race_{$round}")->count());
            $this->assertSame(1, Order::query()->where('external_id', "ord_race_{$round}")->count());
            $this->assertSame(1, $this->letters("ord_race_{$round}", LetterEnum::ORDER_PAID));
        }
    }

    public function test_different_events_of_a_new_order_at_once_make_one_order(): void
    {
        for ($round = 1; $round <= self::ROUNDS; $round++) {
            $override = ['data' => ['order' => ['id' => "ord_new_{$round}"]]];

            $statuses = $this->deliverAtOnce([
                $this->exampleEvent('order-created', ['id' => "evt_created_{$round}", ...$override]),
                $this->exampleEvent('order-paid', ['id' => "evt_paid_{$round}", ...$override]),
            ]);

            // Whichever came first, the paid snapshot is the newer one and wins.
            $this->assertContains('applied', $statuses, "round {$round}");
            $order = Order::query()->where('external_id', "ord_new_{$round}")->sole();
            $this->assertSame(OrderStatusEnum::PAID, $order->status);
            $this->assertSame(['created', 'paid'], $order->history()->pluck('status')->map->value->all());
            $this->assertSame(1, $this->letters("ord_new_{$round}", LetterEnum::ORDER_PAID));
        }
    }

    /**
     * Sends each body from its own process, all released at the same moment.
     *
     * @param  list<string>  $bodies
     * @return list<string> the `status` of each response
     */
    private function deliverAtOnce(array $bodies): array
    {
        $go = "{$this->dir}/go";
        File::delete($go);

        // A forked child must not share the parent's database socket.
        DB::disconnect();

        $children = [];

        foreach ($bodies as $i => $body) {
            $pid = pcntl_fork();

            if ($pid === 0) {
                $this->runChild($body, $go, "{$this->dir}/result-{$i}");
            }

            $children[$i] = $pid;
        }

        File::put($go, '');

        foreach ($children as $pid) {
            pcntl_waitpid($pid, $status);
        }

        return array_map(function (int $i) {
            $result = "{$this->dir}/result-{$i}";
            $this->assertFileExists($result, "delivery {$i} did not finish");

            return File::get($result);
        }, array_keys($bodies));
    }

    private function runChild(string $body, string $go, string $result): never
    {
        try {
            // Connected and warmed up before the start, so both requests hit the database together.
            DB::select('select 1');

            while (!file_exists($go)) {
                usleep(200);
            }

            $response = $this->sendWebhook($body);

            File::put($result, $response->status() === 200 ? $response->json('status') : "HTTP {$response->status()}");
        } catch (\Throwable $e) {
            File::put($result, 'error: ' . $e->getMessage());
        }

        // Skip PHPUnit's shutdown and destructors: they belong to the parent.
        posix_kill(posix_getpid(), SIGKILL);

        exit(1);
    }

    private function letters(string $externalId, LetterEnum $letter): int
    {
        return LogLetter::query()
            ->whereHas('order', fn ($query) => $query->where('external_id', $externalId))
            ->where('template_key', $letter)
            ->count();
    }
}
