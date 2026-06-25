<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Configuration\Constants\ConfigurationActorType;
use Modules\Configuration\Constants\ConfigurationRevisionOperation;
use Modules\Configuration\Constants\ConfigurationValueType;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('global_configuration_value_revisions', function (Blueprint $table): void {
            $table->id();
            $table->string('key', 191);
            $table->unsignedInteger('definition_version');
            $table->enum('operation', ConfigurationRevisionOperation::values());
            $table->longText('stored_value')->nullable();
            $table->enum('value_type', ConfigurationValueType::values());
            $table->boolean('is_sensitive')->default(false);
            $table->unsignedBigInteger('resulting_row_version')->nullable();
            $table->foreignId('source_revision_id')->nullable()
                ->constrained('global_configuration_value_revisions')
                ->restrictOnDelete();
            $table->enum('actor_type', ConfigurationActorType::values());
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('actor_name')->nullable();
            $table->string('actor_email')->nullable();
            $table->string('reason', 1000)->nullable();
            $table->timestamp('created_at');

            $table->index(['key', 'created_at'], 'global_configuration_revisions_key_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('global_configuration_value_revisions');
    }
};
