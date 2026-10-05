<?php

namespace Tests\Feature;

use App\Enums\ServerStatus;
use App\Models\Server;
use App\Models\User;
use Database\Seeders\ServerSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServerSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_50_demo_servers_owned_by_the_first_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        app(ServerSeeder::class)->run();

        $servers = Server::all();

        $this->assertCount(50, $servers);
        $this->assertSame([$admin->id], $servers->pluck('owner_id')->unique()->all());
        $this->assertSame(3, $servers->pluck('status')->unique()->count());
        $this->assertSame(50, $servers->pluck('name')->unique()->count());
        $this->assertSame(5, $servers->whereNull('renewal_date')->count());
        $this->assertSame(10, $servers->whereNotNull('renewal_date')->where('renewal_date', '<', now()->toDateString())->count());
        $this->assertTrue($servers->contains(fn (Server $server) => $server->status === ServerStatus::Active));
    }

    public function test_running_it_twice_appends_without_touching_existing_rows(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $existing = Server::factory()->create(['owner_id' => $admin->id, 'name' => 'keep-me']);

        app(ServerSeeder::class)->run();
        app(ServerSeeder::class)->run();

        $this->assertSame(101, Server::count());
        $this->assertDatabaseHas('servers', ['name' => 'keep-me']);
    }

    public function test_it_does_nothing_when_there_are_no_users(): void
    {
        app(ServerSeeder::class)->run();

        $this->assertSame(0, Server::count());
    }
}
