<?php

namespace Tests\Feature\Infrastructure;

use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ModulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_module_migrations_are_loaded(): void
    {
        $this->assertTrue(Schema::hasTable('users'));
    }

    public function test_module_model_resolves_its_factory(): void
    {
        $user = User::factory()->create();

        $this->assertModelExists($user);
    }

    public function test_unknown_api_route_renders_json_without_accept_header(): void
    {
        $this->get('/api/missing')
            ->assertNotFound()
            ->assertHeader('Content-Type', 'application/json')
            ->assertJsonStructure(['message']);
    }
}
