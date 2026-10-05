<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_shows_demo_credentials_when_enabled(): void
    {
        config(['app.demo_enabled' => true]);

        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertSee('Demo access');
        $response->assertSee('demo@example.com');
        $response->assertSee('demo1234');
    }

    public function test_login_form_is_prefilled_when_enabled(): void
    {
        config(['app.demo_enabled' => true]);

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('value="demo@example.com"', false)
            ->assertSee('value="demo1234"', false);
    }

    public function test_demo_box_is_hidden_when_disabled(): void
    {
        config(['app.demo_enabled' => false]);

        $this->get(route('login'))
            ->assertOk()
            ->assertDontSee('Demo access')
            ->assertDontSee('demo1234');
    }
}
