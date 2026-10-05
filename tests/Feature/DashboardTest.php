<?php

namespace Tests\Feature;

use App\Enums\ServerStatus;
use App\Models\Server;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_stat_card_counts(): void
    {
        $user = User::factory()->create();
        Server::factory()->count(2)->create(['status' => ServerStatus::Active, 'deployed_apps_count' => 3]);
        Server::factory()->create(['status' => ServerStatus::PaymentRequired, 'deployed_apps_count' => 4]);
        Server::factory()->create(['status' => ServerStatus::Inactive, 'deployed_apps_count' => 0]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk()
            ->assertSee('Total servers')
            ->assertSee('Payment required')
            ->assertSee('Renewals due');

        // exact stat values inside their cards (avoid substring collisions with dates/IPs)
        $response->assertSee('>4<', false);   // total
        $response->assertSee('>2<', false);   // active
        $response->assertSee('>10<', false);  // apps sum
    }

    public function test_dashboard_filters_by_status(): void
    {
        $user = User::factory()->create();
        Server::factory()->create(['name' => 'keep-active', 'status' => ServerStatus::Active]);
        Server::factory()->create(['name' => 'hide-inactive', 'status' => ServerStatus::Inactive]);

        $this->actingAs($user)
            ->get('/dashboard?status=active')
            ->assertOk()
            ->assertSee('keep-active')
            ->assertDontSee('hide-inactive');
    }

    public function test_dashboard_filters_by_provider(): void
    {
        $user = User::factory()->create();
        Server::factory()->create(['name' => 'hetzner-box', 'provider' => 'Hetzner']);
        Server::factory()->create(['name' => 'vultr-box', 'provider' => 'Vultr']);

        $this->actingAs($user)->get('/dashboard?provider=Hetzner')
            ->assertOk()
            ->assertSee('hetzner-box')
            ->assertDontSee('vultr-box');
    }

    public function test_dashboard_search_matches_name_ip_and_os(): void
    {
        $user = User::factory()->create();
        Server::factory()->create([
            'name' => 'searchable-box',
            'ip_address' => '203.0.113.99',
            'operating_system' => 'Ubuntu 24.04',
        ]);
        Server::factory()->create(['name' => 'other-box', 'operating_system' => 'AlmaLinux 9']);

        $this->actingAs($user)->get('/dashboard?q=searchable')
            ->assertOk()->assertSee('searchable-box')->assertDontSee('other-box');

        $this->actingAs($user)->get('/dashboard?q=203.0.113.99')
            ->assertOk()->assertSee('searchable-box');

        $this->actingAs($user)->get('/dashboard?q=AlmaLinux')
            ->assertOk()->assertSee('other-box')->assertDontSee('searchable-box');
    }

    public function test_dashboard_owner_mine_filter(): void
    {
        $user = User::factory()->create();
        Server::factory()->create(['owner_id' => $user->id, 'name' => 'my-box']);
        Server::factory()->create(['name' => 'their-box']);

        $this->actingAs($user)->get('/dashboard?owner=mine')
            ->assertOk()
            ->assertSee('my-box')
            ->assertDontSee('their-box');
    }

    public function test_dashboard_sorts_by_renewal_date_ascending(): void
    {
        $user = User::factory()->create();
        Server::factory()->create(['name' => 'later-box', 'renewal_date' => '2026-12-01']);
        Server::factory()->create(['name' => 'sooner-box', 'renewal_date' => '2026-10-20']);

        $response = $this->actingAs($user)->get('/dashboard?sort=renewal_date&dir=asc');

        $response->assertOk();
        $content = $response->getContent();
        $this->assertLessThan(strpos($content, 'later-box'), strpos($content, 'sooner-box'));
    }

    public function test_status_can_be_changed_from_the_table(): void
    {
        $user = User::factory()->create();
        $server = Server::factory()->create(['owner_id' => $user->id, 'status' => ServerStatus::Active]);

        $this->actingAs($user)
            ->patch("/servers/{$server->id}/status", ['status' => 'inactive'])
            ->assertRedirect('/dashboard');

        $this->assertDatabaseHas('servers', ['id' => $server->id, 'status' => 'inactive']);
    }

    public function test_member_cannot_change_a_foreign_servers_status(): void
    {
        $member = User::factory()->create();
        $server = Server::factory()->create(['status' => ServerStatus::Active]);

        $this->actingAs($member)
            ->patch("/servers/{$server->id}/status", ['status' => 'inactive'])
            ->assertForbidden();

        $this->assertDatabaseHas('servers', ['id' => $server->id, 'status' => 'active']);
    }

    public function test_invalid_status_value_is_rejected(): void
    {
        $user = User::factory()->create();
        $server = Server::factory()->create(['owner_id' => $user->id]);

        $this->actingAs($user)
            ->patch("/servers/{$server->id}/status", ['status' => 'exploded'])
            ->assertSessionHasErrors('status');
    }

    public function test_empty_state_shows_when_no_servers(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/dashboard')->assertSee('No servers yet');
    }

    public function test_no_matches_state_when_filters_exclude_everything(): void
    {
        $user = User::factory()->create();
        Server::factory()->create(['provider' => 'Hetzner']);

        $this->actingAs($user)
            ->get('/dashboard?provider=NonexistentProvider')
            ->assertOk()
            ->assertSee('No matches for these filters');
    }

    public function test_due_soon_card_counts_renewals_within_30_days(): void
    {
        $user = User::factory()->create();
        Server::factory()->create(['renewal_date' => now()->addDays(10)->format('Y-m-d')]);
        Server::factory()->create(['renewal_date' => now()->addDays(90)->format('Y-m-d')]);
        Server::factory()->create(['renewal_date' => null]);

        $this->actingAs($user)->get('/dashboard')->assertOk()->assertSee('Renewals due');

        $this->assertSame(1, Server::whereNotNull('renewal_date')
            ->whereDate('renewal_date', '<=', now()->addDays(30)->toDateString())->count());
    }

    public function test_guests_are_redirected_from_dashboard(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_servers_path_redirects_to_dashboard(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/servers')->assertRedirect('/dashboard');
    }

    public function test_billing_warning_rows_are_visually_marked(): void
    {
        $user = User::factory()->create();
        Server::factory()->create([
            'owner_id' => $user->id,
            'status' => ServerStatus::PaymentRequired,
            'renewal_date' => now()->addDays(5)->format('Y-m-d'),
            'name' => 'warning-box',
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        // billing warning rows carry an explicit marker plus a rose background class
        $response->assertSee('data-billing-warning="1"', false);
        $response->assertSee('bg-rose-50');
    }
}
