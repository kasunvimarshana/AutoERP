<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Configuration\Constants\ConfigurationRevisionAction;
use Modules\Configuration\Constants\ConfigurationScope;
use Modules\Configuration\Constants\ConfigurationValueType;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('configuration_value_revisions', function (Blueprint $table): void {
            $table->id();
            $table->enum('scope', ConfigurationScope::values());
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('organization_unit_id')->nullable();
            $table->string('key', 191);
            $table->enum('action', ConfigurationRevisionAction::values());
            $table->enum('value_type', ConfigurationValueType::values());
            $table->boolean('is_sensitive')->default(false);
            $table->boolean('before_exists');
            $table->json('before_value')->nullable();
            $table->boolean('after_exists');
            $table->json('after_value')->nullable();
            $table->unsignedBigInteger('entry_row_version');
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->string('changed_by_name')->nullable();
            $table->timestamp('created_at');

            $table->index(
                ['scope', 'key', 'created_at'],
                'configuration_revisions_scope_key_created_idx',
            );
            $table->index(
                ['tenant_id', 'organization_unit_id', 'key', 'created_at'],
                'configuration_revisions_owner_key_created_idx',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('configuration_value_revisions');
    }
};
