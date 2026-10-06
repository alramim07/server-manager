<?php

namespace Tests\Feature;

use App\Models\Server;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class DemoResetTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.demo_enabled' => true,
            'session.driver' => 'database',
        ]);
    }

    public function test_demo_reset_restores_the_seed_state(): void
    {
        User::factory()->create(['email' => 'stray@example.com']);
        Server::factory()->create(['name' => 'stray-box']);

        $this->artisan('demo:reset')->assertSuccessful();

        $this->assertDatabaseMissing('users', ['email' => 'stray@example.com']);
        $this->assertDatabaseMissing('servers', ['name' => 'stray-box']);
        $this->assertDatabaseHas('users', ['email' => 'demo@example.com', 'role' => 'admin']);
        $this->assertDatabaseHas('users', ['email' => 'alex@example.com', 'role' => 'member']);
        $this->assertDatabaseHas('users', ['email' => 'jordan@example.com', 'role' => 'member']);
        $this->assertSame(50, Server::count());
    }

    public function test_idle_reset_skips_while_a_session_is_active(): void
    {
        $this->insertSession(now()->getTimestamp());
        Server::factory()->create(['name' => 'keep-me']);
        $countBefore = Server::count();

        $this->artisan('demo:reset-if-idle')->assertSuccessful();

        $this->assertSame($countBefore, Server::count());
        $this->assertDatabaseHas('servers', ['name' => 'keep-me']);
    }

    public function test_idle_reset_fires_when_no_session_is_recent(): void
    {
        $this->insertSession(now()->subHours(2)->getTimestamp());
        Server::factory()->create(['name' => 'stale-marker']);

        $this->artisan('demo:reset-if-idle')->assertSuccessful();

        $this->assertDatabaseMissing('servers', ['name' => 'stale-marker']);
        $this->assertSame(50, Server::count());
    }

    public function test_idle_reset_skips_when_the_sessions_table_is_missing(): void
    {
        Server::factory()->create(['name' => 'survivor']);
        Schema::drop('sessions');

        $this->artisan('demo:reset-if-idle')->assertSuccessful();

        $this->assertDatabaseHas('servers', ['name' => 'survivor']);
    }

    public function test_resets_are_skipped_when_demo_mode_is_disabled(): void
    {
        config(['app.demo_enabled' => false]);
        Server::factory()->create(['name' => 'untouchable']);

        $this->artisan('demo:reset')->assertSuccessful();
        $this->artisan('demo:reset-if-idle')->assertSuccessful();

        $this->assertDatabaseHas('servers', ['name' => 'untouchable']);
    }

    public function test_seeding_twice_does_not_duplicate_demo_data(): void
    {
        $this->artisan('db:seed')->assertSuccessful();
        $this->artisan('db:seed')->assertSuccessful();

        $this->assertSame(50, Server::count());
        $this->assertSame(3, User::count());
    }

    private function insertSession(int $lastActivity): void
    {
        DB::table('sessions')->insert([
            'id' => Str::uuid()->toString(),
            'user_id' => null,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'phpunit',
            'payload' => base64_encode(''),
            'last_activity' => $lastActivity,
        ]);
    }
}
