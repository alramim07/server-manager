<?php

namespace Tests\Feature;

use App\Enums\RenewalUrgency;
use App\Enums\ServerStatus;
use App\Models\Server;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServerPersistenceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_it_persists_a_server_and_reloads_it_with_casts_working(): void
    {
        $server = Server::factory()->create([
            'status' => ServerStatus::PaymentRequired,
            'renewal_date' => '2026-11-04',
        ]);

        $this->assertDatabaseHas('servers', [
            'id' => $server->id,
            'owner_id' => $server->owner_id,
            'name' => $server->name,
            'ip_address' => $server->ip_address,
            'operating_system' => $server->operating_system,
            'provider' => $server->provider,
            'status' => 'payment_required',
            'deployed_apps_count' => $server->deployed_apps_count,
            'renewal_date' => '2026-11-04',
        ]);

        $fresh = Server::query()->findOrFail($server->id);

        $this->assertInstanceOf(ServerStatus::class, $fresh->status);
        $this->assertSame(ServerStatus::PaymentRequired, $fresh->status);
        $this->assertSame('2026-11-04', $fresh->renewal_date->format('Y-m-d'));

        Carbon::setTestNow('2026-10-05 12:00:00');

        $urgency = $fresh->renewalUrgency();
        $this->assertInstanceOf(RenewalUrgency::class, $urgency);
        $this->assertSame(RenewalUrgency::Warning, $urgency);
    }

    public function test_duplicate_ip_addresses_are_rejected_by_the_database(): void
    {
        $server = Server::factory()->create();

        $this->expectException(QueryException::class);

        Server::factory()->create(['ip_address' => $server->ip_address]);
    }
}
