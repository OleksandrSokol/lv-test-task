<?php

namespace Tests\Unit\Core;

use App\Modules\Core\Enums\ActivitySourceEnum;
use App\Modules\Core\Models\Log;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;
use Tests\Unit\Core\Fixtures\UserRenamed;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_domain_event_writes_one_log_row(): void
    {
        $user = User::factory()->create(['name' => 'Анна']);

        UserRenamed::dispatch($user, 'Аня');

        $log = Log::query()->sole();

        $this->assertSame('user.renamed', $log->event);
        $this->assertSame(ActivitySourceEnum::QUEUE, $log->source);
        $this->assertTrue($log->subject->is($user));
        $this->assertEquals(['old' => 'Аня', 'new' => 'Анна'], $log->data);
    }

    public function test_domain_event_is_written_only_after_commit(): void
    {
        $user = User::factory()->create();

        DB::transaction(function () use ($user) {
            UserRenamed::dispatch($user, 'old');

            $this->assertSame(0, Log::query()->count());
        });

        $this->assertSame(1, Log::query()->count());
    }

    public function test_rolled_back_transaction_leaves_no_log_row(): void
    {
        $user = User::factory()->create();

        try {
            DB::transaction(function () use ($user) {
                UserRenamed::dispatch($user, 'old');

                throw new RuntimeException('rollback');
            });
        } catch (RuntimeException) {
        }

        $this->assertSame(0, Log::query()->count());
    }
}
