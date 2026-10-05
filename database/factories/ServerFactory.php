<?php

namespace Database\Factories;

use App\Enums\ServerStatus;
use App\Models\Server;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Server>
 */
class ServerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'owner_id' => User::factory(),
            'name' => 'srv-'.fake()->unique()->numberBetween(1, 9999),
            'ip_address' => '203.0.113.'.fake()->unique()->numberBetween(1, 254),
            'operating_system' => fake()->randomElement(['Ubuntu 24.04', 'Debian 12', 'AlmaLinux 9', 'Rocky Linux 9']),
            'provider' => fake()->randomElement(['Hetzner', 'DigitalOcean', 'Contabo', 'Vultr', 'Linode']),
            'status' => ServerStatus::Active,
            'deployed_apps_count' => fake()->numberBetween(0, 8),
            'renewal_date' => fake()->dateTimeBetween('+5 days', '+90 days')->format('Y-m-d'),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
