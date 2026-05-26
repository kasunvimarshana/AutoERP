<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')
                ->constrained('tenants', 'id')
                ->cascadeOnDelete()
                ->comment('Multi-tenant owner reference');
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');

            $table->foreignId('group_id')->nullable()->constrained('tenant_setting_groups', 'id')->nullOnDelete();
            $table->string('key');
            $table->longText('value')->nullable();
            $table->enum(
                'value_type',
                ['null', 'string', 'integer', 'float', 'boolean', 'json', 'encrypted']
            )->default('null');
            $table->boolean('is_sensitive')->default(false);
            $table->unsignedBigInteger('created_by')->nullable()->index('tenant_settings_created_by_idx');
            $table->unsignedBigInteger('updated_by')->nullable()->index('tenant_settings_updated_by_idx');
            $table->unsignedBigInteger('deleted_by')->nullable()->index('tenant_settings_deleted_by_idx');

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'key'], 'tenant_settings_tenant_key_uk');
            $table->index(['tenant_id', 'group_id'], 'tenant_settings_tenant_group_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_settings');
    }
};
