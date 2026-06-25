<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Configuration\Constants\ConfigurationRevisionOperation;
use Modules\Configuration\Constants\ConfigurationScope;
use Modules\Configuration\Constants\ConfigurationValueType;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('configuration_value_revisions', function (Blueprint $table): void {
            $table->id();
            $table->enum('scope', ConfigurationScope::values());
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->cascadeOnDelete();
            $table->unsignedBigInteger('organization_unit_id')->nullable();
            $table->string('key', 191);
            $table->enum('operation', ConfigurationRevisionOperation::values());
            $table->longText('stored_value')->nullable();
            $table->enum('value_type', ConfigurationValueType::values());
            $table->boolean('is_sensitive')->default(false);
            $table->unsignedBigInteger('resulting_row_version')->nullable();
            $table->foreignId('source_revision_id')->nullable()
                ->constrained('configuration_value_revisions')
                ->nullOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reason', 1000)->nullable();
            $table->timestamp('created_at');

            $table->foreign(
                ['organization_unit_id', 'tenant_id'],
                'configuration_revisions_org_tenant_fk',
            )->references(['id', 'tenant_id'])
                ->on('organization_units')
                ->cascadeOnDelete();
            $table->index(
                ['scope', 'tenant_id', 'organization_unit_id', 'key', 'created_at'],
                'configuration_revisions_scope_key_created_idx',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('configuration_value_revisions');
    }
};
