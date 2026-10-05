<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationGateTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_page_is_available_by_default(): void
    {
        $this->get(route('register'))->assertOk();
    }

    public function test_registration_page_returns_404_when_disabled(): void
    {
        config(['app.registration_enabled' => false]);

        $this->get(route('register'))->assertNotFound();
    }

    public function test_registration_post_is_rejected_when_disabled(): void
    {
        config(['app.registration_enabled' => false]);

        $this->post(route('register.store'), [
            'name' => 'Sneaky',
            'email' => 'sneaky@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertNotFound();

        $this->assertDatabaseMissing('users', ['email' => 'sneaky@example.com']);
    }

    public function test_register_links_are_hidden_when_disabled(): void
    {
        config(['app.registration_enabled' => false]);

        $response = $this->get(route('login'));

        $response->assertOk();
        $this->assertStringNotContainsString('/register', $response->getContent());
    }
}
