<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Configuration\Constants\ConfigurationRevisionOperation;
use Modules\Configuration\Constants\ConfigurationValueType;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_configuration_value_revisions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->string('key', 191);
            $table->unsignedInteger('definition_version');
            $table->enum('operation', ConfigurationRevisionOperation::values());
            $table->longText('stored_value')->nullable();
            $table->enum('value_type', ConfigurationValueType::values());
            $table->boolean('is_sensitive')->default(false);
            $table->unsignedBigInteger('resulting_row_version')->nullable();
            $table->unsignedBigInteger('source_revision_id')->nullable();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('reason', 1000)->nullable();
            $table->timestamp('created_at');

            $table->unique(['id', 'tenant_id'], 'tenant_configuration_revisions_id_tenant_uk');
            $table->foreign(
                ['source_revision_id', 'tenant_id'],
                'tenant_configuration_revisions_source_tenant_fk',
            )->references(['id', 'tenant_id'])
                ->on('tenant_configuration_value_revisions')
                ->restrictOnDelete();
            $table->index(['tenant_id', 'key', 'created_at'], 'tenant_configuration_revisions_key_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_configuration_value_revisions');
    }
};
