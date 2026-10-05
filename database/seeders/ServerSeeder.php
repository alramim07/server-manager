<?php

namespace Database\Seeders;

use App\Enums\ServerStatus;
use App\Models\Server;
use App\Models\User;
use Illuminate\Database\Seeder;

class ServerSeeder extends Seeder
{
    private const NAME_PREFIXES = [
        'web-prod',
        'web-stage',
        'api',
        'db-primary',
        'db-replica',
        'worker',
        'cache',
        'vpn',
        'monitor',
        'backup',
    ];

    private const OPERATING_SYSTEMS = [
        'Ubuntu 24.04',
        'Ubuntu 22.04',
        'Debian 12',
        'AlmaLinux 9',
        'Rocky Linux 9',
        'Windows Server 2022',
    ];

    private const PROVIDERS = ['Hetzner', 'DigitalOcean', 'Contabo', 'Vultr', 'Linode'];

    /**
     * Create 50 realistic demo servers owned by the first admin user.
     *
     * Existing rows are left untouched; run repeatedly to append more.
     */
    public function run(): void
    {
        $owner = User::where('role', 'admin')->orderBy('id')->first() ?? User::query()->orderBy('id')->first();

        if ($owner === null) {
            $this->command?->warn('No users found — create a user (or run DatabaseSeeder) first.');

            return;
        }

        foreach ($this->distribution() as $index => $status) {
            Server::create([
                'owner_id' => $owner->id,
                'name' => $this->name($index),
                'ip_address' => fake()->unique()->ipv4(),
                'operating_system' => fake()->randomElement(self::OPERATING_SYSTEMS),
                'provider' => fake()->randomElement(self::PROVIDERS),
                'status' => $status,
                'deployed_apps_count' => fake()->numberBetween(0, 12),
                'renewal_date' => $this->renewalDate($index),
                'notes' => fake()->boolean(40) ? fake()->sentence() : null,
            ]);
        }

        $this->command?->info("Seeded 50 demo servers owned by {$owner->email}.");
    }

    /**
     * ~65% active, 20% payment required, ~15% inactive (33/10/7 of 50) —
     * shuffled so the table does not show clumped status runs.
     *
     * @return array<int, ServerStatus>
     */
    private function distribution(): array
    {
        $statuses = array_merge(
            array_fill(0, 33, ServerStatus::Active),
            array_fill(0, 10, ServerStatus::PaymentRequired),
            array_fill(0, 7, ServerStatus::Inactive),
        );

        shuffle($statuses);

        return $statuses;
    }

    private function name(int $index): string
    {
        $prefix = self::NAME_PREFIXES[$index % count(self::NAME_PREFIXES)];
        $sequence = intdiv($index, count(self::NAME_PREFIXES)) + 1;

        return sprintf('%s-%02d', $prefix, $sequence);
    }

    private function renewalDate(int $index): ?string
    {
        return match ($index % 10) {
            0, 1 => fake()->dateTimeBetween('-60 days', '-2 days')->format('Y-m-d'),
            2, 3 => fake()->dateTimeBetween('+1 day', '+30 days')->format('Y-m-d'),
            9 => null,
            default => fake()->dateTimeBetween('+31 days', '+180 days')->format('Y-m-d'),
        };
    }
}
