<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_fields', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('entity_type', 100);
            $table->string('field_key', 100);
            $table->string('field_label', 255);
            $table->string('field_type', 50);
            $table->boolean('is_required')->default(false);
            $table->text('default_value')->nullable();
            $table->integer('sort_order')->default(0);
            $table->json('options')->nullable();
            $table->text('validation_rules')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['tenant_id', 'entity_type', 'field_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_fields');
    }
};
