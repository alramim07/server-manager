<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DemoReset extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'demo:reset';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Wipe the database and reseed the demo data';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (! config('app.demo_enabled')) {
            $this->components->warn('Demo mode is disabled - skipping reset.');

            return self::SUCCESS;
        }

        $this->call('migrate:fresh', ['--force' => true, '--seed' => true]);

        $this->components->info('Demo database reset to seed state.');

        return self::SUCCESS;
    }
}
