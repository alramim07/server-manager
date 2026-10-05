<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const APP_POOL = [
        'nginx-proxy', 'postgres', 'redis', 'grafana', 'node-api', 'wordpress', 'gitea',
        'nextcloud', 'caddy', 'prometheus', 'mysql', 'minio', 'uptime-kuma', 'wireguard',
        'vaultwarden', 'portainer', 'matomo', 'ghost', 'airflow', 'keycloak', 'elastic',
        'kibana', 'adminer', 'outline', 'discourse', 'n8n', 'linkding', 'immich',
        'jellyfin', 'navidrome', 'paperless', 'beszel', 'homepage', 'rsync', 'mailcow', 'restic',
    ];

    public function up(): void
    {
        Schema::create('deployed_apps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained()->cascadeOnDelete();
            $table->string('name', 80);
            $table->timestamps();
        });

        if (Schema::hasColumn('servers', 'deployed_apps_count')) {
            $this->backfillFromCounts();

            Schema::table('servers', function (Blueprint $table) {
                $table->dropColumn('deployed_apps_count');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('servers', 'deployed_apps_count')) {
            Schema::table('servers', function (Blueprint $table) {
                $table->unsignedInteger('deployed_apps_count')->default(0);
            });
        }

        $counts = DB::table('deployed_apps')
            ->select('server_id', DB::raw('COUNT(*) as app_count'))
            ->groupBy('server_id')
            ->pluck('app_count', 'server_id');

        foreach ($counts as $serverId => $count) {
            DB::table('servers')->where('id', $serverId)->update(['deployed_apps_count' => $count]);
        }

        Schema::dropIfExists('deployed_apps');
    }

    private function backfillFromCounts(): void
    {
        DB::table('servers')->orderBy('id')->chunkById(100, function ($servers) {
            foreach ($servers as $server) {
                foreach ($this->appNames((int) $server->deployed_apps_count) as $name) {
                    DB::table('deployed_apps')->insert([
                        'server_id' => $server->id,
                        'name' => $name,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        });
    }

    /**
     * @return array<int, string>
     */
    private function appNames(int $count): array
    {
        $poolSize = count(self::APP_POOL);
        $names = [];

        for ($i = 0; $i < $count; $i++) {
            $base = self::APP_POOL[$i % $poolSize];
            $names[] = $i < $poolSize
                ? $base
                : $base.'-'.(intdiv($i, $poolSize) + 1);
        }

        return $names;
    }
};
