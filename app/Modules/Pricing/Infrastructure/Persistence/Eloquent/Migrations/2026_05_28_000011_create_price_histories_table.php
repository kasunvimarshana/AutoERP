<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('row_version')->default(1)->comment('Used for optimistic concurrency control');
            $table->foreignId('tenant_id')->constrained('tenants', 'id')->cascadeOnDelete();
            $table->foreignId('organization_unit_id')
                ->nullable()
                ->constrained('organization_units', 'id')
                ->nullOnDelete();

            $table->string('entity_type');
            $table->unsignedBigInteger('entity_id');
            $table->string('field_name');
            $table->string('old_text')->nullable();
            $table->string('new_text')->nullable();
            $table->decimal('old_number', 20, 4)->nullable();
            $table->decimal('new_number', 20, 4)->nullable();
            $table->boolean('old_boolean')->nullable();
            $table->boolean('new_boolean')->nullable();
            $table->date('old_date')->nullable();
            $table->date('new_date')->nullable();
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->dateTime('changed_at');
            $table->text('reason')->nullable();
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'entity_type', 'entity_id'], 'price_histories_entity_idx');
            $table->index(['tenant_id', 'changed_at'], 'price_histories_changed_at_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_histories');
    }
};
