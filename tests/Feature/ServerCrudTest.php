<?php

namespace Tests\Feature;

use App\Models\Server;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServerCrudTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(array $overrides = []): array
    {
        return $overrides + [
            'name' => 'web-prod-01',
            'ip_address' => '203.0.113.10',
            'operating_system' => 'Ubuntu 24.04',
            'provider' => 'Hetzner',
            'status' => 'active',
            'deployed_apps' => ['nginx', 'postgres', 'redis', 'grafana'],
            'renewal_date' => '2026-11-01',
            'notes' => 'primary web server',
        ];
    }

    public function test_guests_cannot_create_servers(): void
    {
        $this->post('/servers', $this->validPayload())->assertRedirect('/login');
    }

    public function test_users_can_create_a_server(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/servers', $this->validPayload());

        $response->assertRedirect('/dashboard');
        $this->assertDatabaseHas('servers', [
            'name' => 'web-prod-01',
            'ip_address' => '203.0.113.10',
            'owner_id' => $user->id,
            'status' => 'active',
        ]);

        $server = Server::firstWhere('ip_address', '203.0.113.10');
        $this->assertSame(['nginx', 'postgres', 'redis', 'grafana'], $server->deployedApps()->pluck('name')->all());
    }

    public function test_create_requires_valid_input(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/servers', [
            'name' => '',
            'ip_address' => 'not-an-ip',
            'operating_system' => '',
            'provider' => '',
            'status' => 'exploded',
            'deployed_apps' => [str_repeat('x', 81)],
        ])->assertSessionHasErrors([
            'name', 'ip_address', 'operating_system', 'provider', 'status', 'deployed_apps.0',
        ]);
    }

    public function test_at_most_50_deployed_apps_are_allowed(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/servers', $this->validPayload([
                'deployed_apps' => array_map(fn (int $i) => 'app-'.$i, range(1, 51)),
            ]))
            ->assertSessionHasErrors('deployed_apps');
    }

    public function test_deployed_apps_can_be_submitted_as_plain_text_lines(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/servers', $this->validPayload([
            'deployed_apps' => null,
            'deployed_apps_text' => "nginx\nredis\n\nnginx\n  caddy  \n",
        ]))->assertSessionHasNoErrors();

        $server = Server::firstWhere('ip_address', '203.0.113.10');
        $this->assertSame(['nginx', 'redis', 'caddy'], $server->deployedApps()->pluck('name')->all());
    }

    public function test_updating_a_server_replaces_its_deployed_app_names(): void
    {
        $user = User::factory()->create();
        $server = Server::factory()->withApps(2)->create(['owner_id' => $user->id]);

        $this->actingAs($user)
            ->put("/servers/{$server->id}", $this->validPayload(['deployed_apps' => ['only-this']]))
            ->assertRedirect('/dashboard');

        $this->assertSame(['only-this'], $server->deployedApps()->pluck('name')->all());
    }

    public function test_duplicate_ip_addresses_are_rejected(): void
    {
        $user = User::factory()->create();
        Server::factory()->create(['ip_address' => '203.0.113.10']);

        $this->actingAs($user)
            ->post('/servers', $this->validPayload())
            ->assertSessionHasErrors('ip_address');
    }

    public function test_owner_can_edit_their_server(): void
    {
        $user = User::factory()->create();
        $server = Server::factory()->create(['owner_id' => $user->id, 'name' => 'old-name']);

        $this->actingAs($user)->put("/servers/{$server->id}", $this->validPayload(['name' => 'new-name']))
            ->assertRedirect('/dashboard');

        $this->assertDatabaseHas('servers', ['id' => $server->id, 'name' => 'new-name']);
    }

    public function test_member_cannot_edit_a_foreign_server_and_gets_403(): void
    {
        $member = User::factory()->create();
        $server = Server::factory()->create();

        $this->actingAs($member)
            ->put("/servers/{$server->id}", $this->validPayload(['name' => 'hacked']))
            ->assertForbidden();

        $this->assertDatabaseHas('servers', ['id' => $server->id, 'name' => $server->name]);
    }

    public function test_admin_can_delete_any_server(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $server = Server::factory()->create();

        $this->actingAs($admin)->delete("/servers/{$server->id}")->assertRedirect('/dashboard');
        $this->assertDatabaseMissing('servers', ['id' => $server->id]);
    }

    public function test_delete_confirmation_flash_shows_on_the_dashboard(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $server = Server::factory()->create();

        $this->actingAs($admin)->delete("/servers/{$server->id}")->assertRedirect('/dashboard');

        $this->get('/dashboard')->assertSee('Server deleted.');
    }

    public function test_member_cannot_delete_a_foreign_server(): void
    {
        $member = User::factory()->create();
        $server = Server::factory()->create();

        $this->actingAs($member)->delete("/servers/{$server->id}")->assertForbidden();
        $this->assertDatabaseHas('servers', ['id' => $server->id]);
    }

    public function test_updating_a_server_may_keep_its_own_ip_address(): void
    {
        $user = User::factory()->create();
        $server = Server::factory()->create([
            'owner_id' => $user->id,
            'name' => 'my-server',
            'ip_address' => '203.0.113.42',
        ]);

        $this->actingAs($user)
            ->put("/servers/{$server->id}", $this->validPayload([
                'name' => 'my-server',
                'ip_address' => '203.0.113.42',
            ]))
            ->assertRedirect('/dashboard')
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('servers', [
            'id' => $server->id,
            'name' => 'my-server',
            'ip_address' => '203.0.113.42',
        ]);
    }

    public function test_index_page_renders_servers_delete_forms_and_flash(): void
    {
        $user = User::factory()->create();
        $server = Server::factory()->create(['owner_id' => $user->id, 'name' => 'index-hero-box']);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee($server->name)
            ->assertSee('delete-dialog')
            ->assertSee('action="'.route('servers.destroy', $server).'"', false);

        $this->actingAs($user)
            ->withSession(['status' => 'Server added.'])
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Server added.');
    }

    public function test_show_page_renders(): void
    {
        $user = User::factory()->create();
        $server = Server::factory()->create(['owner_id' => $user->id]);

        $this->actingAs($user)
            ->get("/servers/{$server->id}")
            ->assertOk()
            ->assertSee($server->name)
            ->assertSee('action="'.route('servers.destroy', $server).'"', false);
    }

    public function test_edit_page_renders(): void
    {
        $user = User::factory()->create();
        $server = Server::factory()->withApps(1)->create(['owner_id' => $user->id]);

        $this->actingAs($user)->get("/servers/{$server->id}/edit")
            ->assertOk()
            ->assertSee($server->ip_address)
            ->assertSee('value="app-1"', false)
            ->assertSee('+ Add app')
            ->assertSee('href="'.route('dashboard').'" class="rounded-md border', false)
            ->assertDontSee('href="'.route('servers.show', $server).'"', false);
    }

    public function test_create_page_renders(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/servers/create')
            ->assertOk()
            ->assertSee('<h1', false)
            ->assertSee('New server')
            ->assertSee('href="'.route('dashboard').'" class="rounded-md border', false);
    }

    public function test_validation_failure_redirects_back_with_old_input(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from('/servers/create')
            ->post('/servers', ['name' => 'my-web', 'ip_address' => 'not-an-ip'])
            ->assertRedirect('/servers/create')
            ->assertSessionHasErrors('ip_address')
            ->assertSessionHasInput('name', 'my-web');
    }

    public function test_guests_are_redirected_from_all_server_routes(): void
    {
        $server = Server::factory()->create();

        $this->get('/servers')->assertRedirect('/login');
        $this->get('/servers/create')->assertRedirect('/login');
        $this->get("/servers/{$server->id}")->assertRedirect('/login');
        $this->get("/servers/{$server->id}/edit")->assertRedirect('/login');
        $this->put("/servers/{$server->id}", [])->assertRedirect('/login');
        $this->delete("/servers/{$server->id}")->assertRedirect('/login');
    }
}
