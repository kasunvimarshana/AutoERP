<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_unit_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete()->comment('Multi-tenant owner reference');
            $table->foreignId('organization_unit_id')->constrained('organization_units', 'id')->cascadeOnDelete()->comment('Branch or department ownership');
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');

            $table->foreignId('group_id')->constrained('organization_unit_setting_groups', 'id')->cascadeOnDelete();
            $table->string('key');
            $table->text('value')->nullable();

            $table->timestamps();

            $table->unique(['tenant_id', 'organization_unit_id', 'group_id', 'key'], 'organization_unit_settings_group_key_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_unit_settings');
    }
};
