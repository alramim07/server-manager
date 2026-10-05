<?php

namespace Database\Seeders;

use App\Models\Server;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => config('app.demo_email')],
            ['name' => 'Demo Admin', 'password' => Hash::make(config('app.demo_password')), 'role' => 'admin'],
        );

        User::firstOrCreate(
            ['email' => 'alex@example.com'],
            ['name' => 'Alex Doe', 'password' => Hash::make(config('app.demo_password')), 'role' => 'member'],
        );

        User::firstOrCreate(
            ['email' => 'jordan@example.com'],
            ['name' => 'Jordan Lee', 'password' => Hash::make(config('app.demo_password')), 'role' => 'member'],
        );

        if (! Server::query()->exists()) {
            $this->call(ServerSeeder::class);
        }
    }
}
