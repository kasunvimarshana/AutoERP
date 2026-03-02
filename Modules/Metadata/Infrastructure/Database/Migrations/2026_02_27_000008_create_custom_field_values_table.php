<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_field_values', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->foreignId('field_definition_id')
                ->constrained('custom_field_definitions')
                ->onDelete('cascade');
            $table->string('entity_type');
            $table->unsignedBigInteger('entity_id');
            $table->text('value')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->unique(
                ['tenant_id', 'field_definition_id', 'entity_type', 'entity_id'],
                'cfv_unique_per_entity'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_field_values');
    }
};
