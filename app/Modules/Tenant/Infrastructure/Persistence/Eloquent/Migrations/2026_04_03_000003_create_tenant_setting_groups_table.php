<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_setting_groups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete()->comment('Multi-tenant owner reference');
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');

            $table->string('key');
            $table->text('value')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('tenant_setting_groups', 'id')->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['tenant_id', 'key'], 'tenant_setting_groups_tenant_key_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_setting_groups');
    }
};
