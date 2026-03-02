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
            $table->string('entity_type', 100);
            $table->unsignedBigInteger('entity_id');
            $table->unsignedBigInteger('field_id');
            $table->text('value')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'entity_type', 'entity_id', 'field_id']);
            $table->index(['tenant_id', 'entity_type', 'entity_id']);
            $table->foreign('field_id')->references('id')->on('custom_fields')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_field_values');
    }
};
