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
            'deployed_apps_count' => 4,
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

        $response->assertRedirect(route('servers.show', Server::firstWhere('ip_address', '203.0.113.10')));
        $this->assertDatabaseHas('servers', [
            'name' => 'web-prod-01',
            'ip_address' => '203.0.113.10',
            'owner_id' => $user->id,
            'status' => 'active',
        ]);
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
            'deployed_apps_count' => -1,
        ])->assertSessionHasErrors([
            'name', 'ip_address', 'operating_system', 'provider', 'status', 'deployed_apps_count',
        ]);
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
            ->assertRedirect(route('servers.show', $server));

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

        $this->actingAs($admin)->delete("/servers/{$server->id}")->assertRedirect(route('servers.index'));
        $this->assertDatabaseMissing('servers', ['id' => $server->id]);
    }

    public function test_member_cannot_delete_a_foreign_server(): void
    {
        $member = User::factory()->create();
        $server = Server::factory()->create();

        $this->actingAs($member)->delete("/servers/{$server->id}")->assertForbidden();
        $this->assertDatabaseHas('servers', ['id' => $server->id]);
    }

    public function test_edit_page_and_show_page_render(): void
    {
        $user = User::factory()->create();
        $server = Server::factory()->create(['owner_id' => $user->id]);

        $this->actingAs($user)->get("/servers/{$server->id}")->assertOk()->assertSee($server->name);
        $this->actingAs($user)->get("/servers/{$server->id}/edit")->assertOk()->assertSee($server->ip_address);
    }

    public function test_create_page_renders(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/servers/create')->assertOk()->assertSee('New server');
    }

    public function test_validation_failure_redirects_back_with_old_input(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from('/servers/create')
            ->post('/servers', ['name' => ''])
            ->assertRedirect('/servers/create')
            ->assertSessionHasErrors('name');
    }

    public function test_guests_are_redirected_from_all_server_routes(): void
    {
        $server = Server::factory()->create();

        $this->get('/servers')->assertRedirect('/login');
        $this->get('/servers/create')->assertRedirect('/login');
        $this->get("/servers/{$server->id}")->assertRedirect('/login');
        $this->delete("/servers/{$server->id}")->assertRedirect('/login');
    }
}
