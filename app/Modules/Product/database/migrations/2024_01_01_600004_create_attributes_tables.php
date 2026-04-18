<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attribute_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained(null, 'id', 'attribute_groups_tenant_id_fk')->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained(null, 'id', 'attributes_tenant_id_fk')->cascadeOnDelete();
            $table->foreignId('group_id')->nullable()->constrained('attribute_groups', 'id', 'attributes_group_id_fk')->nullOnDelete();
            $table->string('name');
            $table->enum('type', ['text', 'select', 'number', 'boolean'])->default('select');
            $table->boolean('is_required')->default(false);
            $table->timestamps();
        });

        Schema::create('attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attribute_id')->constrained(null, 'id', 'attribute_values_attribute_id_fk')->cascadeOnDelete();
            $table->string('value');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['attribute_id', 'value'], 'attribute_values_attribute_value_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attribute_values');
        Schema::dropIfExists('attributes');
        Schema::dropIfExists('attribute_groups');
    }
};
