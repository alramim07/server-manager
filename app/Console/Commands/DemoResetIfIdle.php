<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DemoResetIfIdle extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'demo:reset-if-idle';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset the demo database when no session is active within the idle window';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (! config('app.demo_enabled')) {
            $this->components->warn('Demo mode is disabled - skipping reset.');

            return self::SUCCESS;
        }

        if (config('session.driver') !== 'database') {
            $this->components->warn('Session driver is not database - cannot verify idleness, skipping reset.');

            return self::SUCCESS;
        }

        $minutes = (int) config('app.demo_idle_minutes');

        if (! Schema::hasTable('sessions')) {
            $this->components->warn('Sessions table not found - skipping reset.');

            return self::SUCCESS;
        }

        $hasRecentSession = DB::table('sessions')
            ->where('last_activity', '>=', now()->subMinutes($minutes)->getTimestamp())
            ->exists();

        if ($hasRecentSession) {
            $this->components->info("Active session within {$minutes} min - skipping reset.");

            return self::SUCCESS;
        }

        $this->call('migrate:fresh', ['--force' => true, '--seed' => true]);
        $this->components->info('Idle window exceeded - demo database reset.');

        return self::SUCCESS;
    }
}
