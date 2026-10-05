<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('servers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('name', 80);
            $table->string('ip_address', 45)->unique();
            $table->string('operating_system', 80);
            $table->string('provider', 80);
            $table->string('status', 20)->default('active');
            $table->unsignedInteger('deployed_apps_count')->default(0);
            $table->date('renewal_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('provider');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('servers');
    }
};
