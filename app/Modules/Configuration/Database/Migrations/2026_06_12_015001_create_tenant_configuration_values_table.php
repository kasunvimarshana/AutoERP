<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Configuration\Constants\ConfigurationValueType;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_configuration_values', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1);
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('key', 191);
            $table->longText('value')->nullable();
            $table->enum('value_type', ConfigurationValueType::values());
            $table->boolean('is_sensitive')->default(false);
            $table->timestamps();

            $table->unique(
                ['tenant_id', 'key'],
                'tenant_configuration_values_tenant_key_uk',
            );
            $table->index(
                ['tenant_id', 'updated_at'],
                'tenant_configuration_values_tenant_updated_idx',
            );

            $table->unique(['id', 'tenant_id'], 'tenant_configuration_values_id_tenant_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_configuration_values');
    }
};
