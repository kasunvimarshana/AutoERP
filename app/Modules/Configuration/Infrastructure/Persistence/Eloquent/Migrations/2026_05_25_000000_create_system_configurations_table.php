<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_configurations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');
            $table->string('key')->unique();
            $table->longText('value')->nullable();
            $table->enum(
                'value_type',
                ['null', 'string', 'integer', 'float', 'boolean', 'json', 'encrypted']
            )->default('null');
            $table->enum('source', ['database', 'environment', 'runtime'])->default('database');
            $table->string('description')->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->index('system_configurations_created_by_idx');
            $table->unsignedBigInteger('updated_by')->nullable()->index('system_configurations_updated_by_idx');
            $table->unsignedBigInteger('deleted_by')->nullable()->index('system_configurations_deleted_by_idx');
            $table->timestamps();
            $table->softDeletes();

            $table->index('source');
        });

        Schema::create('tenant_configurations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')
                ->constrained('tenants', 'id')
                ->cascadeOnDelete()
                ->comment('Multi-tenant owner reference');
            $table->json('metadata')->nullable()->comment('Extensible custom dynamic data');
            $table->string('key');
            $table->longText('value')->nullable();
            $table->enum(
                'value_type',
                ['null', 'string', 'integer', 'float', 'boolean', 'json', 'encrypted']
            )->default('null');
            $table->enum('source', ['database', 'environment', 'runtime'])->default('database');
            $table->string('description')->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->index('tenant_configurations_created_by_idx');
            $table->unsignedBigInteger('updated_by')->nullable()->index('tenant_configurations_updated_by_idx');
            $table->unsignedBigInteger('deleted_by')->nullable()->index('tenant_configurations_deleted_by_idx');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'key'], 'tenant_configurations_tenant_key_uk');
            $table->index(['tenant_id', 'source'], 'tenant_configurations_tenant_source_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_configurations');
        Schema::dropIfExists('system_configurations');
    }
};
