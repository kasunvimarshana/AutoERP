<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_configuration_value_revisions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants', indexName: 'tenant_configuration_value_revisions_tenant_fk')->restrictOnDelete();
            $table->string('key', 191);
            $table->unsignedInteger('definition_version');
            $table->enum('operation', ['created', 'updated', 'removed', 'rolled_back']);
            $table->longText('stored_value')->nullable();
            $table->enum('value_type', ['string', 'integer', 'decimal', 'boolean', 'json']);
            $table->boolean('is_sensitive')->default(false);
            $table->unsignedBigInteger('resulting_row_version')->nullable();
            $table->unsignedBigInteger('source_revision_id')->nullable();
            $table->enum('actor_type', ['system', 'platform_operator', 'tenant_user']);
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('actor_name')->nullable();
            $table->string('actor_email')->nullable();
            $table->string('reason', 1000)->nullable();
            $table->dateTime('created_at');

            $table->unique(['id', 'tenant_id'], 'tenant_configuration_revisions_id_tenant_uk');
            $table->foreign(
                ['source_revision_id', 'tenant_id'],
                'tenant_configuration_revisions_source_tenant_fk',
            )->references(['id', 'tenant_id'])
                ->on('tenant_configuration_value_revisions')
                ->restrictOnDelete();
            $table->index(['tenant_id', 'key', 'created_at'], 'tenant_configuration_revisions_key_created_ix');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_configuration_value_revisions');
    }
};
