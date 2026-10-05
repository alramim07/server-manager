<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login_from_the_dashboard(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_guests_see_the_login_page(): void
    {
        $this->get('/login')->assertOk()->assertSee('No account yet?');
    }

    public function test_users_can_register_and_the_first_user_becomes_admin(): void
    {
        $response = $this->post('/register', [
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
        $this->assertSame('admin', User::firstWhere('email', 'ada@example.com')->role);
    }

    public function test_subsequent_users_are_members(): void
    {
        User::factory()->create(['email' => 'first@example.com']);

        $this->post('/register', [
            'name' => 'Grace Hopper',
            'email' => 'grace@example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
        ])->assertRedirect('/dashboard');

        $this->assertSame('member', User::firstWhere('email', 'grace@example.com')->role);
    }

    public function test_duplicate_emails_are_rejected(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $this->post('/register', [
            'name' => 'Someone',
            'email' => 'taken@example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
        ])->assertSessionHasErrors('email');
    }

    public function test_users_can_login_with_correct_credentials(): void
    {
        User::factory()->create(['email' => 'ada@example.com', 'password' => 'secret-password']);

        $response = $this->post('/login', [
            'email' => 'ada@example.com',
            'password' => 'secret-password',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
    }

    public function test_login_with_wrong_credentials_fails(): void
    {
        User::factory()->create(['email' => 'ada@example.com', 'password' => 'secret-password']);

        $this->from('/login')->post('/login', [
            'email' => 'ada@example.com',
            'password' => 'wrong-password',
        ])->assertRedirect('/login')->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_authenticated_users_are_redirected_away_from_auth_pages(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/login')->assertRedirect('/dashboard');
        $this->actingAs($user)->get('/register')->assertRedirect('/dashboard');
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_register_ignores_a_posted_role(): void
    {
        User::factory()->create(['email' => 'first@example.com']);

        $this->post('/register', [
            'name' => 'Mallory',
            'email' => 'mallory@example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
            'role' => 'admin',
        ])->assertRedirect('/dashboard');

        $this->assertSame('member', User::firstWhere('email', 'mallory@example.com')->role);
    }

    public function test_login_redirects_to_the_intended_page(): void
    {
        User::factory()->create(['email' => 'ada@example.com', 'password' => 'secret-password']);

        $this->get('/');

        $this->post('/login', [
            'email' => 'ada@example.com',
            'password' => 'secret-password',
        ])->assertRedirect('/');
    }

    public function test_session_id_changes_after_login(): void
    {
        User::factory()->create(['email' => 'ada@example.com', 'password' => 'secret-password']);

        $this->get('/login');
        $before = session()->getId();

        $this->post('/login', [
            'email' => 'ada@example.com',
            'password' => 'secret-password',
        ]);

        $this->assertNotSame($before, session()->getId());
    }
}
