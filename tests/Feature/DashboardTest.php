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
        Server::factory()->withApps(3)->count(2)->create(['status' => ServerStatus::Active]);
        Server::factory()->withApps(4)->create(['status' => ServerStatus::PaymentRequired]);
        Server::factory()->create(['status' => ServerStatus::Inactive]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk()
            ->assertSee('Total servers')
            ->assertSee('Payment required')
            ->assertSee('Renewals due');

        $response->assertViewHas('stats', fn (array $s) => $s['total'] === 4 && $s['active'] === 2 && $s['apps'] === 10);
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

    public function test_expired_active_servers_flip_to_payment_required(): void
    {
        $user = User::factory()->create();
        $expired = Server::factory()->create([
            'owner_id' => $user->id,
            'name' => 'expired-box',
            'status' => ServerStatus::Active,
            'renewal_date' => now()->subDays(3)->format('Y-m-d'),
        ]);
        $fresh = Server::factory()->create([
            'status' => ServerStatus::Active,
            'renewal_date' => now()->addDays(60)->format('Y-m-d'),
        ]);
        $inactive = Server::factory()->create([
            'status' => ServerStatus::Inactive,
            'renewal_date' => now()->subDays(10)->format('Y-m-d'),
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk()->assertSee('expired-box');
        $this->assertDatabaseHas('servers', ['id' => $expired->id, 'status' => 'payment_required']);
        $this->assertDatabaseHas('servers', ['id' => $fresh->id, 'status' => 'active']);
        $this->assertDatabaseHas('servers', ['id' => $inactive->id, 'status' => 'inactive']);
        $response->assertViewHas('stats', fn (array $s) => $s['payment'] === 1 && $s['active'] === 1 && $s['inactive'] === 1);
    }

    public function test_dashboard_renders_details_dialog_and_row_templates(): void
    {
        $user = User::factory()->create();
        $server = Server::factory()->create(['owner_id' => $user->id, 'name' => 'details-box']);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('server-details-dialog', false)
            ->assertSee('data-details-for="'.$server->id.'"', false)
            ->assertSee('id="server-details-'.$server->id.'"', false)
            ->assertSee('Deployed apps (')
            ->assertSee('href="'.route('servers.edit', $server).'"', false);
    }

    public function test_details_template_hides_edit_button_for_foreign_servers(): void
    {
        $member = User::factory()->create();
        $server = Server::factory()->create();

        $this->actingAs($member)
            ->get('/dashboard')
            ->assertOk()
            ->assertDontSee('href="'.route('servers.edit', $server).'"', false);
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

    public function test_dashboard_renders_bulk_select_controls(): void
    {
        $user = User::factory()->create();
        $server = Server::factory()->create(['owner_id' => $user->id]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('id="select-all-servers"', false)
            ->assertSee('data-select-row value="'.$server->id.'"', false)
            ->assertSee('id="bulk-actions"', false)
            ->assertSee('data-bulk-action="'.route('servers.bulkDestroy').'"', false)
            ->assertSee('data-row-delete', false);
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

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk()->assertSee('Renewals due');
        $response->assertViewHas('stats', fn (array $s) => $s['dueSoon'] === 1);
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

    public function test_invalid_sort_and_direction_are_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from('/dashboard')
            ->get('/dashboard?sort=evil')
            ->assertRedirect('/dashboard')
            ->assertSessionHasErrors('sort');

        $this->actingAs($user)
            ->from('/dashboard')
            ->get('/dashboard?dir=up')
            ->assertRedirect('/dashboard')
            ->assertSessionHasErrors('dir');
    }

    public function test_filter_validation_errors_surface_above_the_filters(): void
    {
        $user = User::factory()->create();

        // NB: no session-error assertion between the failed request and the render —
        // TestResponse::session() calls start(), and a second marshalErrorBag pass
        // over the JSON-serialized bag replaces it with an empty one. Rejection is
        // asserted separately in test_invalid_sort_and_direction_are_rejected.
        $this->actingAs($user)
            ->from('/dashboard')
            ->get('/dashboard?sort=evil');

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('bg-amber-100', false)
            ->assertSee('The selected sort is invalid');
    }

    public function test_search_input_is_escaped(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard?q="><script>alert(1)</script>');

        $response->assertOk();
        // the raw payload must never reach the output; its escaped form must
        $response->assertDontSee('<script>alert(1)</script>', false);
        $response->assertSee('&lt;script&gt;', false);
    }

    public function test_pagination_links_preserve_filter_query(): void
    {
        $user = User::factory()->create();
        Server::factory()->count(16)->create(['status' => ServerStatus::Active]);

        $response = $this->actingAs($user)->get('/dashboard?status=active&page=2');

        $response->assertOk();

        $servers = $response->viewData('servers');
        $this->assertCount(1, $servers->items());
        $this->assertStringContainsString('status=active', (string) $servers->links());
    }
}
