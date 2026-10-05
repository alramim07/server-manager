<?php

namespace Tests\Feature;

use App\Models\Server;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class ServerPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_any_authenticated_user_can_view_any_server(): void
    {
        $member = User::factory()->create();
        $server = Server::factory()->create();

        $this->assertTrue(Gate::forUser($member)->allows('view', $server));
    }

    public function test_owner_can_update_and_delete_their_server(): void
    {
        $owner = User::factory()->create();
        $server = Server::factory()->create(['owner_id' => $owner->id]);

        $this->assertTrue(Gate::forUser($owner)->allows('update', $server));
        $this->assertTrue(Gate::forUser($owner)->allows('delete', $server));
    }

    public function test_member_cannot_update_or_delete_someone_elses_server(): void
    {
        $member = User::factory()->create();
        $server = Server::factory()->create();

        $this->assertFalse(Gate::forUser($member)->allows('update', $server));
        $this->assertFalse(Gate::forUser($member)->allows('delete', $server));
    }

    public function test_admin_can_update_and_delete_any_server(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $server = Server::factory()->create();

        $this->assertTrue(Gate::forUser($admin)->allows('update', $server));
        $this->assertTrue(Gate::forUser($admin)->allows('delete', $server));
    }
}
