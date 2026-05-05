<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
$table->foreignId('org_unit_id')->nullable()->constrained('org_units', 'id')->nullOnDelete();
$table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('user_id')->constrained(null, 'id', 'user_devices_user_id_fk')->cascadeOnDelete();
            $table->string('device_token');
            $table->string('platform')->nullable(); // ios, android, web
            $table->string('device_name')->nullable();
            $table->timestamp('last_active_at')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'user_id', 'device_token'], 'user_devices_tenant_user_token_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_devices');
    }
};
